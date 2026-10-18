<?php

namespace Tests\Feature;

use App\Jobs\AutoCancelPendingBookingsJob;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Services\BookingCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCancelPendingBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_pending_booking_is_automatically_cancelled(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);
        $renter = Renter::factory()->create();

        // Expired 1 hour ago
        $expiredBooking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->subHour(),
            'lock_version' => 1,
        ]);

        $cancelledCount = (new AutoCancelPendingBookingsJob)->handle();

        $this->assertEquals(1, $cancelledCount);

        $expiredBooking->refresh();
        $this->assertEquals(Booking::STATUS_CANCELLED_TIMEOUT, $expiredBooking->status);
        $this->assertEquals(2, $expiredBooking->lock_version);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'booking_cancelled_timeout',
            'entity_type' => 'booking',
            'entity_id' => $expiredBooking->id,
        ]);
    }

    public function test_non_expired_pending_booking_is_not_cancelled(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);
        $renter = Renter::factory()->create();

        // Expires 10 hours from now
        $activePending = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->addHours(10),
            'lock_version' => 1,
        ]);

        $cancelledCount = (new AutoCancelPendingBookingsJob)->handle();

        $this->assertEquals(0, $cancelledCount);

        $activePending->refresh();
        $this->assertEquals(Booking::STATUS_PENDING, $activePending->status);
        $this->assertEquals(1, $activePending->lock_version);
    }

    public function test_non_pending_bookings_are_not_affected_even_if_expired_at_is_in_past(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);
        $renter = Renter::factory()->create();

        $approvedBooking = Booking::factory()->approved()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->subDays(2),
        ]);

        $cancelledCount = (new AutoCancelPendingBookingsJob)->handle();

        $this->assertEquals(0, $cancelledCount);

        $approvedBooking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $approvedBooking->status);
    }

    public function test_cancelling_booking_frees_calendar_dates(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'is_active' => true,
        ]);
        $renter = Renter::factory()->create();

        $start = Carbon::now('Asia/Jakarta')->addDays(3)->toDateString();
        $end = Carbon::now('Asia/Jakarta')->addDays(5)->toDateString();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'start_date' => $start,
            'end_date' => $end,
            'status' => Booking::STATUS_PENDING,
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->subHours(2),
        ]);

        $calendarService = new BookingCalendarService;

        // Before job: calendar slot is locked
        $this->assertFalse($calendarService->isAvailable($item, $start, $end));

        // Run job
        (new AutoCancelPendingBookingsJob)->handle();

        // After job: calendar slot is now freed
        $this->assertTrue($calendarService->isAvailable($item, $start, $end));
    }

    public function test_artisan_command_executes_successfully(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'pending_expires_at' => Carbon::now('Asia/Jakarta')->subHours(5),
        ]);

        $this->artisan('booking:auto-cancel-pending')
            ->expectsOutputToContain('Successfully cancelled 1 expired pending booking(s).')
            ->assertSuccessful();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CANCELLED_TIMEOUT, $booking->status);
    }
}
