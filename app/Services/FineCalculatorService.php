<?php

namespace App\Services;

use App\DTOs\FineCalculationResult;
use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class FineCalculatorService
{
    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * Calculate late days, fines, damage deductions, and final deposit settlement.
     * Implements business rule BR-03 with integer rupiah arithmetic.
     *
     * @param  CarbonInterface|string  $endDate  Agreed return date
     * @param  CarbonInterface|string  $actualReturnDate  Actual return date
     * @param  int  $finePerDay  Fine rate per late day in integer IDR
     * @param  int  $deposit  Original deposit paid in integer IDR
     * @param  int  $damageDeduction  Total repair/damage cost in integer IDR
     */
    public function calculate(
        CarbonInterface|string $endDate,
        CarbonInterface|string $actualReturnDate,
        int $finePerDay,
        int $deposit,
        int $damageDeduction = 0
    ): FineCalculationResult {
        $end = Carbon::parse($endDate, self::TIMEZONE)->startOfDay();
        $actualReturn = Carbon::parse($actualReturnDate, self::TIMEZONE)->startOfDay();

        $safeFinePerDay = max(0, $finePerDay);
        $safeDeposit = max(0, $deposit);
        $safeDamage = max(0, $damageDeduction);

        // BR-03: late_days = max(0, return_date - end_date)
        $lateDays = $actualReturn->greaterThan($end)
            ? (int) $end->diffInDays($actualReturn)
            : 0;

        // BR-03: late_fine = late_days × fine_per_day
        $lateFine = $lateDays * $safeFinePerDay;

        // BR-03: total_deduction = late_fine + damage_deduction
        $totalDeduction = $lateFine + $safeDamage;

        // BR-03: deposit_remaining = deposit - total_deduction
        $depositRemaining = $safeDeposit - $totalDeduction;

        // BR-03: shortfall = max(0, -deposit_remaining)
        $shortfall = max(0, -$depositRemaining);

        // BR-03: refund = max(0, deposit_remaining)
        $refund = max(0, $depositRemaining);

        return new FineCalculationResult(
            endDate: $end,
            actualReturnDate: $actualReturn,
            lateDays: $lateDays,
            finePerDay: $safeFinePerDay,
            lateFine: $lateFine,
            damageDeduction: $safeDamage,
            totalDeduction: $totalDeduction,
            deposit: $safeDeposit,
            depositRemaining: $depositRemaining,
            refund: $refund,
            shortfall: $shortfall,
            isLate: $lateDays > 0,
            hasDamage: $safeDamage > 0,
            hasRefund: $refund > 0,
            hasShortfall: $shortfall > 0,
        );
    }

    /**
     * Calculate settlement directly from a Booking model instance.
     *
     * @param  Booking  $booking  The active/returned booking
     * @param  CarbonInterface|string  $actualReturnDate  Actual return date
     * @param  int|iterable<mixed>  $damage  Damage amount in IDR or collection/array of damage items
     */
    public function calculateForBooking(
        Booking $booking,
        CarbonInterface|string $actualReturnDate,
        int|iterable $damage = 0
    ): FineCalculationResult {
        $booking->loadMissing('item');

        $damageDeduction = is_iterable($damage)
            ? $this->calculateTotalDamage($damage)
            : (int) $damage;

        $deposit = (int) ($booking->deposit_paid ?? $booking->item?->deposit_amount ?? 0);
        $finePerDay = (int) ($booking->item?->fine_per_day ?? 0);

        return $this->calculate(
            endDate: $booking->end_date,
            actualReturnDate: $actualReturnDate,
            finePerDay: $finePerDay,
            deposit: $deposit,
            damageDeduction: $damageDeduction,
        );
    }

    /**
     * Calculate sum of damage items from an iterable collection/array.
     *
     * @param  iterable<mixed>  $damageItems
     */
    public function calculateTotalDamage(iterable $damageItems): int
    {
        $total = 0;

        foreach ($damageItems as $item) {
            if (is_array($item) || $item instanceof \ArrayAccess) {
                $total += max(0, (int) ($item['amount'] ?? $item['price'] ?? $item['cost'] ?? 0));
            } elseif (is_object($item)) {
                $total += max(0, (int) ($item->amount ?? $item->price ?? $item->cost ?? 0));
            } elseif (is_numeric($item)) {
                $total += max(0, (int) $item);
            }
        }

        return $total;
    }
}
