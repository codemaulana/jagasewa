<?php

namespace Tests\Unit;

use App\DTOs\FineCalculationResult;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Services\FineCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FineCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private FineCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FineCalculatorService;
    }

    /**
     * TC-11 / DT-01: Tepat waktu, tanpa kerusakan -> Refund penuh.
     */
    public function test_dt01_on_time_without_damage_returns_full_refund(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-12',
            finePerDay: 50000,
            deposit: 200000,
            damageDeduction: 0
        );

        $this->assertInstanceOf(FineCalculationResult::class, $result);
        $this->assertSame(0, $result->lateDays);
        $this->assertSame(0, $result->lateFine);
        $this->assertSame(0, $result->damageDeduction);
        $this->assertSame(0, $result->totalDeduction);
        $this->assertSame(200000, $result->depositRemaining);
        $this->assertSame(200000, $result->refund);
        $this->assertSame(0, $result->shortfall);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->hasDamage);
        $this->assertTrue($result->hasRefund);
        $this->assertFalse($result->hasShortfall);
    }

    /**
     * TC-12 / DT-02: Terlambat 2 hari, tanpa kerusakan -> Refund berkurang.
     */
    public function test_dt02_two_days_late_without_damage_deducts_late_fine(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-14',
            finePerDay: 50000,
            deposit: 200000,
            damageDeduction: 0
        );

        $this->assertSame(2, $result->lateDays);
        $this->assertSame(100000, $result->lateFine);
        $this->assertSame(0, $result->damageDeduction);
        $this->assertSame(100000, $result->totalDeduction);
        $this->assertSame(100000, $result->depositRemaining);
        $this->assertSame(100000, $result->refund);
        $this->assertSame(0, $result->shortfall);
        $this->assertTrue($result->isLate);
        $this->assertFalse($result->hasDamage);
        $this->assertTrue($result->hasRefund);
        $this->assertFalse($result->hasShortfall);
    }

    /**
     * TC-13 / DT-03: Tepat waktu, ada kerusakan -> Deposit dikurangi kerusakan.
     */
    public function test_dt03_on_time_with_damage_deducts_damage_from_deposit(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-12',
            finePerDay: 50000,
            deposit: 200000,
            damageDeduction: 150000
        );

        $this->assertSame(0, $result->lateDays);
        $this->assertSame(0, $result->lateFine);
        $this->assertSame(150000, $result->damageDeduction);
        $this->assertSame(150000, $result->totalDeduction);
        $this->assertSame(50000, $result->depositRemaining);
        $this->assertSame(50000, $result->refund);
        $this->assertSame(0, $result->shortfall);
        $this->assertFalse($result->isLate);
        $this->assertTrue($result->hasDamage);
        $this->assertTrue($result->hasRefund);
        $this->assertFalse($result->hasShortfall);
    }

    /**
     * TC-14 / DT-04: Terlambat 3 hari + kerusakan melebihi deposit -> Shortfall (kekurangan bayar).
     */
    public function test_dt04_late_plus_damage_exceeding_deposit_results_in_shortfall(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-15',
            finePerDay: 75000,
            deposit: 200000,
            damageDeduction: 100000
        );

        $this->assertSame(3, $result->lateDays);
        $this->assertSame(225000, $result->lateFine);
        $this->assertSame(100000, $result->damageDeduction);
        $this->assertSame(325000, $result->totalDeduction);
        $this->assertSame(-125000, $result->depositRemaining);
        $this->assertSame(0, $result->refund);
        $this->assertSame(125000, $result->shortfall);
        $this->assertTrue($result->isLate);
        $this->assertTrue($result->hasDamage);
        $this->assertFalse($result->hasRefund);
        $this->assertTrue($result->hasShortfall);
    }

    /**
     * TC-15 / DT-05: Dikembalikan sebelum jatuh tempo -> late_days = 0, refund penuh.
     */
    public function test_dt05_returned_early_before_end_date_has_zero_late_days(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-10',
            finePerDay: 50000,
            deposit: 200000,
            damageDeduction: 0
        );

        $this->assertSame(0, $result->lateDays);
        $this->assertSame(0, $result->lateFine);
        $this->assertSame(0, $result->damageDeduction);
        $this->assertSame(0, $result->totalDeduction);
        $this->assertSame(200000, $result->depositRemaining);
        $this->assertSame(200000, $result->refund);
        $this->assertSame(0, $result->shortfall);
        $this->assertFalse($result->isLate);
    }

    /**
     * Test exact calculation from a Booking model instance.
     */
    public function test_calculate_for_booking_uses_booking_deposit_and_item_fine_rate(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'deposit_amount' => 150000,
            'fine_per_day' => 40000,
        ]);
        $renter = Renter::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-03',
            'deposit_paid' => 150000,
        ]);

        $result = $this->calculator->calculateForBooking(
            booking: $booking,
            actualReturnDate: '2026-05-05',
            damage: 20000
        );

        $this->assertSame(2, $result->lateDays);
        $this->assertSame(80000, $result->lateFine);
        $this->assertSame(20000, $result->damageDeduction);
        $this->assertSame(100000, $result->totalDeduction);
        $this->assertSame(50000, $result->depositRemaining);
        $this->assertSame(50000, $result->refund);
        $this->assertSame(0, $result->shortfall);
    }

    /**
     * Test calculateTotalDamage with various collection formats.
     */
    public function test_calculate_total_damage_aggregates_items_correctly(): void
    {
        $arrayItems = [
            ['amount' => 35000],
            ['cost' => 15000],
            ['price' => 10000],
        ];

        $total = $this->calculator->calculateTotalDamage($arrayItems);
        $this->assertSame(60000, $total);

        $objectItems = [
            (object) ['amount' => 25000],
            (object) ['cost' => 25000],
        ];

        $totalObj = $this->calculator->calculateTotalDamage($objectItems);
        $this->assertSame(50000, $totalObj);

        $numericList = [10000, 20000, 30000];
        $totalNum = $this->calculator->calculateTotalDamage($numericList);
        $this->assertSame(60000, $totalNum);
    }

    /**
     * Test calculateForBooking accepts an array of damage items.
     */
    public function test_calculate_for_booking_accepts_damage_items_array(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'fine_per_day' => 50000,
        ]);
        $renter = Renter::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'end_date' => '2026-06-10',
            'deposit_paid' => 100000,
        ]);

        $damageItems = [
            ['description' => 'Kancing lepas', 'amount' => 20000],
            ['description' => 'Noda minyak', 'amount' => 30000],
        ];

        $result = $this->calculator->calculateForBooking(
            booking: $booking,
            actualReturnDate: '2026-06-10',
            damage: $damageItems
        );

        $this->assertSame(0, $result->lateDays);
        $this->assertSame(50000, $result->damageDeduction);
        $this->assertSame(50000, $result->depositRemaining);
        $this->assertSame(50000, $result->refund);
    }

    /**
     * Test toArray and toSettlementAttributes formatting.
     */
    public function test_conversion_methods_return_valid_structures(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-14',
            finePerDay: 50000,
            deposit: 200000,
            damageDeduction: 10000
        );

        $array = $result->toArray();
        $this->assertSame('2026-01-12', $array['end_date']);
        $this->assertSame('2026-01-14', $array['actual_return_date']);
        $this->assertSame(2, $array['late_days']);
        $this->assertSame(100000, $array['late_fine']);
        $this->assertSame(10000, $array['damage_deduction']);
        $this->assertSame(110000, $array['total_deduction']);
        $this->assertSame(90000, $array['deposit_remaining']);
        $this->assertSame(90000, $array['refund']);
        $this->assertSame(0, $array['shortfall']);

        $settlementAttrs = $result->toSettlementAttributes([
            'booking_id' => 10,
            'vendor_id' => 1,
            'damage_notes' => 'Catatan kerusakan ringan',
        ]);

        $this->assertSame('2026-01-14', $settlementAttrs['actual_return_date']);
        $this->assertSame(2, $settlementAttrs['late_days']);
        $this->assertSame(100000, $settlementAttrs['late_fine']);
        $this->assertSame(10000, $settlementAttrs['damage_deduction']);
        $this->assertSame(110000, $settlementAttrs['total_deduction']);
        $this->assertSame(90000, $settlementAttrs['deposit_remaining']);
        $this->assertSame(10, $settlementAttrs['booking_id']);
        $this->assertSame(1, $settlementAttrs['vendor_id']);
        $this->assertSame('Catatan kerusakan ringan', $settlementAttrs['damage_notes']);
    }

    /**
     * Test month and year crossings.
     */
    public function test_month_and_year_crossings(): void
    {
        // Year crossing
        $result = $this->calculator->calculate(
            endDate: '2025-12-30',
            actualReturnDate: '2026-01-02',
            finePerDay: 50000,
            deposit: 200000
        );

        $this->assertSame(3, $result->lateDays);
        $this->assertSame(150000, $result->lateFine);

        // Month crossing
        $result2 = $this->calculator->calculate(
            endDate: '2026-01-30',
            actualReturnDate: '2026-02-02',
            finePerDay: 50000,
            deposit: 200000
        );

        $this->assertSame(3, $result2->lateDays);
    }

    /**
     * Test timestamps with time portions are normalized to startOfDay (no partial day bugs).
     */
    public function test_time_components_do_not_skew_day_calculations(): void
    {
        $end = Carbon::parse('2026-01-12 23:59:59', 'Asia/Jakarta');
        $return = Carbon::parse('2026-01-14 01:00:00', 'Asia/Jakarta');

        $result = $this->calculator->calculate($end, $return, 50000, 200000);

        $this->assertSame(2, $result->lateDays);
        $this->assertSame(100000, $result->lateFine);
    }

    /**
     * Test negative inputs are guarded against.
     */
    public function test_negative_rates_and_damages_are_sanitized_to_zero(): void
    {
        $result = $this->calculator->calculate(
            endDate: '2026-01-12',
            actualReturnDate: '2026-01-14',
            finePerDay: -50000,
            deposit: -200000,
            damageDeduction: -30000
        );

        $this->assertSame(0, $result->finePerDay);
        $this->assertSame(0, $result->lateFine);
        $this->assertSame(0, $result->damageDeduction);
        $this->assertSame(0, $result->deposit);
        $this->assertSame(0, $result->depositRemaining);
    }
}
