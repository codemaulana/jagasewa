<?php

namespace Tests\Feature;

use App\Filament\Vendor\Pages\StoreSettings;
use App\Filament\Vendor\Resources\Items\Pages\CreateItem;
use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProFeaturesCustomWaTemplateTest extends TestCase
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
     * T-48 / BUG-06: Pro vendor can save custom WhatsApp templates and generate custom wa.me links with tags.
     */
    public function test_pro_vendor_can_save_and_use_custom_wa_templates(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Cosplay Jakarta Pro',
            'bank_account_info' => 'BCA 1234567890 a.n. Cosplay Jakarta',
            'is_active' => true,
        ]);

        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Activate Pro subscription
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

        // 1. Pro vendor saves custom WA template in StoreSettings
        Livewire::test(StoreSettings::class)
            ->assertSuccessful()
            ->fillForm([
                'custom_wa_templates.approved' => "Halo Kak {nama_penyewa}! Kostum {nama_barang} (Ref: {kode_booking}) telah disetujui oleh {nama_toko}. Silakan transfer biaya dan deposit ke:\n{rekening_toko}\nTerima kasih banyak!",
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendor->refresh();
        $this->assertNotNull($vendor->custom_wa_templates);
        $this->assertArrayHasKey('approved', $vendor->custom_wa_templates);

        // 2. Booking is created for this vendor
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Sailor Moon Premium',
            'price_per_day' => 150000,
            'deposit_amount' => 100000,
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Aisyah Putri',
            'phone' => '+6281234567890',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-CS-001',
            'total_price' => 300000,
            'deposit_paid' => 100000,
        ]);

        // 3. Generate chat URL
        $chatUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');

        $this->assertNotNull($chatUrl);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $chatUrl);

        $decodedMessage = rawurldecode(explode('?text=', $chatUrl)[1]);

        $this->assertStringContainsString('Halo Kak Aisyah Putri!', $decodedMessage);
        $this->assertStringContainsString('Kostum Sailor Moon Premium', $decodedMessage);
        $this->assertStringContainsString('Ref: JS-CS-001', $decodedMessage);
        $this->assertStringContainsString('Cosplay Jakarta Pro', $decodedMessage);
        $this->assertStringContainsString('BCA 1234567890 a.n. Cosplay Jakarta', $decodedMessage);
    }

    /**
     * T-48 / BUG-06: Free vendor always uses standard notification message and cannot save custom templates.
     */
    public function test_free_vendor_uses_standard_wa_templates(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Rental Gratisan',
            'bank_account_info' => 'Mandiri 999888777 a.n. Rental Gratisan',
            'is_active' => true,
        ]);

        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Attempting to submit custom template on Free tier
        Livewire::test(StoreSettings::class)
            ->fillForm([
                'name' => 'Rental Gratisan',
                'wa_number' => '+628111222333',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendor->refresh();
        $this->assertNull($vendor->custom_wa_templates);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kamera Canon 60D',
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '+6289876543210',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-STD-999',
        ]);

        $chatUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');
        $this->assertNotNull($chatUrl);

        $decodedMessage = rawurldecode(explode('?text=', $chatUrl)[1]);

        // Must contain standard JagaSewa text, not custom
        $this->assertStringContainsString('Kabar baik! Permohonan booking Anda di Rental Gratisan telah DISETUJUI', $decodedMessage);
        $this->assertStringContainsString('Mandiri 999888777 a.n. Rental Gratisan', $decodedMessage);
    }

    /**
     * T-48 / BUG-06: Pro vendor can customize return reminder message.
     */
    public function test_pro_vendor_custom_return_reminder_template(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Genshin Rent Pro',
            'custom_wa_templates' => [
                'return_reminder' => 'Halo {nama_penyewa}, kostum {nama_barang} (Kode: {kode_booking}) ditunggu kepulangannya ya hari ini!',
            ],
            'is_active' => true,
        ]);

        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Hu Tao Fullset Wigs',
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Citra Kirana',
            'phone' => '+6281312345678',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-HUTAO-12',
        ]);

        $chatUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'return_reminder');
        $this->assertNotNull($chatUrl);

        $decodedMessage = rawurldecode(explode('?text=', $chatUrl)[1]);
        $this->assertEquals('Halo Citra Kirana, kostum Hu Tao Fullset Wigs (Kode: JS-HUTAO-12) ditunggu kepulangannya ya hari ini!', $decodedMessage);
    }

    /**
     * T-48 / BUG-06: Photo quota enforcement - Free tier max 5 photos, Pro tier allows up to 8 photos.
     */
    public function test_photo_quota_enforcement_free_vs_pro(): void
    {
        // 1. Free vendor
        $freeVendor = Vendor::factory()->create(['is_active' => true]);
        $freeUser = VendorUser::factory()->create(['vendor_id' => $freeVendor->id]);

        $this->actingAs($freeUser, 'vendor');
        session(['vendor_id' => $freeVendor->id]);

        // Attempt to upload 6 photos on Free tier (limit is 5)
        $sixPhotos = [];
        for ($i = 1; $i <= 6; $i++) {
            $sixPhotos[] = UploadedFile::fake()->image("photo_{$i}.jpg", 400, 400);
        }

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Barang Melebihi Kuota Foto',
                'slug' => 'barang-melebihi-kuota-foto',
                'size' => 'All Size',
                'price_per_day' => 100000,
                'deposit_amount' => 50000,
                'fine_per_day' => 20000,
                'photo_paths' => $sixPhotos,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasErrors(['data.photo_paths']);

        // 2. Pro vendor
        $proVendor = Vendor::factory()->create(['is_active' => true]);
        $proUser = VendorUser::factory()->create(['vendor_id' => $proVendor->id]);

        Subscription::create([
            'vendor_id' => $proVendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $this->actingAs($proUser, 'vendor');
        session(['vendor_id' => $proVendor->id]);

        // Pro vendor uploading 8 photos (allowed)
        $eightPhotos = [];
        for ($i = 1; $i <= 8; $i++) {
            $eightPhotos[] = UploadedFile::fake()->image("pro_photo_{$i}.jpg", 400, 400);
        }

        Livewire::test(CreateItem::class)
            ->fillForm([
                'name' => 'Barang Pro Delapan Foto',
                'slug' => 'barang-pro-delapan-foto',
                'size' => 'All Size',
                'price_per_day' => 250000,
                'deposit_amount' => 150000,
                'fine_per_day' => 50000,
                'photo_paths' => $eightPhotos,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $savedItem = Item::where('slug', 'barang-pro-delapan-foto')->first();
        $this->assertNotNull($savedItem);
        $this->assertCount(8, $savedItem->photo_paths);
    }
}
