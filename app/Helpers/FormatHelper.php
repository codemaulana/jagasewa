<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

class FormatHelper
{
    /**
     * Format integer rupiah. Example: Rp 1.470.000
     */
    public static function rupiah(int|float|null $amount): string
    {
        $amount = (int) round($amount ?? 0);

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Format duration in days as pure integer. Example: "32 hari"
     */
    public static function days(int|float|null $days): string
    {
        $days = max(0, (int) round($days ?? 0));

        return "{$days} hari";
    }

    /**
     * Calculate whole days between two dates inclusive.
     * Prevents the float 31.999999999988 bug caused by endOfDay timestamps.
     */
    public static function calculatePeriodDays(string|DateTimeInterface|null $start, string|DateTimeInterface|null $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        $startDate = Carbon::parse($start, 'Asia/Jakarta')->startOfDay();
        $endDate = Carbon::parse($end, 'Asia/Jakarta')->startOfDay();

        return max(1, (int) round($startDate->diffInDays($endDate)) + 1);
    }

    /**
     * Format date in Indonesian locale. Example: "22 Okt 2026" or "Oktober 2026"
     */
    public static function date(string|DateTimeInterface|null $date, string $format = 'd M Y'): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat($format);
    }
}
