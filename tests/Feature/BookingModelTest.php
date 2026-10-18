<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_booking_with_factory(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'price_per_day' => 150000]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'total_days' => 3,
            'total_price' => 450000,
            'deposit_paid' => 100000,
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'total_days' => 3,
            'total_price' => 450000,
            'deposit_paid' => 100000,
            'status' => 'pending',
            'lock_version' => 1,
        ]);

        $this->assertNotNull($booking->ulid);
        $this->assertEquals(26, strlen($booking->ulid));
        $this->assertNotNull($booking->reference_code);
        $this->assertStringStartsWith('JS-', $booking->reference_code);

        // Date and integer casts
        $this->assertInstanceOf(Carbon::class, $booking->start_date);
        $this->assertInstanceOf(Carbon::class, $booking->end_date);
        $this->assertInstanceOf(Carbon::class, $booking->pending_expires_at);
        $this->assertIsInt($booking->total_days);
        $this->assertIsInt($booking->total_price);
        $this->assertIsInt($booking->deposit_paid);
        $this->assertIsInt($booking->lock_version);
    }

    public function test_reference_code_is_unique(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261020-DUPL',
        ]);

        $this->expectException(QueryException::class);

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261020-DUPL',
        ]);
    }

    public function test_idempotency_key_is_unique(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'idempotency_key' => 'idemp-key-unique-001',
        ]);

        $this->expectException(QueryException::class);

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'idempotency_key' => 'idemp-key-unique-001',
        ]);
    }

    public function test_relationships_work(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
        ]);

        $this->assertInstanceOf(Vendor::class, $booking->vendor);
        $this->assertEquals($vendor->id, $booking->vendor->id);

        $this->assertInstanceOf(Item::class, $booking->item);
        $this->assertEquals($item->id, $booking->item->id);

        $this->assertInstanceOf(Renter::class, $booking->renter);
        $this->assertEquals($renter->id, $booking->renter->id);

        $this->assertTrue($vendor->bookings->contains($booking));
        $this->assertTrue($item->bookings->contains($booking));
        $this->assertTrue($renter->bookings->contains($booking));
    }

    public function test_deleting_item_cascades_to_bookings(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
        ]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);

        $item->delete();

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_is_blocking_calendar_helper(): void
    {
        $blockingStatuses = [
            Booking::STATUS_PENDING,
            Booking::STATUS_APPROVED,
            Booking::STATUS_ACTIVE,
            Booking::STATUS_RETURNED,
        ];

        foreach ($blockingStatuses as $status) {
            $booking = new Booking(['status' => $status]);
            $this->assertTrue($booking->isBlockingCalendar(), "Status {$status} should block calendar");
        }

        $nonBlockingStatuses = [
            Booking::STATUS_CLOSED,
            Booking::STATUS_REJECTED,
            Booking::STATUS_CANCELLED_TIMEOUT,
            Booking::STATUS_CANCELLED_RENTER,
            Booking::STATUS_CANCELLED_VENDOR,
        ];

        foreach ($nonBlockingStatuses as $status) {
            $booking = new Booking(['status' => $status]);
            $this->assertFalse($booking->isBlockingCalendar(), "Status {$status} should NOT block calendar");
        }
    }

    public function test_belongs_to_vendor_trait_works_on_booking(): void
    {
        $vendorA = Vendor::factory()->create();
        $vendorB = Vendor::factory()->create();

        $itemA = Item::factory()->create(['vendor_id' => $vendorA->id]);
        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);

        $bookingA = Booking::factory()->create(['vendor_id' => $vendorA->id, 'item_id' => $itemA->id]);
        $bookingB = Booking::factory()->create(['vendor_id' => $vendorB->id, 'item_id' => $itemB->id]);

        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);
        $this->actingAs($userA, 'vendor');

        $vendorABookings = Booking::forCurrentVendor()->get();
        $this->assertTrue($vendorABookings->contains($bookingA));
        $this->assertFalse($vendorABookings->contains($bookingB));
    }

    public function test_bookings_migration_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('bookings'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_18_000006_create_bookings_table.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('bookings'));

        // Re-run for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000006_create_bookings_table.php',
        ])->assertSuccessful();
    }
}
