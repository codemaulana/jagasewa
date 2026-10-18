<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now('Asia/Jakarta')->addDays(fake()->numberBetween(1, 10));
        $totalDays = fake()->numberBetween(1, 4);
        $endDate = (clone $startDate)->addDays($totalDays - 1);
        $pricePerDay = 150000;
        $totalPrice = $pricePerDay * $totalDays;
        $depositPaid = 100000;

        return [
            'vendor_id' => Vendor::factory(),
            'item_id' => function (array $attributes) {
                return Item::factory()->create([
                    'vendor_id' => $attributes['vendor_id'] ?? Vendor::factory(),
                ])->id;
            },
            'renter_id' => Renter::factory(),
            'reference_code' => 'JS-'.Carbon::now('Asia/Jakarta')->format('Ymd').'-'.strtoupper(Str::random(4)).fake()->unique()->numberBetween(10, 99),
            'idempotency_key' => (string) Str::uuid(),
            'lock_version' => 1,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => $totalDays,
            'total_price' => $totalPrice,
            'deposit_paid' => $depositPaid,
            'fine_amount' => 0,
            'status' => Booking::STATUS_PENDING,
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->addHours(24),
            'vendor_notes' => null,
            'renter_notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the booking is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_APPROVED,
            'approved_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    /**
     * Indicate that the booking is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_ACTIVE,
            'approved_at' => Carbon::now('Asia/Jakarta')->subDays(2),
            'activated_at' => Carbon::now('Asia/Jakarta')->subDay(),
        ]);
    }

    /**
     * Indicate that the booking is returned.
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_RETURNED,
            'approved_at' => Carbon::now('Asia/Jakarta')->subDays(4),
            'activated_at' => Carbon::now('Asia/Jakarta')->subDays(3),
            'returned_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    /**
     * Indicate that the booking is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_CLOSED,
            'approved_at' => Carbon::now('Asia/Jakarta')->subDays(5),
            'activated_at' => Carbon::now('Asia/Jakarta')->subDays(4),
            'returned_at' => Carbon::now('Asia/Jakarta')->subDay(),
            'closed_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }
}
