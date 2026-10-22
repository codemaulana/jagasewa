<?php

use App\Helpers\FormatHelper;

if (! function_exists('format_rupiah')) {
    function format_rupiah(int|float|null $amount): string
    {
        return FormatHelper::rupiah($amount);
    }
}

if (! function_exists('format_days')) {
    function format_days(int|float|null $days): string
    {
        return FormatHelper::days($days);
    }
}

if (! function_exists('format_date_id')) {
    function format_date_id(string|DateTimeInterface|null $date, string $format = 'd M Y'): string
    {
        return FormatHelper::date($date, $format);
    }
}
