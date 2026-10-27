<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Vendor;
use App\Services\BookingCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BookingCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingCalendarService;
    }

    /**
     * Overlap langsung
     * Existing: 10–12 Jan (buffer 2, 2)
     * Baru: 11–13 Jan (buffer 2, 2)
     * Hasil: BENTROK
     */
    public function test_direct_overlap_is_clashing(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'approved',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-11',
            endDate: '2026-01-13',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Direct overlap should clash.');
    }

    /**
     * Irisan di zona buffer before
     * Existing: 10–12 Jan (buffer 2, 2) → Efektif: 8–14 Jan
     * Baru: 7–9 Jan (buffer 2, 2) → Efektif: 5–11 Jan
     * Hasil: BENTROK
     */
    public function test_overlap_in_buffer_before_zone_is_clashing(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'approved',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-07',
            endDate: '2026-01-09',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Overlap in buffer before zone should clash.');
    }

    /**
     * Irisan di zona buffer after
     * Existing: 10–12 Jan (buffer 2, 2) → Efektif: 8–14 Jan
     * Baru: 13–15 Jan (buffer 2, 2) → Efektif: 11–17 Jan
     * Hasil: BENTROK
     */
    public function test_overlap_in_buffer_after_zone_is_clashing(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'approved',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-13',
            endDate: '2026-01-15',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Overlap in buffer after zone should clash.');
    }

    /**
     * Back-to-back tepat di batas buffer
     * Existing: 10–12 Jan (buffer 2, 2) → Efektif: 8–14 Jan
     * Baru: 15–17 Jan (buffer 2, 2) → Efektif: 13–19 Jan
     * Hasil: BENTROK (tanggal 13 & 14 beririsan)
     */
    public function test_back_to_back_at_buffer_boundary_is_clashing(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'approved',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-15',
            endDate: '2026-01-17',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Back-to-back at buffer boundary should clash because effective ranges overlap on days 13-14.');
    }

    /**
     * Aman di luar buffer
     * Existing: 10–12 Jan (buffer 2, 2) → Efektif: 8–14 Jan
     * Baru: 17–20 Jan (buffer 2, 2) → Efektif: 15–22 Jan
     * Hasil: AMAN
     */
    public function test_safe_slot_outside_buffer_is_available(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'approved',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-17',
            endDate: '2026-01-20',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertTrue($available, 'Slot outside buffer should be available.');
    }

    /**
     * Rentang satu hari
     * Existing: 15 Jan–15 Jan (buffer 1, 1) → Efektif: 14–16 Jan
     * Baru: 16 Jan–16 Jan (buffer 1, 1) → Efektif: 15–17 Jan
     * Hasil: BENTROK (16 Jan dalam buffer after)
     */
    public function test_single_day_range_is_clashing_when_buffers_overlap(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-15',
                'end_date' => '2026-01-15',
                'buffer_before' => 1,
                'buffer_after' => 1,
                'status' => 'pending',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-16',
            endDate: '2026-01-16',
            bufferBefore: 1,
            bufferAfter: 1,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Single day ranges with overlapping buffer should clash.');
    }

    /**
     * Pergantian bulan
     * Existing: 29 Jan–2 Feb (buffer 2, 2) → Efektif: 27 Jan–4 Feb
     * Baru: 4 Feb–6 Feb (buffer 2, 2) → Efektif: 2–8 Feb
     * Hasil: BENTROK (tanggal 4 Feb beririsan)
     */
    public function test_month_boundary_transition_is_clashing(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-29',
                'end_date' => '2026-02-02',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'active',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-02-04',
            endDate: '2026-02-06',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Month boundary transition with overlapping effective date must clash.');
    }

    /**
     * Pergantian tahun
     * Existing: 30 Des 2026–1 Jan 2027 (buffer 2, 2) → Efektif: 28 Des–3 Jan
     * Baru: 3 Jan 2027–5 Jan 2027 (buffer 2, 2) → Efektif: 1 Jan–7 Jan
     * Hasil: BENTROK (tanggal 3 Jan beririsan)
     */
    public function test_year_boundary_transition_is_clashing(): void
    {
        $existing = [
            [
                'start_date' => '2026-12-30',
                'end_date' => '2027-01-01',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'returned',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2027-01-03',
            endDate: '2027-01-05',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existing
        );

        $this->assertFalse($available, 'Year boundary transition with overlapping effective date must clash.');
    }

    /**
     * Pembatalan membebaskan slot
     * Existing: 10–12 Jan (cancelled) (buffer 2, 2)
     * Baru: 10–12 Jan (buffer 2, 2)
     * Hasil: AMAN (booking cancelled tidak mengunci kalender)
     */
    public function test_cancelled_booking_frees_the_slot(): void
    {
        $existingCancelled = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'cancelled_vendor',
            ],
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'cancelled',
            ],
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'rejected',
            ],
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 2,
                'buffer_after' => 2,
                'status' => 'closed',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-10',
            endDate: '2026-01-12',
            bufferBefore: 2,
            bufferAfter: 2,
            existingBookings: $existingCancelled
        );

        $this->assertTrue($available, 'Cancelled/rejected/closed bookings must free the slot.');
    }

    /**
     * Buffer = 0 back-to-back
     * Existing: 10–12 Jan (buffer 0, 0) → Efektif: 10–12 Jan
     * Baru: 13–15 Jan (buffer 0, 0) → Efektif: 13–15 Jan
     * Hasil: AMAN (tidak beririsan)
     */
    public function test_zero_buffer_allows_exact_back_to_back(): void
    {
        $existing = [
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-01-12',
                'buffer_before' => 0,
                'buffer_after' => 0,
                'status' => 'approved',
            ],
        ];

        $available = $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-13',
            endDate: '2026-01-15',
            bufferBefore: 0,
            bufferAfter: 0,
            existingBookings: $existing
        );

        $this->assertTrue($available, 'Buffer 0 must allow exact back-to-back booking.');
    }

    /**
     * Validasi: start_date lebih besar dari end_date melempar exception.
     */
    public function test_start_date_after_end_date_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->isAvailable(
            itemId: 1,
            startDate: '2026-01-15',
            endDate: '2026-01-10'
        );
    }

    /**
     * Validasi: item inactive tidak dapat dibooking.
     */
    public function test_inactive_item_is_not_available(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->inactive()->create(['vendor_id' => $vendor->id]);

        $available = $this->service->isAvailable(
            itemId: $item,
            startDate: '2026-03-01',
            endDate: '2026-03-05'
        );

        $this->assertFalse($available, 'Inactive item must return false for isAvailable.');
    }

    /**
     * Menguji helper calculateEffectiveRange.
     */
    public function test_calculate_effective_range(): void
    {
        $range = $this->service->calculateEffectiveRange('2026-01-10', '2026-01-12', 2, 3);

        $this->assertEquals('2026-01-08', $range['start']->toDateString());
        $this->assertEquals('2026-01-15', $range['end']->toDateString());
    }

    /**
     * Menguji helper isStatusLocking.
     */
    public function test_is_status_locking_distinguishes_correctly(): void
    {
        $this->assertTrue($this->service->isStatusLocking('pending'));
        $this->assertTrue($this->service->isStatusLocking('approved'));
        $this->assertTrue($this->service->isStatusLocking('active'));
        $this->assertTrue($this->service->isStatusLocking('returned'));

        $this->assertFalse($this->service->isStatusLocking('rejected'));
        $this->assertFalse($this->service->isStatusLocking('closed'));
        $this->assertFalse($this->service->isStatusLocking('cancelled'));
        $this->assertFalse($this->service->isStatusLocking('cancelled_timeout'));
        $this->assertFalse($this->service->isStatusLocking('cancelled_renter'));
        $this->assertFalse($this->service->isStatusLocking('cancelled_vendor'));
    }
}
