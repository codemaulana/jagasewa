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

class MayarWebhookHandler
{
    public function __construct(
        protected SubscriptionStateMachine $stateMachine,
        protected ErrorMonitoringService $errorMonitoring
    ) {}

    /**
     * Verify Mayar webhook signature using HMAC-SHA256 or webhook secret token.
     */
    public function verifySignature(?string $signature, string $rawBody): bool
    {
        $secret = config('services.mayar.webhook_secret');

        if (empty($secret) || empty($signature)) {
            return false;
        }

        // 1. Direct secret token comparison
        if (hash_equals($secret, $signature)) {
            return true;
        }

        // 2. HMAC-SHA256 signature verification over raw request body
        $computed = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($computed, $signature);
    }

    /**
     * Process Mayar webhook payload according to billing state machine & business rules.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string, code: int}
     */
    public function handle(array $payload): array
    {
        $data = (array) ($payload['data'] ?? $payload);

        $event = (string) ($payload['event'] ?? $data['event'] ?? '');
        $gatewayEventId = (string) ($data['id'] ?? $data['payment_id'] ?? $payload['id'] ?? '');
        $externalId = (string) (
            $data['extra_data']['subscription_ulid']
            ?? $data['order_id']
            ?? $data['external_id']
            ?? $payload['external_id']
            ?? ''
        );
        $status = strtoupper((string) ($data['status'] ?? $data['payment_status'] ?? ''));
        $amount = (int) ($data['amount'] ?? $data['total_amount'] ?? $payload['amount'] ?? 0);
        $currency = strtoupper((string) ($data['currency'] ?? $payload['currency'] ?? 'IDR'));

        // Handle Mayar "Testing URL" ping from dashboard
        $isTestPing = empty($externalId)
            || str_contains(strtolower($event), 'test')
            || (isset($payload['type']) && str_contains(strtolower((string) $payload['type']), 'test'));

        if ($isTestPing && empty($externalId)) {
            Log::info('Mayar webhook test ping acknowledged successfully.', ['payload' => $payload]);

            return [
                'status' => 'success',
                'message' => 'Mayar webhook test ping acknowledged',
                'code' => 200,
            ];
        }

        if (empty($gatewayEventId) || empty($externalId)) {
            return [
                'status' => 'error',
                'message' => 'Missing required Mayar webhook attributes: event id or subscription external_id',
                'code' => 422,
            ];
        }

        // Step 3: Cek idempotency (BLT-01, BLT-05)
        if (SubscriptionEvent::where('gateway_event_id', $gatewayEventId)->exists()) {
            Log::info("Mayar webhook replay detected for event [{$gatewayEventId}]. Skipping duplicate execution.");

            return [
                'status' => 'ignored',
                'message' => 'Event already processed',
                'code' => 200,
            ];
        }

        // Step 4 & 5: Begin Transaction with Row Lock
        return DB::transaction(function () use ($payload, $data, $event, $gatewayEventId, $externalId, $status, $amount, $currency) {
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

            // Expected amount check (BLT-04, BLT-09)
            $expectedAmount = Subscription::getPlanPrice($subscription->billing_cycle);
            $isPaidEvent = $event === 'payment.received'
                || $event === 'invoice.paid'
                || in_array($status, ['SUCCESS', 'PAID', 'SETTLED'], true);

            if ($isPaidEvent && $amount < $expectedAmount) {
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

                return [
                    'status' => 'error',
                    'message' => $msg,
                    'code' => 422,
                ];
            }

            // Determine state machine trigger & target status
            if ($isPaidEvent) {
                $trigger = 'invoice.paid';
                $targetStatus = Subscription::STATUS_ACTIVE;
            } elseif ($event === 'invoice.expired' || in_array($status, ['EXPIRED'], true)) {
                $trigger = 'invoice.expired';
                $targetStatus = Subscription::STATUS_EXPIRED;
            } elseif ($event === 'payment.failed' || in_array($status, ['FAILED', 'DENIED'], true)) {
                $trigger = 'invoice.failed';
                $targetStatus = Subscription::STATUS_PAST_DUE;
            } else {
                $trigger = 'unknown';
                $targetStatus = $subscription->status;
            }

            // Step 6: Jalankan State Machine
            try {
                $this->stateMachine->transition($subscription, $targetStatus, $trigger, [
                    'billing_cycle' => $subscription->billing_cycle,
                    'gateway_subscription_id' => $data['id'] ?? $payload['id'] ?? null,
                    'gateway_customer_id' => $data['customer_id'] ?? $payload['customer_id'] ?? null,
                ]);
            } catch (InvalidSubscriptionTransitionException $e) {
                // BLT-07: Expired order rejecting late payment
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

            // Step 7: Simpan Event
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
                event: 'webhook_mayar_processed',
                entityType: 'subscription',
                entityId: (string) $subscription->id,
                payload: [
                    'gateway_event_id' => $gatewayEventId,
                    'event_type' => $trigger,
                    'status' => $status ?: $event,
                    'amount_idr' => $amount,
                ],
                vendorId: $subscription->vendor_id
            );

            return [
                'status' => 'success',
                'message' => 'Mayar webhook processed successfully',
                'code' => 200,
            ];
        });
    }
}
