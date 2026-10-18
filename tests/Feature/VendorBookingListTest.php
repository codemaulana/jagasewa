<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Filament\Vendor\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBookingListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_unauthenticated_user_cannot_access_vendor_bookings_page(): void
    {
        $response = $this->get('/vendor/bookings');

        $response->assertRedirect('/vendor/login');
    }

    public function test_authenticated_vendor_can_access_bookings_page(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get('/vendor/bookings');

        $response->assertStatus(200);
    }

    public function test_vendor_a_only_sees_own_bookings_and_not_vendor_b_bookings(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $userB = VendorUser::factory()->create(['vendor_id' => $vendorB->id]);

        $itemA = Item::factory()->create(['vendor_id' => $vendorA->id]);
        $renterA = Renter::factory()->create(['name' => 'Penyewa Toko A']);
        $bookingA = Booking::factory()->create([
            'vendor_id' => $vendorA->id,
            'item_id' => $itemA->id,
            'renter_id' => $renterA->id,
            'reference_code' => 'JS-20261019-AAAA',
            'status' => Booking::STATUS_PENDING,
        ]);

        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);
        $renterB = Renter::factory()->create(['name' => 'Penyewa Toko B']);
        $bookingB = Booking::factory()->create([
            'vendor_id' => $vendorB->id,
            'item_id' => $itemB->id,
            'renter_id' => $renterB->id,
            'reference_code' => 'JS-20261019-BBBB',
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$bookingA])
            ->assertCanNotSeeTableRecords([$bookingB]);
    }

    public function test_vendor_can_filter_bookings_by_status(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $pendingBooking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-PEND',
            'status' => Booking::STATUS_PENDING,
        ]);

        $approvedBooking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-APPR',
            'status' => Booking::STATUS_APPROVED,
        ]);

        $closedBooking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-CLOS',
            'status' => Booking::STATUS_CLOSED,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Filter: pending
        Livewire::test(ListBookings::class)
            ->filterTable('status', Booking::STATUS_PENDING)
            ->assertCanSeeTableRecords([$pendingBooking])
            ->assertCanNotSeeTableRecords([$approvedBooking, $closedBooking]);

        // Filter: approved
        Livewire::test(ListBookings::class)
            ->filterTable('status', Booking::STATUS_APPROVED)
            ->assertCanSeeTableRecords([$approvedBooking])
            ->assertCanNotSeeTableRecords([$pendingBooking, $closedBooking]);
    }

    public function test_vendor_can_filter_bookings_by_item(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $itemKostum = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Sailor Moon',
        ]);

        $itemKamera = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kamera Fujifilm X-T4',
        ]);

        $renter = Renter::factory()->create();

        $bookingKostum = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemKostum->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-KOS1',
        ]);

        $bookingKamera = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemKamera->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-KAM1',
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Filter by item: Kostum
        Livewire::test(ListBookings::class)
            ->filterTable('item_id', $itemKostum->id)
            ->assertCanSeeTableRecords([$bookingKostum])
            ->assertCanNotSeeTableRecords([$bookingKamera]);

        // Filter by item: Kamera
        Livewire::test(ListBookings::class)
            ->filterTable('item_id', $itemKamera->id)
            ->assertCanSeeTableRecords([$bookingKamera])
            ->assertCanNotSeeTableRecords([$bookingKostum]);
    }

    public function test_bookings_table_renders_expected_columns(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kebaya Modern Brokat',
            'size' => 'M',
        ]);
        $renter = Renter::factory()->create([
            'name' => 'Siti Nurhaliza',
            'phone' => '081234567890',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-SPEC',
            'status' => Booking::STATUS_PENDING,
            'start_date' => Carbon::now('Asia/Jakarta')->addDays(2)->format('Y-m-d'),
            'end_date' => Carbon::now('Asia/Jakarta')->addDays(4)->format('Y-m-d'),
            'total_days' => 3,
            'total_price' => 300000,
            'deposit_paid' => 100000,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$booking])
            ->assertTableColumnExists('reference_code')
            ->assertTableColumnExists('item.name')
            ->assertTableColumnExists('renter.name')
            ->assertTableColumnExists('rental_dates')
            ->assertTableColumnExists('total_price')
            ->assertTableColumnExists('status');
    }

    public function test_pending_badge_count_reflects_only_own_pending_bookings(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);

        $itemA = Item::factory()->create(['vendor_id' => $vendorA->id]);
        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);

        $renter = Renter::factory()->create();

        // 2 pending bookings for Vendor A
        Booking::factory()->count(2)->create([
            'vendor_id' => $vendorA->id,
            'item_id' => $itemA->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
        ]);

        // 1 approved booking for Vendor A
        Booking::factory()->create([
            'vendor_id' => $vendorA->id,
            'item_id' => $itemA->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
        ]);

        // 5 pending bookings for Vendor B
        Booking::factory()->count(5)->create([
            'vendor_id' => $vendorB->id,
            'item_id' => $itemB->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        // Navigation badge for Vendor A should be '2', not 7
        $this->assertEquals('2', BookingResource::getNavigationBadge());
    }
}
