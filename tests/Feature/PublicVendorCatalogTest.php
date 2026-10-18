<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicVendorCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_public_user_can_view_active_vendor_catalog(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Studio Akihabara Cosplay',
            'slug' => 'studio-akihabara',
            'is_active' => true,
        ]);

        $item1 = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Raiden Shogun (M)',
            'slug' => 'raiden-shogun',
            'size' => 'M',
            'price_per_day' => 175000,
            'deposit_amount' => 80000,
            'buffer_before' => 1,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        $item2 = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Hu Tao Fullset (L)',
            'slug' => 'hu-tao',
            'size' => 'L',
            'price_per_day' => 150000,
            'deposit_amount' => 50000,
            'buffer_before' => 1,
            'buffer_after' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/b/studio-akihabara');

        $response->assertStatus(200);
        $response->assertSee('Studio Akihabara Cosplay');
        $response->assertSee('@ studio-akihabara');
        $response->assertSee('Kostum Raiden Shogun (M)');
        $response->assertSee('Kostum Hu Tao Fullset (L)');
        $response->assertSee('Rp 175.000');
        $response->assertSee('Rp 150.000');
        $response->assertSee('2 Pilihan Rental');
        $response->assertSee('/b/studio-akihabara/raiden-shogun');
        $response->assertSee('/b/studio-akihabara/hu-tao');
    }

    public function test_inactive_vendor_returns_404(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-tutup',
            'is_active' => false,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $response = $this->get('/b/vendor-tutup');

        $response->assertStatus(404);
    }

    public function test_non_existent_vendor_returns_404(): void
    {
        $response = $this->get('/b/vendor-fiktif-yang-tidak-pernah-ada');

        $response->assertStatus(404);
    }

    public function test_inactive_items_are_hidden_from_catalog(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-etalase',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Barang Siap Disewa',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Barang Rusak Sedang Diperbaiki',
            'is_active' => false,
        ]);

        $response = $this->get('/b/vendor-etalase');

        $response->assertStatus(200);
        $response->assertSee('Barang Siap Disewa');
        $response->assertDontSee('Barang Rusak Sedang Diperbaiki');
        $response->assertSee('1 Pilihan Rental');
    }

    public function test_other_vendors_items_do_not_appear_in_catalog(): void
    {
        $vendorA = Vendor::factory()->create([
            'name' => 'Vendor A Rental',
            'slug' => 'vendor-a',
            'is_active' => true,
        ]);

        $vendorB = Vendor::factory()->create([
            'name' => 'Vendor B Rental',
            'slug' => 'vendor-b',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'name' => 'Kamera Milik Vendor A',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendorB->id,
            'name' => 'Kamera Rahasia Vendor B',
            'is_active' => true,
        ]);

        $response = $this->get('/b/vendor-a');

        $response->assertStatus(200);
        $response->assertSee('Kamera Milik Vendor A');
        $response->assertDontSee('Kamera Rahasia Vendor B');
    }

    public function test_items_photos_have_valid_signed_urls(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'katalog-foto',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Berfoto',
            'slug' => 'kostum-berfoto',
            'photo_paths' => ['items/thumbnail.jpg', 'items/detail.jpg'],
            'is_active' => true,
        ]);

        $response = $this->get('/b/katalog-foto');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('items/thumbnail.jpg', $content);
        $this->assertMatchesRegularExpression('/(expiration=|X-Amz-Expires=)/', $content);
    }

    public function test_catalog_pagination_works(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-banyak-barang',
            'is_active' => true,
        ]);

        // Create 15 items
        for ($i = 1; $i <= 15; $i++) {
            Item::factory()->create([
                'vendor_id' => $vendor->id,
                'name' => "Barang Rental Nomor $i",
                'is_active' => true,
            ]);
        }

        $page1Response = $this->get('/b/vendor-banyak-barang');
        $page1Response->assertStatus(200);
        $page1Response->assertSee('15 Pilihan Rental');

        $page2Response = $this->get('/b/vendor-banyak-barang?page=2');
        $page2Response->assertStatus(200);
    }

    public function test_empty_catalog_shows_empty_state(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-kosong',
            'is_active' => true,
        ]);

        $response = $this->get('/b/vendor-kosong');

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Barang Rental');
        $response->assertSee('0 Pilihan Rental');
    }
}
