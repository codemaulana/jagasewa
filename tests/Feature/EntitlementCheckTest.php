<?php

namespace Tests\Feature;

use App\Models\BlacklistEntry;
use App\Models\Item;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\Billing\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T-43: Vendor Free mengakses fitur Pro diarahkan (redirect) ke halaman billing
     */
    public function test_free_vendor_accessing_pro_route_is_redirected_to_billing(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Default Free subscription
        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.reports.export'));

        $response->assertRedirect(route('vendor.billing.index'));
        $response->assertSessionHas('error');
    }

    /**
     * T-43: Request JSON / API fitur Pro oleh vendor Free mengembalikan 403 Forbidden
     */
    public function test_free_vendor_accessing_pro_json_route_returns_403_forbidden(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->getJson(route('vendor.reports.export'));

        $response->assertStatus(403)
            ->assertJson([
                'code' => 'UPGRADE_REQUIRED',
            ]);
    }

    /**
     * T-43: Vendor Pro dapat mengakses fitur Pro dengan sukses (HTTP 200)
     */
    public function test_pro_vendor_accessing_pro_route_returns_200_stream(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Active Pro subscription
        Subscription::factory()->pro()->create([
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.reports.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    /**
     * SEC-25: Entitlement dicek secara dinamis di server setiap request.
     * Jika subscription Pro kedaluwarsa (expired), akses Pro langsung tertutup seketika.
     */
    public function test_expired_pro_vendor_immediately_loses_access(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Pro subscription that has passed its validity period
        Subscription::factory()->expired()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.reports.export'));

        $response->assertRedirect(route('vendor.billing.index'));
    }

    /**
     * Batas kuota barang aktif pada paket Free maksimal 15 item (Prompt Gelombang 1)
     */
    public function test_free_vendor_active_item_limit_enforced_at_15(): void
    {
        $vendor = Vendor::factory()->create();
        $service = app(EntitlementService::class);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
        ]);

        // Create 14 active items -> can still add
        Item::factory()->count(14)->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);
        $this->assertTrue($service->canAddActiveItem($vendor));

        // Create 15th active item -> at limit
        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);
        // Now 15 active items, cannot add 16th
        $this->assertFalse($service->canAddActiveItem($vendor));

        // Inactive items do not count towards the 15 limit
        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => false,
        ]);
        $this->assertFalse($service->canAddActiveItem($vendor));
    }

    /**
     * Vendor Pro memiliki kuota barang aktif tanpa batas (unlimited)
     */
    public function test_pro_vendor_can_add_unlimited_active_items(): void
    {
        $vendor = Vendor::factory()->create();
        $service = app(EntitlementService::class);

        Subscription::factory()->pro()->create([
            'vendor_id' => $vendor->id,
        ]);

        // Create 10 active items
        Item::factory()->count(10)->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $this->assertTrue($service->canAddActiveItem($vendor));
    }

    /**
     * Batas entri blacklist pada paket Free maksimal 5 entri
     */
    public function test_free_vendor_blacklist_limit_enforced_at_5(): void
    {
        $vendor = Vendor::factory()->create();
        $service = app(EntitlementService::class);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
        ]);

        // Create 5 entries
        BlacklistEntry::factory()->count(5)->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->assertFalse($service->canAddBlacklistEntry($vendor));
    }

    /**
     * Vendor Pro memiliki kuota blacklist tanpa batas (unlimited)
     */
    public function test_pro_vendor_can_add_unlimited_blacklist_entries(): void
    {
        $vendor = Vendor::factory()->create();
        $service = app(EntitlementService::class);

        Subscription::factory()->pro()->create([
            'vendor_id' => $vendor->id,
        ]);

        // Create 8 entries
        BlacklistEntry::factory()->count(8)->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->assertTrue($service->canAddBlacklistEntry($vendor));
    }

    /**
     * Halaman katalog toko publik: Free menampilkan watermark, Pro menyembunyikan watermark (White-Label)
     */
    public function test_public_catalog_watermark_visibility(): void
    {
        // 1. Vendor Free -> shows "Didukung oleh JagaSewa"
        $vendorFree = Vendor::factory()->create(['slug' => 'toko-free']);
        Item::factory()->create(['vendor_id' => $vendorFree->id, 'is_active' => true]);
        Subscription::factory()->create(['vendor_id' => $vendorFree->id, 'plan' => Subscription::PLAN_FREE]);

        $resFree = $this->get(route('public.vendor.catalog', ['vendor_slug' => 'toko-free']));
        $resFree->assertStatus(200);
        $resFree->assertSee('Didukung oleh');
        $resFree->assertSee('JagaSewa');

        // 2. Vendor Pro -> white-label, no watermark
        $vendorPro = Vendor::factory()->create(['slug' => 'toko-pro']);
        Item::factory()->create(['vendor_id' => $vendorPro->id, 'is_active' => true]);
        Subscription::factory()->pro()->create(['vendor_id' => $vendorPro->id]);

        $resPro = $this->get(route('public.vendor.catalog', ['vendor_slug' => 'toko-pro']));
        $resPro->assertStatus(200);
        $resPro->assertDontSee('Didukung oleh');
    }

    /**
     * Halaman ringkasan billing vendor menampilkan status kuota dan paket saat ini
     */
    public function test_vendor_billing_index_page_renders_overview(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Paket Free');
        $response->assertSee('Rp 15.000');
        $response->assertSee('Rp 39.000');
        $response->assertSee('Rp 129.000');
    }
}
