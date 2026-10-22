<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\Reports;
use App\Filament\Vendor\Pages\StoreSettings;
use App\Filament\Vendor\Resources\VendorUsers\Pages\CreateVendorUser;
use App\Filament\Vendor\Widgets\RentalStatsOverviewWidget;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Gelombang3GrowthTest extends TestCase
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
     * Test 1: Multi-Staf quota limits (Free max 1 staff, Pro up to 5 staff).
     */
    public function test_multi_staf_quota_limits_free_vs_pro(): void
    {
        // 1. Free Vendor starts with 1 owner
        $freeVendor = Vendor::factory()->create(['name' => 'Rental Free']);
        $owner = VendorUser::factory()->create([
            'vendor_id' => $freeVendor->id,
            'role' => VendorUser::ROLE_OWNER,
            'email' => 'owner.free@example.com',
        ]);

        $this->actingAs($owner, 'vendor');

        // Free limit is 1, current is 1. Attempting to create 2nd user must fail with quota error
        Livewire::test(CreateVendorUser::class)
            ->fillForm([
                'name' => 'Staf Tambahan',
                'email' => 'staf.extra@example.com',
                'password' => 'Password123!',
                'role' => VendorUser::ROLE_OPERATIONAL,
            ])
            ->call('create')
            ->assertHasErrors();

        $this->assertEquals(1, $freeVendor->users()->count());
    }

    /**
     * Test 1b: Pro Vendor can add operational staff up to 5 users.
     */
    public function test_pro_vendor_can_add_operational_staff(): void
    {
        $proVendor = Vendor::factory()->create(['name' => 'Rental Pro Mega']);
        $owner = VendorUser::factory()->create([
            'vendor_id' => $proVendor->id,
            'role' => VendorUser::ROLE_OWNER,
            'email' => 'owner.pro@example.com',
        ]);

        Subscription::factory()->create([
            'vendor_id' => $proVendor->id,
            'plan' => 'pro',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(30),
        ]);

        $this->actingAs($owner, 'vendor');

        Livewire::test(CreateVendorUser::class)
            ->fillForm([
                'name' => 'Budi Staf Ops',
                'email' => 'budi.ops@example.com',
                'password' => 'Password123!',
                'role' => VendorUser::ROLE_OPERATIONAL,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEquals(2, $proVendor->users()->count());
        $newStaff = $proVendor->users()->where('email', 'budi.ops@example.com')->first();
        $this->assertNotNull($newStaff);
        $this->assertTrue($newStaff->isOperational());
        $this->assertFalse($newStaff->canViewFinancials());
        $this->assertFalse($newStaff->canManageSettings());
    }

    /**
     * Test 2: Role-based access control for Operational Staff (masking financials & blocking settings).
     */
    public function test_operational_staff_role_financial_masking_and_access_restrictions(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Studio Pro']);
        $owner = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => VendorUser::ROLE_OWNER,
        ]);
        $staff = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => VendorUser::ROLE_OPERATIONAL,
        ]);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => 'pro',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(30),
        ]);

        // A. Owner access checks
        $this->actingAs($owner, 'vendor');
        $this->assertTrue(Reports::canAccess());
        $this->assertTrue(StoreSettings::canAccess());

        $ownerStats = Livewire::test(RentalStatsOverviewWidget::class);
        $ownerStats->assertSee('Pendapatan Bulan Ini');

        // B. Operational staff access checks
        $this->actingAs($staff, 'vendor');
        $this->assertFalse(Reports::canAccess());
        $this->assertFalse(StoreSettings::canAccess());

        $staffStats = Livewire::test(RentalStatsOverviewWidget::class);
        $staffStats->assertDontSee('Pendapatan Bulan Ini');
        $staffStats->assertSee('Pesanan Sedang Aktif');

        // C. Operational staff accessing Billing or Report Export gets 403 Forbidden
        $billingResponse = $this->get(route('vendor.billing.index'));
        $billingResponse->assertStatus(403);

        $exportResponse = $this->get(route('vendor.reports.export'));
        $exportResponse->assertStatus(403);
    }

    /**
     * Test 3: Reports & Analytics metrics, item utilization, and CSV export.
     */
    public function test_reports_analytics_calculation_and_csv_export(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Analytics Rental']);
        $owner = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => VendorUser::ROLE_OWNER,
        ]);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => 'pro',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(30),
        ]);

        $renter = Renter::factory()->create(['name' => 'Siti Nurhaliza', 'phone' => '081234567899']);
        $item1 = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Sony A7IV', 'price_per_day' => 350000]);
        $item2 = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Lensa 24-70mm GM', 'price_per_day' => 200000]);

        // Create completed bookings in this month
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item1->id,
            'renter_id' => $renter->id,
            'reference_code' => 'BK-TEST-ANALYTICS-01',
            'status' => Booking::STATUS_CLOSED,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'total_days' => 4,
            'total_price' => 1400000,
            'created_at' => now(),
        ]);

        $this->actingAs($owner, 'vendor');

        // Test Reports Livewire page
        $component = Livewire::test(Reports::class)
            ->assertSee('Sony A7IV')
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Rp 1.400.000');

        $viewData = $component->instance()->getViewData();
        $this->assertEquals(1400000, $viewData['totalRevenue']);
        $this->assertEquals(1, $viewData['totalBookingsCount']);

        $itemStats = $viewData['itemStats'];
        $this->assertNotEmpty($itemStats);
        $sonyUtil = collect($itemStats)->firstWhere('id', $item1->id);
        $this->assertNotNull($sonyUtil);
        $this->assertEquals(4, $sonyUtil['rented_days']);
        $this->assertGreaterThan(0, $sonyUtil['utilization_rate']);

        $topRenters = $viewData['topRenters'];
        $this->assertNotEmpty($topRenters);
        $this->assertEquals('Siti Nurhaliza', $topRenters[0]['name']);
        $this->assertEquals(1400000, $topRenters[0]['total_spent']);

        // Test CSV Export endpoint
        $exportResponse = $this->get(route('vendor.reports.export', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]));

        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('laporan-booking-jagasewa', $exportResponse->headers->get('Content-Disposition'));

        ob_start();
        $exportResponse->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('BK-TEST-ANALYTICS-01', $csvContent);
        $this->assertStringContainsString('Siti Nurhaliza', $csvContent);
        $this->assertStringContainsString('Sony A7IV', $csvContent);
    }

    /**
     * Test 4: Custom Domain & White-Label store customization end-to-end.
     */
    public function test_custom_domain_and_white_label_branding_suppression(): void
    {
        // 1. Pro Vendor with white-label active and custom domain
        $proVendor = Vendor::factory()->create([
            'name' => 'Toko Busana Mewah',
            'slug' => 'toko-busana',
            'custom_domain' => 'sewa.busanamewah.com',
            'white_label_enabled' => true,
            'banner_path' => 'vendor_banners/test-banner.jpg',
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'vendor_id' => $proVendor->id,
            'plan' => 'pro',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(30),
        ]);

        $this->assertTrue($proVendor->isWhiteLabelActive());

        $itemPro = Item::factory()->create([
            'vendor_id' => $proVendor->id,
            'slug' => 'gaun-pesta-merah',
            'name' => 'Gaun Pesta Merah Maroon',
            'is_active' => true,
        ]);

        // Access via regular slug
        $catalogResponse = $this->get('/b/toko-busana');
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Toko Busana Mewah');
        $catalogResponse->assertSee('Gaun Pesta Merah Maroon');
        // Footer "Didukung oleh JagaSewa" must NOT be rendered when white_label is active
        $catalogResponse->assertDontSee('Didukung oleh');
        $catalogResponse->assertDontSee('Platform Penyewaan Aman');

        // Access via custom domain
        $customDomainCatalog = $this->get('/b/sewa.busanamewah.com');
        $customDomainCatalog->assertStatus(200);
        $customDomainCatalog->assertSee('Toko Busana Mewah');

        // Item show page should also omit "Didukung oleh"
        $itemResponse = $this->get('/b/toko-busana/gaun-pesta-merah');
        $itemResponse->assertStatus(200);
        $itemResponse->assertDontSee('Didukung oleh');

        // 2. Free Vendor has "Didukung oleh JagaSewa" visible
        $freeVendor = Vendor::factory()->create([
            'name' => 'Rental Kostum Murah',
            'slug' => 'rental-kostum',
            'white_label_enabled' => false,
            'is_active' => true,
        ]);

        $itemFree = Item::factory()->create([
            'vendor_id' => $freeVendor->id,
            'slug' => 'kostum-naruto',
            'name' => 'Kostum Naruto Shippuden',
            'is_active' => true,
        ]);

        $freeCatalogResponse = $this->get('/b/rental-kostum');
        $freeCatalogResponse->assertStatus(200);
        $freeCatalogResponse->assertSee('Didukung oleh');
        $freeCatalogResponse->assertSee('JagaSewa');
    }
}
