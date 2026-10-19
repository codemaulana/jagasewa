<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Settlement;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lateDays = fake()->numberBetween(0, 2);
        $lateFine = $lateDays * 50000;
        $damageDeduction = fake()->randomElement([0, 25000, 50000]);
        $totalDeduction = $lateFine + $damageDeduction;
        $depositPaid = 100000;
        $depositRemaining = $depositPaid - $totalDeduction;

        return [
            'vendor_id' => Vendor::factory(),
            'booking_id' => Booking::factory(['status' => Booking::STATUS_RETURNED]),
            'actual_return_date' => Carbon::now('Asia/Jakarta')->format('Y-m-d'),
            'late_days' => $lateDays,
            'late_fine' => $lateFine,
            'damage_deduction' => $damageDeduction,
            'total_deduction' => $totalDeduction,
            'deposit_remaining' => $depositRemaining,
            'damage_notes' => $damageDeduction > 0 ? fake()->sentence() : null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Settlement $settlement) {
            if ($settlement->booking_id && $booking = Booking::find($settlement->booking_id)) {
                $settlement->vendor_id = $booking->vendor_id;
            }
        });
    }
}
