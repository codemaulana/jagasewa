<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorUser>
 */
class VendorUserFactory extends Factory
{
    protected $model = VendorUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'owner',
            'last_login_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user has staff role.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'staff',
        ]);
    }
}
