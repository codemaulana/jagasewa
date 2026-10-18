<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\Auth\Register;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/vendor/login');

        $response->assertStatus(200);
    }

    public function test_registration_page_can_be_rendered(): void
    {
        $response = $this->get('/vendor/register');

        $response->assertStatus(200);
    }

    public function test_vendor_can_login_via_post_request(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'last_login_at' => null,
        ]);

        $response = $this->post('/vendor/login', [
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/vendor');
        $this->assertAuthenticatedAs($user, 'vendor');
        $this->assertEquals($vendor->id, session('vendor_id'));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_vendor_login_fails_with_invalid_credentials(): void
    {
        $vendor = Vendor::factory()->create();
        VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->from('/vendor/login')->post('/vendor/login', [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/vendor/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest('vendor');
    }

    public function test_deactivated_vendor_cannot_login(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => false]);
        VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->from('/vendor/login')->post('/vendor/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/vendor/login');
        $response->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi support.']);
        $this->assertGuest('vendor');
    }

    public function test_vendor_can_register_via_post_request(): void
    {
        $response = $this->post('/vendor/register', [
            'store_name' => 'Rental Kamera Cepat',
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@camerarental.test',
            'wa_number' => '081298765432',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/vendor');

        $this->assertDatabaseHas('vendors', [
            'name' => 'Rental Kamera Cepat',
            'slug' => 'rental-kamera-cepat',
            'email' => 'ahmad@camerarental.test',
            'is_active' => 1,
        ]);

        $vendor = Vendor::where('slug', 'rental-kamera-cepat')->first();
        $this->assertNotNull($vendor);

        $this->assertDatabaseHas('vendor_users', [
            'vendor_id' => $vendor->id,
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@camerarental.test',
            'role' => 'owner',
        ]);

        $user = VendorUser::where('email', 'ahmad@camerarental.test')->first();
        $this->assertAuthenticatedAs($user, 'vendor');
        $this->assertEquals($vendor->id, session('vendor_id'));
    }

    public function test_vendor_can_logout(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')->post('/vendor/logout');

        $response->assertRedirect('/vendor/login');
        $this->assertGuest('vendor');
    }

    public function test_unauthenticated_user_accessing_vendor_dashboard_is_redirected_to_login(): void
    {
        $response = $this->get('/vendor');

        $response->assertRedirect('/vendor/login');
    }

    public function test_filament_livewire_login_component(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'livewire@example.com',
            'password' => 'secret123',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'livewire@example.com',
                'password' => 'secret123',
            ])
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/vendor');

        $this->assertAuthenticatedAs($user, 'vendor');
        $this->assertEquals($vendor->id, session('vendor_id'));
    }

    public function test_filament_livewire_register_component(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'store_name' => 'Studio Kostum Mewah',
                'name' => 'Siti Aminah',
                'email' => 'siti@kostummewah.test',
                'wa_number' => '081345678901',
                'password' => 'secret1234',
                'passwordConfirmation' => 'secret1234',
            ])
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/vendor');

        $vendor = Vendor::where('slug', 'studio-kostum-mewah')->first();
        $this->assertNotNull($vendor);

        $user = VendorUser::where('email', 'siti@kostummewah.test')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user, 'vendor');
        $this->assertEquals($vendor->id, session('vendor_id'));
    }
}
