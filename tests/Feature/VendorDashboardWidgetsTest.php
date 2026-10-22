<?php

namespace Tests\Feature;

use App\Filament\AvatarProviders\InitialAvatarProvider;
use App\Filament\Vendor\Widgets\OnboardingChecklistWidget;
use App\Filament\Vendor\Widgets\RentalStatsOverviewWidget;
use App\Filament\Vendor\Widgets\StoreLinkAndPlanWidget;
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

class VendorDashboardWidgetsTest extends TestCase
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
     * T-49 / BUG-03: Unauthenticated user redirected to login.
     */
    public function test_unauthenticated_user_redirected_from_dashboard(): void
    {
        $this->get('/vendor')
            ->assertRedirect('/vendor/login');
    }

    /**
     * T-49 / BUG-03: Dashboard renders custom rental widgets and does not render default Filament widgets.
     */
    public function test_dashboard_renders_custom_widgets_and_removes_default_filament_widgets(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Koleksi Kostum Cantik',
            'slug' => 'koleksi-kostum-cantik',
            'is_active' => true,
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $response = $this->get('/vendor');

        $response->assertSuccessful();

        // Custom widgets are present
        $response->assertSee('Tautan Etalase Toko Publik');
        $response->assertSee('/b/koleksi-kostum-cantik');
        $response->assertSee('Status Langganan');
        $response->assertSee('Tugas Hari Ini');
        $response->assertSee('Pendapatan Bulan Ini');
        $response->assertSee('Menunggu Konfirmasi');
        $response->assertSee('Panduan Memulai Toko Rental');

        // Default Filament widgets are removed
        $response->assertDontSee('Welcome to Filament');
        $response->assertDontSee('Documentation & Community');
    }

    /**
     * T-49 / BUG-03: Rental stats overview widget reflects actual vendor database records with vendor isolation.
     */
    public function test_dashboard_statistics_reflect_actual_vendor_records_with_isolation(): void
    {
        $vendorA = Vendor::factory()->create(['name' => 'Vendor A', 'is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['name' => 'Vendor B', 'is_active' => true]);

        $itemA = Item::factory()->create(['vendor_id' => $vendorA->id]);
        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);
        $renter = Renter::factory()->create();

        // Vendor A: 2 Pending, 1 Active, 1 Returned
        Booking::factory()->create(['vendor_id' => $vendorA->id, 'item_id' => $itemA->id, 'renter_id' => $renter->id, 'status' => Booking::STATUS_PENDING]);
        Booking::factory()->create(['vendor_id' => $vendorA->id, 'item_id' => $itemA->id, 'renter_id' => $renter->id, 'status' => Booking::STATUS_PENDING]);
        Booking::factory()->create(['vendor_id' => $vendorA->id, 'item_id' => $itemA->id, 'renter_id' => $renter->id, 'status' => Booking::STATUS_ACTIVE]);
        Booking::factory()->create(['vendor_id' => $vendorA->id, 'item_id' => $itemA->id, 'renter_id' => $renter->id, 'status' => Booking::STATUS_RETURNED]);

        // Vendor B: 5 Pending bookings (should NOT leak to Vendor A's dashboard)
        Booking::factory()->count(5)->create(['vendor_id' => $vendorB->id, 'item_id' => $itemB->id, 'renter_id' => $renter->id, 'status' => Booking::STATUS_PENDING]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        $component = Livewire::test(RentalStatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Tugas Hari Ini')
            ->assertSee('Pendapatan Bulan Ini')
            ->assertSee('Menunggu Konfirmasi');

        /** @var RentalStatsOverviewWidget $widget */
        $widget = $component->instance();
        $stats = $widget->getStats();

        // Vendor A counts: 0 today tasks (start/end not today), revenue string, 2 pending
        $this->assertEquals('Tugas Hari Ini', $stats[0]->getLabel());
        $this->assertEquals('Pendapatan Bulan Ini', $stats[1]->getLabel());
        $this->assertEquals('Menunggu Konfirmasi', $stats[2]->getLabel());
        $this->assertEquals(2, $stats[2]->getValue());
    }

    /**
     * T-49 / BUG-03: Store link and plan widget reflects Free vs Pro tier status.
     */
    public function test_store_link_and_plan_widget_displays_pro_status(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Vendor Pro Jaya',
            'slug' => 'vendor-pro-jaya',
            'is_active' => true,
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(StoreLinkAndPlanWidget::class)
            ->assertSuccessful()
            ->assertSee('PRO')
            ->assertSee('Tanpa Batas');
    }

    /**
     * T-49 / BUG-03: Onboarding checklist tracks setup steps.
     */
    public function test_onboarding_checklist_calculates_steps(): void
    {
        $vendor = Vendor::factory()->create([
            'description' => 'Rental cosplay profesional',
            'is_active' => true,
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Item::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(OnboardingChecklistWidget::class)
            ->assertSuccessful()
            ->assertSee('2/3 Selesai');
    }

    /**
     * T-49 / BUG-03: Initial avatar provider generates clean SVG data URI and avoids broken external URLs.
     */
    public function test_initial_avatar_provider_generates_valid_avatar(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Studio Foto']);
        $user = VendorUser::factory()->create([
            'name' => 'Khaerul Umam',
            'vendor_id' => $vendor->id,
        ]);

        $provider = new InitialAvatarProvider;
        $avatarUrl = $provider->get($user);

        $this->assertStringStartsWith('data:image/svg+xml;utf8,', $avatarUrl);
        $this->assertStringContainsString('K', rawurldecode($avatarUrl));
    }
}
