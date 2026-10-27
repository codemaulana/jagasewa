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
     * Uses integer rupiah arithmetic.
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

        $lateDays = $actualReturn->greaterThan($end)
            ? (int) $end->diffInDays($actualReturn)
            : 0;

        $lateFine = $lateDays * $safeFinePerDay;
        $totalDeduction = $lateFine + $safeDamage;
        $depositRemaining = $safeDeposit - $totalDeduction;
        $shortfall = max(0, -$depositRemaining);
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
     * Calculate settlement supporting both daily and hourly rates with optional grace period.
     */
    public function calculateAdvanced(
        CarbonInterface|string $endDate,
        CarbonInterface|string $actualReturnDate,
        int $fineRate,
        int $deposit,
        int $damageDeduction = 0,
        int $lossDeduction = 0,
        string $rateType = 'daily',
        int $graceHours = 0
    ): FineCalculationResult {
        $end = Carbon::parse($endDate, self::TIMEZONE);
        $actualReturn = Carbon::parse($actualReturnDate, self::TIMEZONE);

        $safeDeposit = max(0, $deposit);
        $safeFineRate = max(0, $fineRate);
        $totalDamageAndLoss = max(0, $damageDeduction) + max(0, $lossDeduction);

        if ($rateType === 'hourly') {
            // Apply grace period hours
            $endWithGrace = $end->copy()->addHours($graceHours);
            $lateHours = $actualReturn->greaterThan($endWithGrace)
                ? (int) ceil($endWithGrace->diffInMinutes($actualReturn) / 60)
                : 0;
            $lateFine = $lateHours * $safeFineRate;
            $lateDays = (int) ceil($lateHours / 24);
        } else {
            $endDay = $end->copy()->startOfDay();
            $actualDay = $actualReturn->copy()->startOfDay();
            $lateDays = $actualDay->greaterThan($endDay)
                ? (int) $endDay->diffInDays($actualDay)
                : 0;
            $lateFine = $lateDays * $safeFineRate;
        }

        $totalDeduction = $lateFine + $totalDamageAndLoss;
        $depositRemaining = $safeDeposit - $totalDeduction;
        $shortfall = max(0, -$depositRemaining);
        $refund = max(0, $depositRemaining);

        return new FineCalculationResult(
            endDate: $end,
            actualReturnDate: $actualReturn,
            lateDays: $lateDays,
            finePerDay: $safeFineRate,
            lateFine: $lateFine,
            damageDeduction: $totalDamageAndLoss,
            totalDeduction: $totalDeduction,
            deposit: $safeDeposit,
            depositRemaining: $depositRemaining,
            refund: $refund,
            shortfall: $shortfall,
            isLate: $lateFine > 0,
            hasDamage: $totalDamageAndLoss > 0,
            hasRefund: $refund > 0,
            hasShortfall: $shortfall > 0,
        );
    }

    /**
     * Calculate settlement automatically applying vendor-specific fine rules for Pro vendors.
     */
    public function calculateForBookingWithVendorRules(
        Booking $booking,
        CarbonInterface|string $actualReturnDate,
        int|iterable $damage = 0,
        int|iterable $loss = 0
    ): FineCalculationResult {
        $booking->loadMissing(['item', 'vendor']);
        $vendor = $booking->vendor;

        $damageCost = is_iterable($damage) ? $this->calculateTotalDamage($damage) : (int) $damage;
        $lossCost = is_iterable($loss) ? $this->calculateTotalDamage($loss) : (int) $loss;

        $deposit = (int) ($booking->deposit_paid ?? $booking->item?->deposit_amount ?? 0);
        $fineRate = (int) ($booking->item?->fine_per_day ?? 0);

        // Pro vendors can have custom automated fine rules
        $isPro = $vendor && $vendor->isPro();
        $rules = $vendor?->fine_rules ?? [];

        if ($isPro && ! empty($rules)) {
            $rateType = $rules['late_rate_type'] ?? 'daily';
            $graceHours = (int) ($rules['grace_hours'] ?? 0);
            if ($rateType === 'hourly' && ! empty($rules['hourly_rate'])) {
                $fineRate = (int) $rules['hourly_rate'];
            }

            return $this->calculateAdvanced(
                endDate: $booking->end_date,
                actualReturnDate: $actualReturnDate,
                fineRate: $fineRate,
                deposit: $deposit,
                damageDeduction: $damageCost,
                lossDeduction: $lossCost,
                rateType: $rateType,
                graceHours: $graceHours
            );
        }

        return $this->calculate(
            endDate: $booking->end_date,
            actualReturnDate: $actualReturnDate,
            finePerDay: $fineRate,
            deposit: $deposit,
            damageDeduction: $damageCost + $lossCost
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
