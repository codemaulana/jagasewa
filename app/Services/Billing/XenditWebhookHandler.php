<?php

namespace App\Services\Billing;

use App\Exceptions\InvalidSubscriptionTransitionException;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Services\AuditService;
use App\Services\ErrorMonitoringService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XenditWebhookHandler
{
    public function __construct(
        protected SubscriptionStateMachine $stateMachine,
        protected ErrorMonitoringService $errorMonitoring
    ) {}

    /**
     * Verify Xendit callback token from request header.
     */
    public function verifyCallbackToken(?string $providedToken): bool
    {
        $expectedToken = config('services.xendit.callback_token');

        if (empty($expectedToken) || empty($providedToken)) {
            return false;
        }

        return hash_equals($expectedToken, $providedToken);
    }

    /**
     * Process Xendit invoice webhook payload according to docs/billing.md Section 6.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string, code: int}
     */
    public function handle(array $payload): array
    {
        $gatewayEventId = (string) ($payload['id'] ?? '');
        $externalId = (string) ($payload['external_id'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $amount = (int) ($payload['amount'] ?? 0);
        $currency = strtoupper((string) ($payload['currency'] ?? 'IDR'));

        if (empty($gatewayEventId) || empty($externalId)) {
            return [
                'status' => 'error',
                'message' => 'Missing required webhook attributes: id or external_id',
                'code' => 422,
            ];
        }

        // Step 3: Cek idempotency (BLT-01, BLT-05)
        if (SubscriptionEvent::where('gateway_event_id', $gatewayEventId)->exists()) {
            Log::info("Xendit webhook replay detected for event [{$gatewayEventId}]. Skipping duplicate execution.");

            return [
                'status' => 'ignored',
                'message' => 'Event already processed',
                'code' => 200,
            ];
        }

        // Step 4 & 5: Begin Transaction with Row Lock
        return DB::transaction(function () use ($payload, $gatewayEventId, $externalId, $status, $amount, $currency) {
            /** @var Subscription|null $subscription */
            $subscription = Subscription::where('ulid', $externalId)
                ->orWhere('id', is_numeric($externalId) ? (int) $externalId : 0)
                ->lockForUpdate()
                ->first();

            // BLT-03: Webhook out of order / order not found
            if (! $subscription) {
                $msg = "Subscription order not found for external_id: [{$externalId}]";
                Log::error($msg, ['payload' => $payload]);

                $this->errorMonitoring->captureException(new Exception($msg), [
                    'external_id' => $externalId,
                    'gateway_event_id' => $gatewayEventId,
                ]);

                return [
                    'status' => 'error',
                    'message' => $msg,
                    'code' => 422,
                ];
            }

            // Step 4: Validasi Bisnis (Currency & Nominal)
            if ($currency !== 'IDR') {
                $msg = "Invalid currency [{$currency}], expected IDR";
                Log::warning($msg, ['subscription_id' => $subscription->id]);

                return [
                    'status' => 'error',
                    'message' => $msg,
                    'code' => 422,
                ];
            }

            // Expected amount for plan (BLT-04, BLT-09)
            $expectedAmount = Subscription::getPlanPrice($subscription->billing_cycle);
            if ($status === 'PAID' && $amount < $expectedAmount) {
                $msg = "Payment amount [Rp {$amount}] does not match required plan price [Rp {$expectedAmount}]";
                Log::error($msg, [
                    'subscription_id' => $subscription->id,
                    'amount' => $amount,
                    'expected' => $expectedAmount,
                ]);

                $this->errorMonitoring->captureException(new Exception($msg), [
                    'subscription_id' => $subscription->id,
                    'amount' => $amount,
                    'expected' => $expectedAmount,
                ]);

                // Rollback & return error (status remains pending_payment)
                return [
                    'status' => 'error',
                    'message' => $msg,
                    'code' => 422,
                ];
            }

            // Determine state machine trigger & target status
            [$trigger, $targetStatus] = match ($status) {
                'PAID' => ['invoice.paid', Subscription::STATUS_ACTIVE],
                'EXPIRED' => ['invoice.expired', Subscription::STATUS_EXPIRED],
                'FAILED' => ['invoice.failed', Subscription::STATUS_PAST_DUE],
                default => ['unknown', $subscription->status],
            };

            // Step 6: Jalankan State Machine
            try {
                $this->stateMachine->transition($subscription, $targetStatus, $trigger, [
                    'billing_cycle' => $subscription->billing_cycle,
                    'gateway_subscription_id' => $payload['id'] ?? null,
                    'gateway_customer_id' => $payload['user_id'] ?? null,
                ]);
            } catch (InvalidSubscriptionTransitionException $e) {
                // BLT-07: Order expired -> invoice.paid rejected -> alert admin, return 200
                $msg = "Invalid state transition rejected: {$e->getMessage()}";
                Log::warning($msg, ['subscription_id' => $subscription->id]);

                $this->errorMonitoring->captureException($e, [
                    'subscription_id' => $subscription->id,
                    'current_status' => $subscription->status,
                    'target_status' => $targetStatus,
                ]);

                return [
                    'status' => 'rejected',
                    'message' => $msg,
                    'code' => 200,
                ];
            }

            // Step 7: Simpan Event (Audit & Idempotency record)
            SubscriptionEvent::create([
                'subscription_id' => $subscription->id,
                'vendor_id' => $subscription->vendor_id,
                'event_type' => $trigger,
                'gateway_event_id' => $gatewayEventId,
                'payload_hash' => hash('sha256', json_encode($payload)),
                'amount_idr' => $amount,
                'status' => SubscriptionEvent::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);

            AuditService::log(
                event: 'webhook_xendit_processed',
                entityType: 'subscription',
                entityId: (string) $subscription->id,
                payload: [
                    'gateway_event_id' => $gatewayEventId,
                    'event_type' => $trigger,
                    'status' => $status,
                    'amount_idr' => $amount,
                ],
                vendorId: $subscription->vendor_id
            );

            return [
                'status' => 'success',
                'message' => 'Webhook processed successfully',
                'code' => 200,
            ];
        });
    }
}
