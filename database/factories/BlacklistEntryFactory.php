<?php

namespace Database\Factories;

use App\Models\BlacklistEntry;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlacklistEntry>
 */
class BlacklistEntryFactory extends Factory
{
    protected $model = BlacklistEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'identity_type' => fake()->randomElement([
                BlacklistEntry::TYPE_WA,
                BlacklistEntry::TYPE_NIK,
                BlacklistEntry::TYPE_INSTAGRAM,
            ]),
            'identity_hash' => hash_hmac('sha256', fake()->unique()->numerify('08##########'), 'secret'),
            'scope' => BlacklistEntry::SCOPE_PRIVATE,
            'reason' => fake()->sentence(),
            'evidence' => null,
            'expires_at' => null,
            'consent_basis' => null,
        ];
    }

    /**
     * Indicate that the entry is for WhatsApp.
     */
    public function wa(): static
    {
        return $this->state(fn (array $attributes) => [
            'identity_type' => BlacklistEntry::TYPE_WA,
        ]);
    }

    /**
     * Indicate that the entry is for NIK.
     */
    public function nik(): static
    {
        return $this->state(fn (array $attributes) => [
            'identity_type' => BlacklistEntry::TYPE_NIK,
        ]);
    }

    /**
     * Indicate that the entry is for Instagram.
     */
    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'identity_type' => BlacklistEntry::TYPE_INSTAGRAM,
        ]);
    }

    /**
     * Indicate that the entry has shared scope (Fase 2).
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => BlacklistEntry::SCOPE_SHARED,
            'evidence' => 'Riwayat kerusakan kostum berulang pada booking terdahulu.',
            'expires_at' => now('Asia/Jakarta')->addYear(),
            'consent_basis' => 'legitimate_interest',
        ]);
    }
}
