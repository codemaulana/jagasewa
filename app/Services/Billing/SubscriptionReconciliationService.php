<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Services\AuditService;
use App\Services\ErrorMonitoringService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionReconciliationService
{
    public function __construct(
        protected SubscriptionStateMachine $stateMachine,
        protected ErrorMonitoringService $errorMonitoring
    ) {}

    /**
     * Query gateway status for a subscription.
     * Returns normalized status: 'PAID', 'PENDING', 'EXPIRED', or null on failure.
     */
    public function fetchGatewayStatus(Subscription $subscription): ?string
    {
        $gatewayId = $subscription->gateway_subscription_id;

        if (empty($gatewayId)) {
            return null;
        }

        // 1. Detect if Xendit
        if (str_starts_with($gatewayId, 'inv_') || str_starts_with($gatewayId, 'xen_')) {
            $xenditKey = config('services.xendit.secret_key');

            if (empty($xenditKey)) {
                return null;
            }

            try {
                $response = Http::withBasicAuth($xenditKey, '')
                    ->timeout(10)
                    ->get("https://api.xendit.co/v2/invoices/{$gatewayId}");

                if ($response->successful()) {
                    $rawStatus = strtoupper((string) ($response->json('status') ?? ''));

                    return match ($rawStatus) {
                        'PAID', 'SETTLED' => 'PAID',
                        'EXPIRED' => 'EXPIRED',
                        default => 'PENDING',
                    };
                }

                Log::warning("Xendit reconciliation invoice fetch failed [{$gatewayId}]: ".$response->status());
            } catch (Exception $e) {
                Log::error("Xendit reconciliation network error [{$gatewayId}]: ".$e->getMessage());
            }

            return null;
        }

        // 2. Mayar Gateway
        $mayarUrl = rtrim(config('services.mayar.api_url', 'https://api.mayar.id/hl/v1'), '/');
        $mayarKey = config('services.mayar.api_key');

        if (empty($mayarKey)) {
            return null;
        }

        try {
            // Check invoice or payment endpoint
            $response = Http::withToken($mayarKey)
                ->timeout(10)
                ->get("{$mayarUrl}/invoice/{$gatewayId}");

            if (! $response->successful()) {
                $response = Http::withToken($mayarKey)
                    ->timeout(10)
                    ->get("{$mayarUrl}/payment/{$gatewayId}");
            }

            if ($response->successful()) {
                $data = $response->json('data') ?? $response->json() ?? [];
                $rawStatus = strtoupper((string) ($data['status'] ?? $data['payment_status'] ?? ''));

                return match ($rawStatus) {
                    'PAID', 'SETTLED', 'SUCCESS' => 'PAID',
                    'EXPIRED', 'FAILED' => 'EXPIRED',
                    default => 'PENDING',
                };
            }

            Log::warning("Mayar reconciliation payment fetch failed [{$gatewayId}]: ".$response->status());
        } catch (Exception $e) {
            Log::error("Mayar reconciliation network error [{$gatewayId}]: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Reconcile a single subscription against gateway and business rules.
     *
     * @return array{status: string, action: string, message: string}
     */
    public function reconcile(Subscription $subscription): array
    {
        return DB::transaction(function () use ($subscription) {
            /** @var Subscription $locked */
            $locked = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            if (! $locked) {
                return [
                    'status' => 'error',
                    'action' => 'not_found',
                    'message' => 'Subscription record not found',
                ];
            }

            $now = Carbon::now('Asia/Jakarta');

            // CASE 1: Pending Payment
            if ($locked->status === Subscription::STATUS_PENDING_PAYMENT) {
                $gatewayStatus = $this->fetchGatewayStatus($locked);

                // If gateway confirms PAID (webhook was missed or delayed)
                if ($gatewayStatus === 'PAID') {
                    $this->stateMachine->transition(
                        $locked,
                        Subscription::STATUS_ACTIVE,
                        'invoice.paid',
                        [
                            'billing_cycle' => $locked->billing_cycle,
                            'gateway_subscription_id' => $locked->gateway_subscription_id,
                        ]
                    );

                    SubscriptionEvent::create([
                        'subscription_id' => $locked->id,
                        'vendor_id' => $locked->vendor_id,
                        'event_type' => 'reconciliation.paid',
                        'gateway_event_id' => 'rec_paid_'.$locked->id.'_'.time(),
                        'payload_hash' => hash('sha256', 'reconciliation_'.$locked->gateway_subscription_id),
                        'amount_idr' => Subscription::getPlanPrice($locked->billing_cycle),
                        'status' => SubscriptionEvent::STATUS_PROCESSED,
                        'processed_at' => $now,
                    ]);

                    $msg = "Subscription [{$locked->ulid}] disinkronkan ke ACTIVE lewat rekonsiliasi gateway.";
                    Log::info($msg);
                    $this->errorMonitoring->captureMessage($msg, [
                        'subscription_id' => $locked->id,
                        'vendor_id' => $locked->vendor_id,
                    ]);

                    return [
                        'status' => 'synced',
                        'action' => 'activated',
                        'message' => $msg,
                    ];
                }

                // If gateway confirms EXPIRED or local timeout exceeded (24 hours)
                $createdAt = Carbon::parse($locked->created_at)->timezone('Asia/Jakarta');
                $isTimedOut = $createdAt->diffInHours($now) >= 24;

                if ($gatewayStatus === 'EXPIRED' || $isTimedOut) {
                    $this->stateMachine->transition(
                        $locked,
                        Subscription::STATUS_EXPIRED,
                        $gatewayStatus === 'EXPIRED' ? 'invoice.expired' : 'timeout'
                    );

                    $msg = "Subscription [{$locked->ulid}] kedaluwarsa via rekonsiliasi.";
                    Log::info($msg);

                    return [
                        'status' => 'synced',
                        'action' => 'expired',
                        'message' => $msg,
                    ];
                }

                // Alert if pending > 30 minutes without webhook notification (SEC-25 / billing.md)
                if ($createdAt->diffInMinutes($now) >= 30) {
                    $warnMsg = "Order pending_payment [{$locked->ulid}] menggantung > 30 menit.";
                    Log::warning($warnMsg, ['subscription_id' => $locked->id]);
                    $this->errorMonitoring->captureMessage($warnMsg, [
                        'subscription_id' => $locked->id,
                        'vendor_id' => $locked->vendor_id,
                        'created_at' => $locked->created_at,
                    ]);
                }

                return [
                    'status' => 'unchanged',
                    'action' => 'pending',
                    'message' => 'Subscription still pending payment',
                ];
            }

            // CASE 2: Active but current_period_end has passed
            if ($locked->status === Subscription::STATUS_ACTIVE && $locked->current_period_end) {
                $periodEnd = Carbon::parse($locked->current_period_end)->timezone('Asia/Jakarta');

                if ($now->greaterThan($periodEnd)) {
                    // Check if gateway renewed
                    $gatewayStatus = $this->fetchGatewayStatus($locked);

                    if ($gatewayStatus === 'PAID') {
                        // Extended
                        $this->stateMachine->transition($locked, Subscription::STATUS_ACTIVE, 'invoice.paid');

                        return [
                            'status' => 'synced',
                            'action' => 'renewed',
                            'message' => "Subscription [{$locked->ulid}] renewed via gateway status.",
                        ];
                    }

                    // Move to past_due
                    $this->stateMachine->transition($locked, Subscription::STATUS_PAST_DUE, 'payment_failed');
                    $msg = "Subscription [{$locked->ulid}] melewati periode aktif, dipindahkan ke PAST_DUE.";
                    Log::warning($msg);
                    $this->errorMonitoring->captureMessage($msg, ['subscription_id' => $locked->id]);

                    return [
                        'status' => 'synced',
                        'action' => 'past_due',
                        'message' => $msg,
                    ];
                }
            }

            // CASE 3: Past Due with Grace Period Expired (3 days per config)
            if ($locked->status === Subscription::STATUS_PAST_DUE && $locked->current_period_end) {
                $graceDays = (int) config('plans.grace_period_days', 3);
                $gracePeriodEnd = Carbon::parse($locked->current_period_end)->timezone('Asia/Jakarta')->addDays($graceDays);

                if ($now->greaterThan($gracePeriodEnd)) {
                    $this->stateMachine->transition($locked, Subscription::STATUS_CANCELLED, 'grace_period_expired');
                    $this->stateMachine->transition($locked, Subscription::PLAN_FREE, 'downgrade');

                    $msg = "Subscription [{$locked->ulid}] melewati masa tenggang {$graceDays} hari, di-downgrade ke FREE.";
                    Log::warning($msg);
                    $this->errorMonitoring->captureMessage($msg, ['subscription_id' => $locked->id]);

                    return [
                        'status' => 'synced',
                        'action' => 'downgraded',
                        'message' => $msg,
                    ];
                }
            }

            return [
                'status' => 'unchanged',
                'action' => 'none',
                'message' => 'No reconciliation action required',
            ];
        });
    }

    /**
     * Run reconciliation on all subscriptions requiring attention.
     *
     * @return array{total: int, synced: int, unchanged: int, errors: int}
     */
    public function reconcileAll(): array
    {
        $now = Carbon::now('Asia/Jakarta');

        $subscriptions = Subscription::where('status', Subscription::STATUS_PENDING_PAYMENT)
            ->orWhere(function ($query) use ($now) {
                $query->where('status', Subscription::STATUS_ACTIVE)
                    ->whereNotNull('current_period_end')
                    ->where('current_period_end', '<', $now);
            })
            ->orWhere('status', Subscription::STATUS_PAST_DUE)
            ->get();

        $stats = [
            'total' => $subscriptions->count(),
            'synced' => 0,
            'unchanged' => 0,
            'errors' => 0,
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->reconcile($subscription);

                if ($result['status'] === 'synced') {
                    $stats['synced']++;
                } elseif ($result['status'] === 'unchanged') {
                    $stats['unchanged']++;
                } else {
                    $stats['errors']++;
                }
            } catch (Exception $e) {
                $stats['errors']++;
                Log::error("Subscription reconciliation failed for ID [{$subscription->id}]: ".$e->getMessage());
                $this->errorMonitoring->captureException($e, [
                    'subscription_id' => $subscription->id,
                ]);
            }
        }

        AuditService::log(
            event: 'subscription_reconciliation_completed',
            entityType: 'subscription',
            entityId: 'batch',
            payload: $stats
        );

        Log::info('Subscription reconciliation completed', $stats);

        return $stats;
    }
}
