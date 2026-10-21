<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\StoreSettings;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorStoreSettingsTest extends TestCase
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
     * T-47 / BUG-04: Unauthenticated user cannot access store settings page.
     */
    public function test_unauthenticated_user_cannot_access_store_settings(): void
    {
        $this->get('/vendor/store-settings')
            ->assertRedirect('/vendor/login');
    }

    /**
     * T-47 / BUG-04: Authenticated vendor can render and fill store settings form.
     */
    public function test_vendor_can_render_and_update_store_settings(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Toko Rental Asli',
            'wa_number' => '+628111122233',
            'description' => null,
            'address' => null,
            'bank_account_info' => null,
            'is_active' => true,
        ]);

        $user = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $logoFile = UploadedFile::fake()->image('store_logo.png', 400, 400);
        $bannerFile = UploadedFile::fake()->image('store_banner.jpg', 1200, 400);

        Livewire::test(StoreSettings::class)
            ->assertSuccessful()
            ->assertFormSet([
                'name' => 'Toko Rental Asli',
                'wa_number' => '+628111122233',
            ])
            ->fillForm([
                'name' => 'Toko Rental Sukses Makmur',
                'wa_number' => '+6289988776655',
                'description' => 'Pusat sewa kostum dan kamera terlengkap di Jakarta.',
                'address' => 'Jl. Boulevard Raya No. 45, Jakarta Selatan',
                'bank_account_info' => "BCA 8881234567 a.n. Sukses Makmur\nMandiri 123000999 a.n. Sukses Makmur",
                'logo_path' => $logoFile,
                'banner_path' => $bannerFile,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendor->refresh();

        $this->assertEquals('Toko Rental Sukses Makmur', $vendor->name);
        $this->assertEquals('+6289988776655', $vendor->wa_number);
        $this->assertEquals('Pusat sewa kostum dan kamera terlengkap di Jakarta.', $vendor->description);
        $this->assertEquals('Jl. Boulevard Raya No. 45, Jakarta Selatan', $vendor->address);
        $this->assertStringContainsString('BCA 8881234567', $vendor->bank_account_info);
        $this->assertNotNull($vendor->logo_path);
        $this->assertNotNull($vendor->banner_path);
    }

    /**
     * T-47 / BUG-04: Replacing store media cleans up previous files from storage.
     */
    public function test_replacing_store_media_cleans_up_old_storage_file(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Studio Foto Rental',
            'wa_number' => '+6281234567890',
            'is_active' => true,
        ]);

        $user = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $initialLogo = UploadedFile::fake()->image('old_logo.png', 300, 300);

        Livewire::test(StoreSettings::class)
            ->fillForm([
                'name' => 'Studio Foto Rental',
                'wa_number' => '+6281234567890',
                'logo_path' => $initialLogo,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendor->refresh();
        $oldLogoPath = $vendor->logo_path;
        $this->assertNotNull($oldLogoPath);

        // Remove logo
        Livewire::test(StoreSettings::class)
            ->fillForm([
                'name' => 'Studio Foto Rental',
                'wa_number' => '+6281234567890',
                'logo_path' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendor->refresh();
        $this->assertNull($vendor->logo_path);
    }

    /**
     * T-47 / BUG-04: Public catalog page reflects updated store profile and WhatsApp link.
     */
    public function test_public_catalog_page_renders_updated_store_profile_and_contact(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Cosplay Wonderland',
            'slug' => 'cosplay-wonderland',
            'wa_number' => '+6281298765432',
            'description' => 'Penyedia kostum anime terpercaya.',
            'address' => 'Gedung Cyber Lantai 3',
            'is_active' => true,
        ]);

        $response = $this->get('/b/'.$vendor->slug);

        $response->assertSuccessful();
        $response->assertSee('Cosplay Wonderland');
        $response->assertSee('Penyedia kostum anime terpercaya.');
        $response->assertSee('Gedung Cyber Lantai 3');
        // Contact WhatsApp button present with normalized number 6281298765432
        $response->assertSee('wa.me/6281298765432');
    }

    /**
     * T-47 / SEC-08: Vendor isolation ensures changes do not affect another vendor.
     */
    public function test_vendor_isolation_updating_settings_only_affects_own_vendor(): void
    {
        $vendorA = Vendor::factory()->create([
            'name' => 'Toko Vendor A',
            'wa_number' => '+628111111111',
            'description' => 'Deskripsi Toko A',
            'is_active' => true,
        ]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create([
            'name' => 'Toko Vendor B',
            'wa_number' => '+628222222222',
            'description' => 'Deskripsi Toko B',
            'is_active' => true,
        ]);

        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(StoreSettings::class)
            ->fillForm([
                'name' => 'Toko Vendor A Diperbarui',
                'wa_number' => '+628333333333',
                'description' => 'Deskripsi Toko A Baru',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendorA->refresh();
        $vendorB->refresh();

        $this->assertEquals('Toko Vendor A Diperbarui', $vendorA->name);
        $this->assertEquals('+628333333333', $vendorA->wa_number);
        $this->assertEquals('Deskripsi Toko A Baru', $vendorA->description);

        // Vendor B must remain completely unchanged
        $this->assertEquals('Toko Vendor B', $vendorB->name);
        $this->assertEquals('+628222222222', $vendorB->wa_number);
        $this->assertEquals('Deskripsi Toko B', $vendorB->description);
    }
}
