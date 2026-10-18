<?php

namespace Tests\Feature;

use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVendorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_access_vendor_list_page(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/vendors');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_admin_vendors(): void
    {
        $response = $this->get('/admin/vendors');

        $response->assertRedirect('/admin/login');
    }

    public function test_vendor_user_cannot_access_admin_panel(): void
    {
        $vendor = Vendor::factory()->create();
        $vendorUser = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($vendorUser, 'vendor')->get('/admin/vendors');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_list_vendors_via_livewire(): void
    {
        $admin = User::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Cosplay Rental Jogja']);

        $this->actingAs($admin, 'web');

        Livewire::test(ListVendors::class)
            ->assertCanSeeTableRecords([$vendor]);
    }

    public function test_admin_can_toggle_vendor_is_active_and_audit_log_is_recorded(): void
    {
        $admin = User::factory()->create();
        $vendor = Vendor::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'web');

        // Deactivate vendor
        $vendor->update(['is_active' => false]);

        $this->assertFalse($vendor->fresh()->is_active);

        // Verify audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'vendor_deactivated',
            'entity_type' => 'vendor',
            'entity_id' => (string) $vendor->id,
        ]);

        // Reactivate vendor
        $vendor->update(['is_active' => true]);

        $this->assertTrue($vendor->fresh()->is_active);

        // Verify audit log recorded for reactivation
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'vendor_activated',
            'entity_type' => 'vendor',
            'entity_id' => (string) $vendor->id,
        ]);
    }

    public function test_deactivating_vendor_blocks_vendor_login_and_shows_error_message(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'vendor@cosplay.test',
            'password' => 'secret123',
        ]);

        // Admin deactivates the vendor
        $vendor->update(['is_active' => false]);

        // Vendor attempts to login
        $response = $this->from('/vendor/login')->post('/vendor/login', [
            'email' => 'vendor@cosplay.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/vendor/login');
        $response->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi support.']);
        $this->assertGuest('vendor');

        // Reactivate vendor
        $vendor->update(['is_active' => true]);

        // Vendor logs in successfully
        $responseSuccess = $this->post('/vendor/login', [
            'email' => 'vendor@cosplay.test',
            'password' => 'secret123',
        ]);

        $responseSuccess->assertRedirect('/vendor');
        $this->assertAuthenticatedAs($user, 'vendor');
    }
}
