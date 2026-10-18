<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Services\BookingStatusService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingStatusService;
    }

    protected function createBooking(string $status): Booking
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        return Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => $status,
            'lock_version' => 1,
        ]);
    }

    public function test_approve_valid_pending_booking(): void
    {
        $booking = $this->createBooking(Booking::STATUS_PENDING);

        $result = $this->service->approve($booking);

        $this->assertEquals(Booking::STATUS_APPROVED, $result->status);
        $this->assertNotNull($result->approved_at);
        $this->assertEquals(2, $result->lock_version);

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $booking->vendor_id,
            'event' => 'booking_approved',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_approve_fails_if_booking_not_pending(): void
    {
        $booking = $this->createBooking(Booking::STATUS_APPROVED);

        $this->expectException(DomainException::class);
        $this->service->approve($booking);
    }

    public function test_reject_valid_pending_booking_and_reschedules_documents(): void
    {
        $booking = $this->createBooking(Booking::STATUS_PENDING);

        $doc = IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $booking->renter_id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => 'identity-documents/'.(string) Str::uuid().'.jpg',
            'storage_disk' => 's3',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta'),
            'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->addDays(7),
        ]);

        $result = $this->service->reject($booking, 'Identitas tidak valid');

        $this->assertEquals(Booking::STATUS_REJECTED, $result->status);
        $this->assertEquals('Identitas tidak valid', $result->vendor_notes);
        $this->assertEquals(2, $result->lock_version);

        // Document retention rescheduled to 3 days (BR-05)
        $doc->refresh();
        $expectedDate = Carbon::now('Asia/Jakarta')->addDays(3)->format('Y-m-d');
        $this->assertEquals($expectedDate, $doc->delete_scheduled_at->format('Y-m-d'));

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $booking->vendor_id,
            'event' => 'booking_rejected',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_reject_fails_if_booking_not_pending(): void
    {
        $booking = $this->createBooking(Booking::STATUS_ACTIVE);

        $this->expectException(DomainException::class);
        $this->service->reject($booking);
    }

    public function test_activate_valid_approved_booking(): void
    {
        $booking = $this->createBooking(Booking::STATUS_APPROVED);

        $result = $this->service->activate($booking);

        $this->assertEquals(Booking::STATUS_ACTIVE, $result->status);
        $this->assertNotNull($result->activated_at);
        $this->assertEquals(2, $result->lock_version);

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $booking->vendor_id,
            'event' => 'booking_activated',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_activate_fails_if_booking_is_pending(): void
    {
        $booking = $this->createBooking(Booking::STATUS_PENDING);

        $this->expectException(DomainException::class);
        $this->service->activate($booking);
    }

    public function test_activate_fails_if_booking_is_already_active(): void
    {
        $booking = $this->createBooking(Booking::STATUS_ACTIVE);

        $this->expectException(DomainException::class);
        $this->service->activate($booking);
    }

    public function test_mark_returned_valid_active_booking(): void
    {
        $booking = $this->createBooking(Booking::STATUS_ACTIVE);

        $result = $this->service->markReturned($booking);

        $this->assertEquals(Booking::STATUS_RETURNED, $result->status);
        $this->assertNotNull($result->returned_at);
        $this->assertEquals(2, $result->lock_version);

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $booking->vendor_id,
            'event' => 'booking_returned',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_mark_returned_fails_if_booking_is_not_active(): void
    {
        // Cannot mark returned directly from approved
        $approvedBooking = $this->createBooking(Booking::STATUS_APPROVED);

        $this->expectException(DomainException::class);
        $this->service->markReturned($approvedBooking);
    }

    public function test_mark_returned_fails_if_booking_is_pending(): void
    {
        // Cannot mark returned directly from pending
        $pendingBooking = $this->createBooking(Booking::STATUS_PENDING);

        $this->expectException(DomainException::class);
        $this->service->markReturned($pendingBooking);
    }
}
