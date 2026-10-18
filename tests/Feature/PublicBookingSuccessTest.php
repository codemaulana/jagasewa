<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicBookingSuccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_public_user_can_view_booking_success_page(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Studio Cosplay Akihabara',
            'slug' => 'studio-akihabara',
            'wa_number' => '081234567890',
            'is_active' => true,
        ]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Raiden Shogun (M)',
            'price_per_day' => 150000,
            'deposit_amount' => 100000,
            'is_active' => true,
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081987654321',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-SUCCESS',
            'start_date' => Carbon::now('Asia/Jakarta')->addDays(3)->toDateString(),
            'end_date' => Carbon::now('Asia/Jakarta')->addDays(5)->toDateString(),
            'total_days' => 3,
            'total_price' => 450000,
            'deposit_paid' => 100000,
            'status' => Booking::STATUS_PENDING,
        ]);

        $response = $this->get('/b/success/'.$booking->ulid);

        $response->assertStatus(200);
        $response->assertSee('Permintaan Sewa Berhasil Dibuat!');
        $response->assertSee('JS-20261019-SUCCESS');
        $response->assertSee('Kostum Raiden Shogun (M)');
        $response->assertSee('Studio Cosplay Akihabara');
        $response->assertSee('Budi Santoso');
        $response->assertSee('Rp 450.000');
        $response->assertSee('Rp 100.000');
        $response->assertSee('Hubungi Vendor via WhatsApp');

        // Verify wa.me URL is rendered in HTML
        $content = $response->getContent();
        $this->assertStringContainsString('https://wa.me/6281234567890?text=', $content);
        $this->assertStringContainsString('JS-20261019-SUCCESS', $content);
    }

    public function test_non_existent_booking_ulid_returns_404(): void
    {
        $response = $this->get('/b/success/01HXYZNONEXISTENTULID12345');

        $response->assertStatus(404);
    }

    public function test_booking_success_page_handles_vendor_without_wa_number_gracefully(): void
    {
        $vendor = Vendor::factory()->create([
            'wa_number' => '0221234567', // Landline phone, not a valid WhatsApp mobile number
            'is_active' => true,
        ]);

        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
        ]);

        $response = $this->get('/b/success/'.$booking->ulid);

        $response->assertStatus(200);
        $response->assertDontSee('https://wa.me/?text=');
        $response->assertSee('Kontak Vendor:');
    }
}
