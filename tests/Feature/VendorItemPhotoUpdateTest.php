<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Items\Pages\CreateItem;
use App\Filament\Vendor\Resources\Items\Pages\EditItem;
use App\Models\Item;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorItemPhotoUpdateTest extends TestCase
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
     * Vendor can upload initial photos and replace/update them via EditItem form.
     */
    public function test_vendor_can_upload_and_replace_photos_on_item(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $initialPhoto1 = UploadedFile::fake()->image('photo1.jpg', 600, 600);
        $initialPhoto2 = UploadedFile::fake()->image('photo2.jpg', 600, 600);

        // 1. Create item with 2 photos
        $component = Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Kostum Genshin Xiao',
                'slug' => 'kostum-genshin-xiao',
                'description' => 'Kostum Xiao lengkap dengan spear.',
                'size' => 'L',
                'price_per_day' => 120000,
                'deposit_amount' => 50000,
                'fine_per_day' => 25000,
                'buffer_before' => 1,
                'buffer_after' => 1,
                'photo_paths' => [$initialPhoto1, $initialPhoto2],
                'is_active' => true,
            ])
            ->call('create');

        $component->assertHasNoFormErrors();

        $item = Item::where('slug', 'kostum-genshin-xiao')->first();
        $this->assertNotNull($item);
        $this->assertIsArray($item->photo_paths);
        $this->assertCount(2, $item->photo_paths);

        $oldPhotos = $item->photo_paths;

        // 2. Edit item: replace with a single new photo
        $newPhoto = UploadedFile::fake()->image('photo_new.jpg', 600, 600);

        Livewire::test(EditItem::class, ['record' => $item->getRouteKey()])
            ->fillForm([
                'name' => 'Kostum Genshin Xiao (Updated)',
                'photo_paths' => [$newPhoto],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $item->refresh();
        $this->assertEquals('Kostum Genshin Xiao (Updated)', $item->name);
        $this->assertIsArray($item->photo_paths);
        $this->assertCount(1, $item->photo_paths);
        $this->assertNotEquals($oldPhotos, $item->photo_paths);
    }

    /**
     * Pro vendor can upload up to 8 photos for an item.
     */
    public function test_pro_vendor_can_upload_up_to_eight_photos(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Active Pro tier
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $photos = [];
        for ($i = 1; $i <= 8; $i++) {
            $photos[] = UploadedFile::fake()->image("outfit_{$i}.jpg", 600, 600);
        }

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Kamera Hasselblad Pro Set',
                'slug' => 'kamera-hasselblad-pro-set',
                'size' => 'All Size',
                'price_per_day' => 850000,
                'deposit_amount' => 500000,
                'fine_per_day' => 150000,
                'buffer_before' => 1,
                'buffer_after' => 2,
                'photo_paths' => $photos,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = Item::where('slug', 'kamera-hasselblad-pro-set')->first();
        $this->assertNotNull($item);
        $this->assertCount(8, $item->photo_paths);
    }

    /**
     * Item deletion cleans up physical image files from storage disk.
     */
    public function test_item_deletion_cleans_up_stored_photos(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $disk = Storage::disk('s3');

        $disk->put('item_photos/del_photo1.jpg', 'fake-image-bytes-1');
        $disk->put('item_photos/del_photo2.jpg', 'fake-image-bytes-2');

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'photo_paths' => ['item_photos/del_photo1.jpg', 'item_photos/del_photo2.jpg'],
        ]);

        $this->assertTrue($disk->exists('item_photos/del_photo1.jpg'));
        $this->assertTrue($disk->exists('item_photos/del_photo2.jpg'));

        $item->delete();

        $this->assertFalse($disk->exists('item_photos/del_photo1.jpg'));
        $this->assertFalse($disk->exists('item_photos/del_photo2.jpg'));
    }

    /**
     * Content Security Policy header includes blob: and S3 origins for preview and upload.
     */
    public function test_csp_header_allows_blob_and_s3_storage_origins(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('blob:', $csp);
        $this->assertStringContainsString('https://*.backblazeb2.com', $csp);
        $this->assertStringContainsString('https://*.amazonaws.com', $csp);
    }
}
