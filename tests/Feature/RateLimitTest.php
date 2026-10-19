<?php

namespace Tests\Feature;

use App\Livewire\Public\BookingForm;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Item;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('rate_limit:booking_public:ip:127.0.0.1');
        RateLimiter::clear('rate_limit:booking_public:ip:192.168.1.100');
    }

    public function test_tc21_login_throttled_after_5_attempts_in_one_minute(): void
    {
        $vendor = Vendor::factory()->create();
        VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'vendor@example.com',
            'password' => bcrypt('correctpassword123'),
        ]);

        // First 5 attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('vendor.login.submit'), [
                'email' => 'vendor@example.com',
                'password' => 'wrongpassword',
            ], ['REMOTE_ADDR' => '192.168.1.50']);

            $response->assertStatus(302);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt from the same IP must be throttled with HTTP 429
        $response = $this->post(route('vendor.login.submit'), [
            'email' => 'vendor@example.com',
            'password' => 'wrongpassword',
        ], ['REMOTE_ADDR' => '192.168.1.50']);

        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_tc22_booking_form_throttled_per_ip_and_per_wa(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 0,
            'buffer_after' => 0,
        ]);

        $ip = '192.168.1.100';

        $this->withServerVariables(['REMOTE_ADDR' => $ip]);

        // 1. Test WA limit (3 per hour)
        for ($i = 0; $i < 3; $i++) {
            Livewire::test(BookingForm::class, ['item' => $item])
                ->set('name', 'Budi Santoso')
                ->set('phone', '081299998888')
                ->set('address', 'Jl. Sudirman No. '.($i + 1))
                ->set('startDate', now()->addDays(10 + ($i * 5))->format('Y-m-d'))
                ->set('endDate', now()->addDays(12 + ($i * 5))->format('Y-m-d'))
                ->set('agreePrivacy', true)
                ->call('submit')
                ->assertHasNoErrors();
        }

        // 4th booking with same WA should trigger 429
        $component = Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'Budi Santoso')
            ->set('phone', '081299998888')
            ->set('address', 'Jl. Sudirman No. 99')
            ->set('startDate', now()->addDays(40)->format('Y-m-d'))
            ->set('endDate', now()->addDays(42)->format('Y-m-d'))
            ->set('agreePrivacy', true)
            ->call('submit');

        $component->assertStatus(429);
    }

    public function test_tc23_document_upload_throttled_after_5_attempts_in_15_minutes(): void
    {
        Storage::fake('s3');
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);

        $ip = '192.168.1.75';

        // First 5 uploads should succeed
        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->image("ktp_{$i}.jpg", 600, 400);

            $response = $this->post(route('public.booking.upload'), [
                'booking_ulid' => $booking->ulid,
                'doc_type' => 'ktp',
                'file' => $file,
                'consent' => '1',
            ], ['REMOTE_ADDR' => $ip]);

            $response->assertStatus(201);
            $response->assertJsonStructure(['success', 'data' => ['ulid', 'doc_type']]);
        }

        // 6th upload from same IP must be throttled with HTTP 429
        $file = UploadedFile::fake()->image('ktp_overflow.jpg', 600, 400);
        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => 'ktp',
            'file' => $file,
            'consent' => '1',
        ], ['REMOTE_ADDR' => $ip]);

        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_signed_url_generation_throttled_after_20_attempts_per_minute(): void
    {
        Storage::fake('s3');
        $vendor = Vendor::factory()->create();
        $vendorUser = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);
        $document = IdentityDocument::factory()->create([
            'booking_id' => $booking->id,
            'storage_path' => 'documents/test.jpg',
        ]);

        $this->actingAs($vendorUser, 'vendor');

        for ($i = 0; $i < 20; $i++) {
            $response = $this->get(route('vendor.identity-documents.signed-url', ['ulid' => $document->ulid]));
            $response->assertStatus(200);
        }

        // 21st attempt must be throttled with HTTP 429
        $response = $this->get(route('vendor.identity-documents.signed-url', ['ulid' => $document->ulid]));
        $response->assertStatus(429);
    }

    public function test_blacklist_check_endpoint_throttled_after_30_attempts_per_minute(): void
    {
        $vendor = Vendor::factory()->create();
        $vendorUser = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $this->actingAs($vendorUser, 'vendor');

        for ($i = 0; $i < 30; $i++) {
            $response = $this->get(route('vendor.blacklist.check', ['phone' => '08123456789'.$i]));
            $response->assertStatus(200);
            $response->assertJson(['blacklisted' => false]);
        }

        // 31st attempt must be throttled with HTTP 429
        $response = $this->get(route('vendor.blacklist.check', ['phone' => '081234567899']));
        $response->assertStatus(429);
    }

    public function test_cloudflare_trusted_proxies_resolves_client_ip(): void
    {
        // Cloudflare IP proxy (e.g. 173.245.48.5) forwarding real client IP
        $clientIp = '203.0.113.195';
        $cfProxyIp = '173.245.48.5';

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $cfProxyIp,
            'HTTP_X_FORWARDED_FOR' => $clientIp,
        ])->get('/');

        $response->assertStatus(200);
    }
}
