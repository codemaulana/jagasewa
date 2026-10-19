<?php

namespace Database\Factories;

use App\Models\ManualActivation;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualActivation>
 */
class ManualActivationFactory extends Factory
{
    protected $model = ManualActivation::class;

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
            'activated_by' => User::factory(),
            'amount_paid_idr' => 15000,
            'notes' => 'Transfer BCA konfirmasi via WA Admin',
            'activated_at' => now(),
        ];
    }
}
