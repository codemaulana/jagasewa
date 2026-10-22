<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\Dashboard;
use App\Filament\Vendor\Pages\Reports;
use App\Filament\Vendor\Pages\StoreSettings;
use App\Filament\Vendor\Pages\VendorBookingCalendar;
use App\Filament\Vendor\Resources\BlacklistEntries\BlacklistEntryResource;
use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Filament\Vendor\Resources\Items\ItemResource;
use App\Filament\Vendor\Resources\Renters\RenterResource;
use App\Filament\Vendor\Resources\VendorUsers\VendorUserResource;
use Filament\Facades\Filament;
use Tests\TestCase;

class VendorAppShellTest extends TestCase
{
    /**
     * Test 1: Verify all navigation items are grouped strictly into:
     * - Operasional (Dashboard, Daftar Booking, Kalender Sewa, Katalog Barang)
     * - Pelanggan & Keamanan (Pelanggan, Blacklist)
     * - Bisnis (Laporan & Analitik, Kelola Staf, Pengaturan Toko)
     */
    public function test_navigation_groups_and_sorting_match_specification(): void
    {
        // 1. Operasional Group
        $this->assertEquals('Operasional', Dashboard::getNavigationGroup());
        $this->assertEquals(1, Dashboard::getNavigationSort());
        $this->assertEquals('Dashboard', Dashboard::getNavigationLabel());

        $this->assertEquals('Operasional', BookingResource::getNavigationGroup());
        $this->assertEquals(2, BookingResource::getNavigationSort());
        $this->assertEquals('Daftar Booking', BookingResource::getNavigationLabel());

        $this->assertEquals('Operasional', VendorBookingCalendar::getNavigationGroup());
        $this->assertEquals(3, VendorBookingCalendar::getNavigationSort());
        $this->assertEquals('Kalender Sewa', VendorBookingCalendar::getNavigationLabel());

        $this->assertEquals('Operasional', ItemResource::getNavigationGroup());
        $this->assertEquals(4, ItemResource::getNavigationSort());
        $this->assertEquals('Katalog Barang', ItemResource::getNavigationLabel());

        // 2. Pelanggan & Keamanan Group
        $this->assertEquals('Pelanggan & Keamanan', RenterResource::getNavigationGroup());
        $this->assertEquals(1, RenterResource::getNavigationSort());
        $this->assertEquals('Pelanggan', RenterResource::getNavigationLabel());

        $this->assertEquals('Pelanggan & Keamanan', BlacklistEntryResource::getNavigationGroup());
        $this->assertEquals(2, BlacklistEntryResource::getNavigationSort());
        $this->assertEquals('Blacklist', BlacklistEntryResource::getNavigationLabel());

        // 3. Bisnis Group
        $this->assertEquals('Bisnis', Reports::getNavigationGroup());
        $this->assertEquals(1, Reports::getNavigationSort());
        $this->assertEquals('Laporan & Analitik', Reports::getNavigationLabel());

        $this->assertEquals('Bisnis', VendorUserResource::getNavigationGroup());
        $this->assertEquals(2, VendorUserResource::getNavigationSort());
        $this->assertEquals('Kelola Staf', VendorUserResource::getNavigationLabel());

        $this->assertEquals('Bisnis', StoreSettings::getNavigationGroup());
        $this->assertEquals(3, StoreSettings::getNavigationSort());
        $this->assertEquals('Pengaturan Toko', StoreSettings::getNavigationLabel());
    }

    /**
     * Test 2: Verify 'Ekspor Laporan CSV' and 'Paket & Kuota Toko' are removed from main navigation.
     */
    public function test_csv_export_and_billing_removed_from_main_navigation(): void
    {
        $panel = Filament::getPanel('vendor');
        $navigationItems = $panel->getNavigationItems();

        $labels = array_map(fn ($item) => is_string($item->getLabel()) ? $item->getLabel() : '', $navigationItems);

        $this->assertNotContains('Ekspor Laporan CSV', $labels, 'Ekspor Laporan CSV should not be in main navigation');
        $this->assertNotContains('Paket & Kuota Toko', $labels, 'Paket & Kuota Toko should not be in main navigation');
    }

    /**
     * Test 3: Verify bottom nav bar contains 4 key items + 'Lainnya' trigger for mobile drawer.
     */
    public function test_mobile_bottom_nav_contains_required_items_and_drawer_trigger(): void
    {
        $viewPath = resource_path('views/filament/vendor/layout/bottom-nav.blade.php');
        $this->assertFileExists($viewPath);
        $content = file_get_contents($viewPath);

        $this->assertStringContainsString('Dashboard', $content);
        $this->assertStringContainsString('Booking', $content);
        $this->assertStringContainsString('Kalender', $content);
        $this->assertStringContainsString('Katalog', $content);
        $this->assertStringContainsString('Lainnya', $content);
        $this->assertStringContainsString('$store.sidebar.open()', $content, 'Lainnya button must trigger $store.sidebar.open()');
    }

    /**
     * Test 4: Verify sidebar plan card view exists and topbar badge is configured.
     */
    public function test_sidebar_plan_card_and_topbar_badge_views_exist(): void
    {
        $sidebarPlanCard = resource_path('views/filament/vendor/layout/sidebar-plan-card.blade.php');
        $this->assertFileExists($sidebarPlanCard);
        $sidebarContent = file_get_contents($sidebarPlanCard);

        $this->assertStringContainsString('fi-sidebar-plan-card', $sidebarContent);
        $this->assertStringContainsString('Paket Pro', $sidebarContent);
        $this->assertStringContainsString('Paket Free', $sidebarContent);
        $this->assertStringContainsString('Kelola', $sidebarContent);

        $topbarBadge = resource_path('views/filament/vendor/layout/topbar-badge.blade.php');
        $this->assertFileExists($topbarBadge);
        $topbarContent = file_get_contents($topbarBadge);

        $this->assertStringContainsString('Toko Publik', $topbarContent);
    }
}
