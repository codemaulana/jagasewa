<?php

namespace App\Services\Billing;

use App\Exceptions\InvalidSubscriptionTransitionException;
use App\Models\Subscription;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionStateMachine
{
    /**
     * Permitted state transitions according to docs/billing.md Section 3.
     * Format: [current_status => [target_status => [allowed_triggers]]]
     */
    protected const TRANSITIONS = [
        Subscription::STATUS_ACTIVE => [
            Subscription::STATUS_ACTIVE => ['invoice.paid'], // Renewal / extension
            Subscription::STATUS_PAST_DUE => ['payment_failed', 'invoice.failed'],
            Subscription::STATUS_CANCELLED => ['vendor_cancelled', 'subscription.cancelled'],
        ],
        Subscription::STATUS_PENDING_PAYMENT => [
            Subscription::STATUS_ACTIVE => ['invoice.paid'],
            Subscription::STATUS_EXPIRED => ['timeout', 'invoice.expired'],
            Subscription::STATUS_CANCELLED => ['vendor_cancelled'],
        ],
        Subscription::STATUS_PAST_DUE => [
            Subscription::STATUS_ACTIVE => ['invoice.paid'],
            Subscription::STATUS_CANCELLED => ['grace_period_expired'],
        ],
        Subscription::STATUS_TRIALING => [
            Subscription::STATUS_ACTIVE => ['invoice.paid'],
            Subscription::STATUS_PENDING_PAYMENT => ['trial_expired'],
        ],
        Subscription::STATUS_CANCELLED => [
            Subscription::PLAN_FREE => ['downgrade'],
            Subscription::STATUS_PENDING_PAYMENT => ['resubscribe'],
        ],
        Subscription::STATUS_EXPIRED => [
            Subscription::PLAN_FREE => ['downgrade'],
            Subscription::STATUS_PENDING_PAYMENT => ['resubscribe'],
        ],
    ];

    /**
     * Check whether a transition from status A to B is valid for the given trigger.
     */
    public function canTransition(string $from, string $to, string $trigger): bool
    {
        $allowedTriggers = self::TRANSITIONS[$from][$to] ?? [];

        return in_array($trigger, $allowedTriggers, true);
    }

    /**
     * Execute state machine transition on a subscription record.
     *
     * @throws InvalidSubscriptionTransitionException
     */
    public function transition(Subscription $subscription, string $to, string $trigger, array $context = []): Subscription
    {
        $from = $subscription->status;

        if (! $this->canTransition($from, $to, $trigger)) {
            Log::warning("Subscription state machine rejected invalid transition: [{$from}] -> [{$to}] on trigger [{$trigger}] for subscription ID [{$subscription->id}].");

            throw new InvalidSubscriptionTransitionException($from, $to, $trigger);
        }

        $subscription->status = $to;

        // If activating to Pro via payment (initial activation or seamless renewal)
        if ($to === Subscription::STATUS_ACTIVE && $trigger === 'invoice.paid') {
            $subscription->plan = Subscription::PLAN_PRO;

            $cycle = $context['billing_cycle'] ?? $subscription->billing_cycle ?? Subscription::CYCLE_MONTHLY;
            $subscription->billing_cycle = $cycle;

            // Per Section 2b: Perpanjangan harus menyambung dari tanggal berakhir, bukan dari tanggal bayar.
            $existingActive = Subscription::where('vendor_id', $subscription->vendor_id)
                ->where('plan', Subscription::PLAN_PRO)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->where('id', '!=', $subscription->id)
                ->where(function ($q) {
                    $q->whereNull('current_period_end')
                        ->orWhere('current_period_end', '>', now('Asia/Jakarta'));
                })
                ->latest('id')
                ->first();

            $existingEnd = $existingActive?->current_period_end;
            $baseDate = ($existingEnd && Carbon::parse($existingEnd)->isFuture())
                ? Carbon::parse($existingEnd)->timezone('Asia/Jakarta')
                : Carbon::now('Asia/Jakarta');

            if ($existingActive) {
                $subscription->current_period_start = $existingActive->current_period_start ?? Carbon::now('Asia/Jakarta');
                $existingActive->update(['status' => Subscription::STATUS_CANCELLED]);
            } elseif (! $subscription->current_period_start) {
                $subscription->current_period_start = Carbon::now('Asia/Jakarta');
            }

            $subscription->current_period_end = match ($cycle) {
                Subscription::CYCLE_QUARTERLY => $baseDate->copy()->addMonths(3),
                Subscription::CYCLE_ANNUALLY => $baseDate->copy()->addYear(),
                default => $baseDate->copy()->addMonth(),
            };

            if (! empty($context['gateway_subscription_id'])) {
                $subscription->gateway_subscription_id = $context['gateway_subscription_id'];
            }
            if (! empty($context['gateway_customer_id'])) {
                $subscription->gateway_customer_id = $context['gateway_customer_id'];
            }
        }

        // If downgrading to Free
        if ($to === Subscription::PLAN_FREE || ($to === Subscription::STATUS_ACTIVE && ($context['plan'] ?? null) === Subscription::PLAN_FREE)) {
            $subscription->plan = Subscription::PLAN_FREE;
            $subscription->status = Subscription::STATUS_ACTIVE;
            $subscription->current_period_end = null;

            if ($subscription->vendor) {
                app(EntitlementService::class)->applyDowngrade($subscription->vendor);
            }
        }

        $subscription->save();

        AuditService::log(
            event: 'subscription_status_changed',
            entityType: 'subscription',
            entityId: (string) $subscription->id,
            payload: [
                'from_status' => $from,
                'to_status' => $to,
                'trigger' => $trigger,
                'plan' => $subscription->plan,
            ],
            vendorId: $subscription->vendor_id
        );

        return $subscription;
    }
}
