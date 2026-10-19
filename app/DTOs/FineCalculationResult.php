<?php

namespace App\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;

class FineCalculationResult implements Arrayable
{
    public function __construct(
        public readonly CarbonInterface $endDate,
        public readonly CarbonInterface $actualReturnDate,
        public readonly int $lateDays,
        public readonly int $finePerDay,
        public readonly int $lateFine,
        public readonly int $damageDeduction,
        public readonly int $totalDeduction,
        public readonly int $deposit,
        public readonly int $depositRemaining,
        public readonly int $refund,
        public readonly int $shortfall,
        public readonly bool $isLate,
        public readonly bool $hasDamage,
        public readonly bool $hasRefund,
        public readonly bool $hasShortfall,
    ) {}

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'end_date' => $this->endDate->toDateString(),
            'actual_return_date' => $this->actualReturnDate->toDateString(),
            'late_days' => $this->lateDays,
            'fine_per_day' => $this->finePerDay,
            'late_fine' => $this->lateFine,
            'damage_deduction' => $this->damageDeduction,
            'total_deduction' => $this->totalDeduction,
            'deposit' => $this->deposit,
            'deposit_remaining' => $this->depositRemaining,
            'refund' => $this->refund,
            'shortfall' => $this->shortfall,
            'is_late' => $this->isLate,
            'has_damage' => $this->hasDamage,
            'has_refund' => $this->hasRefund,
            'has_shortfall' => $this->hasShortfall,
        ];
    }

    /**
     * Convert calculation result to database attributes for Settlement model.
     *
     * @param  array<string, mixed>  $additional
     * @return array<string, mixed>
     */
    public function toSettlementAttributes(array $additional = []): array
    {
        return array_merge([
            'actual_return_date' => $this->actualReturnDate->toDateString(),
            'late_days' => $this->lateDays,
            'late_fine' => $this->lateFine,
            'damage_deduction' => $this->damageDeduction,
            'total_deduction' => $this->totalDeduction,
            'deposit_remaining' => $this->depositRemaining,
        ], $additional);
    }
}
