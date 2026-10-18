<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBookingStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_vendor_can_activate_approved_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
            'lock_version' => 1,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('activate')
            ->assertActionHidden('mark_returned')
            ->assertActionHidden('approve')
            ->assertActionHidden('reject')
            ->callAction('activate')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ACTIVE, $booking->status);
        $this->assertNotNull($booking->activated_at);
        $this->assertEquals(2, $booking->lock_version);

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'booking_activated',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_vendor_can_mark_active_booking_as_returned(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_ACTIVE,
            'lock_version' => 2,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('mark_returned')
            ->assertActionHidden('activate')
            ->assertActionHidden('approve')
            ->assertActionHidden('reject')
            ->callAction('mark_returned')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_RETURNED, $booking->status);
        $this->assertNotNull($booking->returned_at);
        $this->assertEquals(3, $booking->lock_version);

        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'booking_returned',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_full_valid_status_transition_sequence(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create(['phone' => '081234567890']);

        // Start at pending
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'lock_version' => 1,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Step 1: Approve
        $component = Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('approve')
            ->callAction('approve')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);

        // Step 2: Activate
        $component->assertActionVisible('activate')
            ->callAction('activate')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ACTIVE, $booking->status);

        // Step 3: Mark Returned
        $component->assertActionVisible('mark_returned')
            ->callAction('mark_returned')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_RETURNED, $booking->status);
        $this->assertNotNull($booking->returned_at);
    }
}
