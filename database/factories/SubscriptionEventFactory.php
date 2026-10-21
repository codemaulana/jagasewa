<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionEvent>
 */
class SubscriptionEventFactory extends Factory
{
    protected $model = SubscriptionEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'vendor_id' => fn (array $attributes) => Subscription::find($attributes['subscription_id'])?->vendor_id ?? Vendor::factory(),
            'event_type' => 'invoice.paid',
            'gateway_event_id' => 'evt_'.Str::random(24),
            'payload_hash' => hash('sha256', Str::random(32)),
            'amount_idr' => 15000,
            'status' => SubscriptionEvent::STATUS_PROCESSED,
            'processed_at' => now(),
        ];
    }
}
