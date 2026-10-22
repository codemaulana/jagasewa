<?php

namespace Tests\Unit;

use App\Helpers\FormatHelper;
use PHPUnit\Framework\TestCase;

class FormatHelperTest extends TestCase
{
    /**
     * Test Rupiah formatting.
     */
    public function test_rupiah_format(): void
    {
        $this->assertEquals('Rp 1.470.000', FormatHelper::rupiah(1470000));
        $this->assertEquals('Rp 0', FormatHelper::rupiah(0));
        $this->assertEquals('Rp 50.000', FormatHelper::rupiah(50000.4));
    }

    /**
     * Test days integer formatting and float rounding.
     */
    public function test_days_format_fixes_float_precision_bug(): void
    {
        // Case: Carbon diffInDays with endOfDay resulted in 31.999999999988
        $floatDays = 31.999999999988;
        $this->assertEquals('32 hari', FormatHelper::days($floatDays));
        $this->assertEquals('1 hari', FormatHelper::days(0.999999));
        $this->assertEquals('0 hari', FormatHelper::days(0));
    }

    /**
     * Test calculate period days inclusive between two timestamps.
     */
    public function test_calculate_period_days_inclusive(): void
    {
        // 1 Oct to 31 Oct is exactly 31 days
        $days = FormatHelper::calculatePeriodDays('2026-10-01 00:00:00', '2026-10-31 23:59:59');
        $this->assertEquals(31, $days);

        // Same day is 1 day
        $sameDay = FormatHelper::calculatePeriodDays('2026-10-22', '2026-10-22');
        $this->assertEquals(1, $sameDay);
    }

    /**
     * Test date formatting in Indonesian locale.
     */
    public function test_date_indonesian_format(): void
    {
        $formatted = FormatHelper::date('2026-10-22', 'd M Y');
        $this->assertEquals('22 Okt 2026', $formatted);

        $monthYear = FormatHelper::date('2026-10-01', 'F Y');
        $this->assertEquals('Oktober 2026', $monthYear);
    }
}
