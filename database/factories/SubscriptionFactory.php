<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'plan' => Subscription::PLAN_FREE,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => null, // Free has indefinite validity
            'gateway_subscription_id' => null,
            'gateway_customer_id' => null,
        ];
    }

    /**
     * Indicate that the subscription is a Pro subscription.
     */
    public function pro(string $cycle = Subscription::CYCLE_MONTHLY): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => $cycle,
            'current_period_start' => now(),
            'current_period_end' => match ($cycle) {
                Subscription::CYCLE_QUARTERLY => now()->addMonths(3),
                Subscription::CYCLE_ANNUALLY => now()->addYear(),
                default => now()->addMonth(),
            },
        ]);
    }

    /**
     * Indicate that the subscription is past due.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_PAST_DUE,
            'current_period_end' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the subscription has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_EXPIRED,
            'current_period_end' => now()->subDays(8),
        ]);
    }
}
