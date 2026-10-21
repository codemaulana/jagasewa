<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\VendorBookingCalendar;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorCalendarPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    /**
     * T-50 / BUG-05: Unauthenticated access redirects to login.
     */
    public function test_unauthenticated_user_redirected_from_calendar_page(): void
    {
        $this->get('/vendor/calendar')
            ->assertRedirect('/vendor/login');
    }

    /**
     * T-50 / BUG-05: Authenticated vendor can render calendar page.
     */
    public function test_vendor_can_render_calendar_page(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Rental Kostum Anime',
            'is_active' => true,
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $response = $this->get('/vendor/calendar');

        $response->assertSuccessful();
        $response->assertSee('Kalender Jadwal Sewa');
        $response->assertSee('Filter Barang');
        $response->assertSee('Semua Barang');
        $response->assertSee('Keterangan:');
    }

    /**
     * T-50 / BUG-05: Calendar renders vendor's bookings and enforces strict vendor isolation.
     */
    public function test_calendar_displays_vendor_bookings_with_isolation(): void
    {
        $vendorA = Vendor::factory()->create(['name' => 'Vendor A', 'is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['name' => 'Vendor B', 'is_active' => true]);

        $itemA = Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'name' => 'Kostum Sailor Moon Eksklusif',
        ]);
        $itemB = Item::factory()->create([
            'vendor_id' => $vendorB->id,
            'name' => 'Kamera Sony A7 IV Rahasia',
        ]);

        $renterA = Renter::factory()->create(['name' => 'Penyewa Cantik']);
        $renterB = Renter::factory()->create(['name' => 'Penyewa Asing']);

        // Booking A in current month
        $now = now('Asia/Jakarta');
        $startDateA = $now->copy()->startOfMonth()->addDays(10)->toDateString();
        $endDateA = $now->copy()->startOfMonth()->addDays(12)->toDateString();

        $bookingA = Booking::factory()->create([
            'vendor_id' => $vendorA->id,
            'item_id' => $itemA->id,
            'renter_id' => $renterA->id,
            'reference_code' => 'JS-VENA-001',
            'status' => Booking::STATUS_APPROVED,
            'start_date' => $startDateA,
            'end_date' => $endDateA,
        ]);

        // Booking B belonging to Vendor B (MUST NOT appear in Vendor A calendar)
        $bookingB = Booking::factory()->create([
            'vendor_id' => $vendorB->id,
            'item_id' => $itemB->id,
            'renter_id' => $renterB->id,
            'reference_code' => 'JS-VENB-999',
            'status' => Booking::STATUS_ACTIVE,
            'start_date' => $startDateA,
            'end_date' => $endDateA,
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(VendorBookingCalendar::class)
            ->assertSuccessful()
            ->assertSee('Kostum Sailor Moon Eksklusif')
            ->assertSee('JS-VENA-001')
            ->assertDontSee('Kamera Sony A7 IV Rahasia')
            ->assertDontSee('JS-VENB-999');
    }

    /**
     * T-50 / BUG-05: Filtering by specific item narrows down the visible events.
     */
    public function test_calendar_item_filter_narrows_displayed_bookings(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $item1 = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Kebaya Modern Hijau']);
        $item2 = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Jas Formal Pria Hitam']);
        $renter = Renter::factory()->create();

        $now = now('Asia/Jakarta');
        $date = $now->copy()->startOfMonth()->addDays(5)->toDateString();

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item1->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-KB1-001',
            'status' => Booking::STATUS_ACTIVE,
            'start_date' => $date,
            'end_date' => $date,
        ]);

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item2->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-JS2-002',
            'status' => Booking::STATUS_PENDING,
            'start_date' => $date,
            'end_date' => $date,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Initially with 'all' items, both are seen
        Livewire::test(VendorBookingCalendar::class)
            ->assertSuccessful()
            ->assertSee('JS-KB1-001')
            ->assertSee('JS-JS2-002')
            // Filter to item 1
            ->set('selectedItemId', (string) $item1->id)
            ->assertSee('JS-KB1-001')
            ->assertDontSee('JS-JS2-002');
    }

    /**
     * T-50 / BUG-05: Month navigation updates calendar month and events.
     */
    public function test_calendar_month_navigation_updates_view(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $now = now('Asia/Jakarta');
        $nextMonth = $now->copy()->addMonth()->format('Y-m');
        $prevMonth = $now->copy()->subMonth()->format('Y-m');

        Livewire::test(VendorBookingCalendar::class)
            ->assertSet('selectedMonth', $now->format('Y-m'))
            ->call('nextMonth')
            ->assertSet('selectedMonth', $nextMonth)
            ->call('previousMonth')
            ->assertSet('selectedMonth', $now->format('Y-m'))
            ->call('previousMonth')
            ->assertSet('selectedMonth', $prevMonth)
            ->call('goToCurrentMonth')
            ->assertSet('selectedMonth', $now->format('Y-m'));
    }

    /**
     * T-50 / BUG-05: Buffer zones render on calendar dates.
     */
    public function test_calendar_renders_buffer_zones_correctly(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Gaun Pengantin Mewah',
            'buffer_before' => 1,
            'buffer_after' => 2,
        ]);
        $renter = Renter::factory()->create();

        $now = now('Asia/Jakarta');
        $startDate = $now->copy()->startOfMonth()->addDays(15);
        $endDate = $startDate->copy()->addDays(1);

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-GAUN-BUFFER',
            'status' => Booking::STATUS_ACTIVE,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Select the buffer before date
        $bufferBeforeDate = $startDate->copy()->subDay()->toDateString();

        Livewire::test(VendorBookingCalendar::class)
            ->call('selectDate', $bufferBeforeDate)
            ->assertSuccessful()
            ->assertSee('Buffer')
            ->assertSee('Jeda Siap (Buffer)');
    }
}
