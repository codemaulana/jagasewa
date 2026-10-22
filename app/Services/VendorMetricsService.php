<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vendor;
use Carbon\Carbon;

class VendorMetricsService
{
    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * The canonical booking statuses considered valid for gross rental revenue.
     * Includes APPROVED (booking confirmed/DP paid), ACTIVE, RETURNED, and CLOSED.
     */
    public const VALID_REVENUE_STATUSES = [
        Booking::STATUS_APPROVED,
        Booking::STATUS_ACTIVE,
        Booking::STATUS_RETURNED,
        Booking::STATUS_CLOSED,
    ];

    /**
     * Get gross revenue for the current calendar month.
     */
    public function getMonthlyRevenue(Vendor $vendor, ?string $monthString = null): int
    {
        $date = $monthString ? Carbon::parse($monthString, self::TIMEZONE) : now(self::TIMEZONE);
        $startOfMonth = $date->copy()->startOfMonth()->toDateString();
        $endOfMonth = $date->copy()->endOfMonth()->toDateString();

        return $this->getRevenueForPeriod($vendor, $startOfMonth, $endOfMonth);
    }

    /**
     * Calculate gross revenue between two dates inclusive based on booking start_date.
     */
    public function getRevenueForPeriod(Vendor $vendor, string $startDate, string $endDate): int
    {
        return (int) Booking::where('vendor_id', $vendor->id)
            ->whereIn('status', self::VALID_REVENUE_STATUSES)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->sum('total_price');
    }

    /**
     * Get operational tasks for today: pickups, returns, and overdue.
     */
    public function getTodayTasks(Vendor $vendor): array
    {
        $today = now(self::TIMEZONE)->toDateString();

        $pickups = Booking::where('vendor_id', $vendor->id)
            ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_PENDING])
            ->whereDate('start_date', $today)
            ->count();

        $returns = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', $today)
            ->count();

        $overdue = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', '<', $today)
            ->count();

        return [
            'pickups' => $pickups,
            'returns' => $returns,
            'overdue' => $overdue,
            'total' => $pickups + $returns + $overdue,
        ];
    }
}
