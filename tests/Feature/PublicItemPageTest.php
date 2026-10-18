<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicItemPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_public_user_can_view_active_item_page(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Studio Cosplay Akihabara',
            'slug' => 'studio-akihabara',
            'is_active' => true,
        ]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Raiden Shogun Genshin Impact',
            'slug' => 'raiden-shogun',
            'size' => 'M',
            'included_items' => ['Kostum', 'Wig Styled', 'Timbangan Naginata'],
            'care_instructions' => 'Dry clean only.',
            'price_per_day' => 175000,
            'deposit_amount' => 80000,
            'fine_per_day' => 40000,
            'buffer_before' => 1,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        $response = $this->get('/b/studio-akihabara/raiden-shogun');

        $response->assertStatus(200);
        $response->assertSee('Kostum Raiden Shogun Genshin Impact');
        $response->assertSee('Studio Cosplay Akihabara');
        $response->assertSee('Ukuran: M');
        $response->assertSee('Rp 175.000');
        $response->assertSee('Rp 80.000');
        $response->assertSee('Rp 40.000');
        $response->assertSee('Timbangan Naginata');
        $response->assertSee('Dry clean only.');
        $response->assertSee('1 Hari');
        $response->assertSee('2 Hari');
    }

    public function test_photo_signed_urls_are_generated_with_validity(): void
    {
        Storage::fake('s3');

        $vendor = Vendor::factory()->create([
            'slug' => 'rental-kamera-bandung',
            'is_active' => true,
        ]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'slug' => 'sony-a7-iv',
            'photo_paths' => ['item_photos/sony1.jpg', 'item_photos/sony2.jpg'],
            'is_active' => true,
        ]);

        $response = $this->get('/b/rental-kamera-bandung/sony-a7-iv');

        $response->assertStatus(200);

        // Verify signed URL with expiration query is present in the rendered HTML
        $content = $response->getContent();
        $this->assertStringContainsString('item_photos/sony1.jpg', $content);
        $this->assertMatchesRegularExpression('/(expiration=|X-Amz-Expires=)/', $content);
    }

    public function test_inactive_item_returns_404(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-aktif',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'slug' => 'barang-nonaktif',
            'is_active' => false,
        ]);

        $response = $this->get('/b/vendor-aktif/barang-nonaktif');

        $response->assertStatus(404);
    }

    public function test_inactive_vendor_returns_404(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-nonaktif',
            'is_active' => false,
        ]);

        Item::factory()->create([
            'vendor_id' => $vendor->id,
            'slug' => 'barang-apapun',
            'is_active' => true,
        ]);

        $response = $this->get('/b/vendor-nonaktif/barang-apapun');

        $response->assertStatus(404);
    }

    public function test_non_existent_item_slug_returns_404(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'vendor-ada',
            'is_active' => true,
        ]);

        $response = $this->get('/b/vendor-ada/barang-fiktif');

        $response->assertStatus(404);
    }

    public function test_non_existent_vendor_slug_returns_404(): void
    {
        $response = $this->get('/b/vendor-fiktif/barang-apapun');

        $response->assertStatus(404);
    }

    public function test_item_belonging_to_another_vendor_returns_404(): void
    {
        $vendorA = Vendor::factory()->create([
            'slug' => 'vendor-a',
            'is_active' => true,
        ]);

        $vendorB = Vendor::factory()->create([
            'slug' => 'vendor-b',
            'is_active' => true,
        ]);

        // Item belongs to vendor A
        Item::factory()->create([
            'vendor_id' => $vendorA->id,
            'slug' => 'kostum-eksklusif-a',
            'is_active' => true,
        ]);

        // Requesting item using vendor B's slug returns 404
        $response = $this->get('/b/vendor-b/kostum-eksklusif-a');

        $response->assertStatus(404);
    }
}
