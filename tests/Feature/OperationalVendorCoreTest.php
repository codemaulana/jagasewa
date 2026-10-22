<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\VendorBookingCalendar;
use App\Filament\Vendor\Widgets\RentalStatsOverviewWidget;
use App\Filament\Vendor\Widgets\TopRentedItemsWidget;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\Billing\EntitlementService;
use App\Services\BookingStatusService;
use App\Services\FineCalculatorService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalVendorCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Storage::fake('s3');
        Storage::fake('public');
    }

    /**
     * Test 1: Rental Stats Overview Widget accurately tracks today tasks and monthly revenue.
     */
    public function test_rental_stats_overview_widget_today_tasks_and_monthly_revenue(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Rental Kamera Jogja']);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'category' => 'Kamera & Lensa',
            'item_code' => 'SONY-A7IV',
            'package_price_3d' => 850000,
        ]);
        $renter = Renter::factory()->create();
        $today = now('Asia/Jakarta')->toDateString();

        // 1. Pickup today (pending / approved starting today)
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
            'start_date' => $today,
            'end_date' => now('Asia/Jakarta')->addDays(2)->toDateString(),
            'total_price' => 300000,
        ]);

        // 2. Return today (active ending today)
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_ACTIVE,
            'start_date' => now('Asia/Jakarta')->subDays(2)->toDateString(),
            'end_date' => $today,
            'total_price' => 300000,
        ]);

        // 3. Overdue return (active ending before today)
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_ACTIVE,
            'start_date' => now('Asia/Jakarta')->subDays(5)->toDateString(),
            'end_date' => now('Asia/Jakarta')->subDay()->toDateString(),
            'total_price' => 450000,
        ]);

        // 4. Pending booking waiting confirmation
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'start_date' => now('Asia/Jakarta')->addDays(3)->toDateString(),
            'end_date' => now('Asia/Jakarta')->addDays(5)->toDateString(),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $component = Livewire::test(RentalStatsOverviewWidget::class)
            ->assertSuccessful();

        /** @var RentalStatsOverviewWidget $widget */
        $widget = $component->instance();
        $stats = $widget->getStats();

        // Stats: [0] Tugas Hari Ini, [1] Pendapatan Bulan Ini, [2] Menunggu Konfirmasi
        $this->assertEquals('Tugas Hari Ini', $stats[0]->getLabel());
        $this->assertEquals(3, $stats[0]->getValue()); // 1 pickup + 1 return + 1 overdue
        $this->assertStringContainsString('1 Pengambilan', $stats[0]->getDescription());
        $this->assertStringContainsString('1 Pengembalian', $stats[0]->getDescription());
        $this->assertStringContainsString('1 Terlambat', $stats[0]->getDescription());

        $this->assertEquals('Pendapatan Bulan Ini', $stats[1]->getLabel());
        $this->assertStringStartsWith('Rp ', $stats[1]->getValue());

        $this->assertEquals('Menunggu Konfirmasi', $stats[2]->getLabel());
        $this->assertEquals(1, $stats[2]->getValue());
    }

    /**
     * Test 2: Top Rented Items Widget displays top item & teaser for Free, and ranking table for Pro.
     */
    public function test_top_rented_items_widget_free_vs_pro(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Studio Kostum Pro']);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $itemA = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Sailor Moon Super S',
            'category' => 'Kostum & Cosplay',
            'item_code' => 'KOS-001',
        ]);
        $itemB = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kebaya Encim Putih',
            'category' => 'Kebaya & Busana Adat',
            'item_code' => 'KBY-002',
        ]);

        $renter = Renter::factory()->create();

        // 3 bookings for item A (closed / active)
        Booking::factory()->count(3)->create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemA->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_CLOSED,
            'total_price' => 200000,
        ]);

        // 1 booking for item B
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $itemB->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_ACTIVE,
            'total_price' => 150000,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // --- Free Tier Verification ---
        Livewire::test(TopRentedItemsWidget::class)
            ->assertSuccessful()
            ->assertSee('Barang Paling Sering Disewa')
            ->assertSee('Mode Dasar')
            ->assertSee('Kostum Sailor Moon Super S')
            ->assertSee('Detail Analitik', false)
            ->assertSee('Lihat Paket Pro');

        // --- Upgrade to Pro Verification ---
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        Livewire::test(TopRentedItemsWidget::class)
            ->assertSuccessful()
            ->assertSee('Analitik Pro Aktif')
            ->assertSee('Kostum Sailor Moon Super S')
            ->assertSee('Kebaya Encim Putih')
            ->assertSee('3x sewa')
            ->assertSee('KOS-001')
            ->assertSee('KBY-002');
    }

    /**
     * Test 3: Catalog item creation supports operational attributes and enforces 15-item limit for Free plan.
     */
    public function test_item_catalog_attributes_and_15_item_limit(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Toko Rental Berkah']);
        $entitlement = app(EntitlementService::class);

        // Verify Free plan limit configuration
        $this->assertEquals(15, $entitlement->limitFor($vendor, 'active_items'));

        // Create 14 items
        for ($i = 1; $i <= 14; $i++) {
            Item::factory()->create([
                'vendor_id' => $vendor->id,
                'name' => "Unit Barang {$i}",
                'category' => 'Elektronik & Audio',
                'item_code' => "ELEK-{$i}",
                'unit_status' => Item::UNIT_STATUS_AVAILABLE,
                'condition' => 'Sangat Baik',
                'package_price_3d' => 150000,
                'is_active' => true,
            ]);
        }

        $this->assertEquals(14, $vendor->items()->count());
        $this->assertTrue($entitlement->canAddActiveItem($vendor));

        // Create 15th item (reaches Free tier max limit)
        $item15 = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Unit Barang 15',
            'is_active' => true,
        ]);

        $this->assertFalse($entitlement->isOverLimit($vendor, 'active_items'));
        $this->assertFalse($entitlement->canAddActiveItem($vendor));
        $this->assertFalse($entitlement->isItemReadOnly($vendor, $item15));

        // 16th item created (e.g. imported or post-downgrade)
        $item16 = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Unit Barang 16 Kelebihan',
            'is_active' => true,
        ]);

        $this->assertTrue($entitlement->isOverLimit($vendor, 'active_items'));
        $this->assertTrue($entitlement->isItemReadOnly($vendor, $item16));

        // When vendor upgrades to Pro, limit becomes null/unlimited and 16th item is editable
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $this->assertTrue($entitlement->canAddActiveItem($vendor));
        $this->assertFalse($entitlement->isItemReadOnly($vendor, $item16));
    }

    /**
     * Test 4: Booking payment recording (DP & Full) and Handover (activate) operational workflow.
     */
    public function test_booking_payment_and_handover_workflow(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'total_price' => 500000,
            'deposit_paid' => 100000,
        ]);

        $statusService = app(BookingStatusService::class);

        // 1. Approve booking
        $statusService->approve($booking);
        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);
        $this->assertNotNull($booking->approved_at);

        // 2. Record Down Payment (DP)
        DB::transaction(function () use ($booking) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'is_dp_paid' => true,
                'dp_paid_at' => now('Asia/Jakarta'),
                'down_payment_amount' => 250000,
                'payment_notes' => 'Transfer via Mandiri 123456',
            ]);
        });

        $booking->refresh();
        $this->assertTrue($booking->is_dp_paid);
        $this->assertEquals(250000, $booking->down_payment_amount);
        $this->assertFalse($booking->is_paid_in_full);

        // 3. Handover / Activation with operational checklist & method
        DB::transaction(function () use ($booking, $statusService) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'handover_method' => 'delivery_courier',
                'tracking_number' => 'PAXEL-JKT-12345',
                'handover_checklist' => ['fisik_mulus', 'fungsi_normal', 'aksesoris_lengkap'],
                'handover_notes' => 'Dikemas dengan bubble wrap tebal 3 lapis',
            ]);
            $statusService->activate($locked);
        });

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ACTIVE, $booking->status);
        $this->assertEquals('delivery_courier', $booking->handover_method);
        $this->assertEquals('PAXEL-JKT-12345', $booking->tracking_number);
        $this->assertContains('fisik_mulus', $booking->handover_checklist);

        // 4. Record Full Payment (Pelunasan)
        DB::transaction(function () use ($booking) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'is_paid_in_full' => true,
                'paid_in_full_at' => now('Asia/Jakarta'),
            ]);
        });

        $booking->refresh();
        $this->assertTrue($booking->is_paid_in_full);
        $this->assertNotNull($booking->paid_in_full_at);
    }

    /**
     * Test 5: Booking return and settlement with checklist and damage photos.
     */
    public function test_booking_return_and_settlement_with_checklist(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'fine_per_day' => 50000]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_ACTIVE,
            'start_date' => now('Asia/Jakarta')->subDays(4)->toDateString(),
            'end_date' => now('Asia/Jakarta')->subDays(2)->toDateString(),
            'total_price' => 400000,
            'deposit_paid' => 200000,
        ]);

        $statusService = app(BookingStatusService::class);
        $calculator = app(FineCalculatorService::class);

        // Mark returned
        $statusService->markReturned($booking);
        $booking->refresh();
        $this->assertEquals(Booking::STATUS_RETURNED, $booking->status);

        // Settlement with damage calculation
        $actualReturnDate = now('Asia/Jakarta')->toDateString();
        $damageItems = [
            ['description' => 'Kancing jas tanggal', 'amount' => 30000],
        ];
        $damageTotal = $calculator->calculateTotalDamage($damageItems);
        $calcResult = $calculator->calculateForBooking($booking, $actualReturnDate, $damageTotal);

        DB::transaction(function () use ($booking, $statusService, $calcResult, $damageItems) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'return_checklist' => ['semua_kembali', 'kebersihan_standar'],
                'return_notes' => 'Kancing jas tanggal saat acara',
            ]);

            $statusService->closeWithSettlement(
                $locked,
                $calcResult->toSettlementAttributes(['damage_notes' => 'Perbaikan kancing']),
                $damageItems
            );
        });

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CLOSED, $booking->status);
        $this->assertNotNull($booking->closed_at);
        $this->assertContains('semua_kembali', $booking->return_checklist);
        $this->assertNotNull($booking->settlement);
        $this->assertEquals(30000, $booking->settlement->damage_deduction);
    }

    /**
     * Test 6: Vendor calendar page supports week/month view switching and category buffer configuration.
     */
    public function test_calendar_page_and_category_buffer_integration(): void
    {
        $vendor = Vendor::factory()->create([
            'category_buffer_settings' => [
                'Kamera & Lensa' => ['buffer_before' => 1, 'buffer_after' => 1],
                'Kostum & Cosplay' => ['buffer_before' => 2, 'buffer_after' => 2],
            ],
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->assertEquals(['buffer_before' => 1, 'buffer_after' => 1], $vendor->getCategoryBuffer('Kamera & Lensa'));
        $this->assertEquals(['buffer_before' => 2, 'buffer_after' => 2], $vendor->getCategoryBuffer('Kostum & Cosplay'));
        $this->assertEquals(['buffer_before' => 1, 'buffer_after' => 1], $vendor->getCategoryBuffer('Lainnya'));

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(VendorBookingCalendar::class)
            ->assertSuccessful()
            ->assertSet('viewMode', 'month')
            ->call('setViewMode', 'week')
            ->assertSet('viewMode', 'week')
            ->call('setViewMode', 'month')
            ->assertSet('viewMode', 'month')
            ->assertSee('Semua Barang');
    }
}
