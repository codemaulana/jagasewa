<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HtaccessAndServerHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T-40 / SEC-17: Verify public/.htaccess configuration file rules.
     */
    public function test_htaccess_contains_all_hardening_directives(): void
    {
        $htaccessPath = public_path('.htaccess');
        $this->assertFileExists($htaccessPath);

        $content = file_get_contents($htaccessPath);

        // SEC-40: Sensitive files blocking
        $this->assertStringContainsString('composer\.(json|lock)', $content);
        $this->assertStringContainsString('\.env', $content);
        $this->assertStringContainsString('artisan', $content);

        // SEC-17: LimitRequestBody 6MB (6291456 bytes)
        $this->assertStringContainsString('LimitRequestBody 6291456', $content);

        // SEC-38: TRACE/TRACK method blocking
        $this->assertStringContainsString('REQUEST_METHOD} ^(TRACE|TRACK)', $content);

        // Directory listing disabled
        $this->assertStringContainsString('Options -Indexes -MultiViews', $content);

        // PHP version header removal
        $this->assertStringContainsString('Header unset X-Powered-By', $content);
        $this->assertStringContainsString('expose_php Off', $content);
    }

    /**
     * T-40 / SEC-17: Verify public/.user.ini file exists with required limits.
     */
    public function test_user_ini_contains_php_upload_and_security_limits(): void
    {
        $userIniPath = public_path('.user.ini');
        $this->assertFileExists($userIniPath);

        $content = file_get_contents($userIniPath);

        $this->assertStringContainsString('upload_max_filesize = 6M', $content);
        $this->assertStringContainsString('post_max_size = 8M', $content);
        $this->assertStringContainsString('expose_php = Off', $content);
    }

    /**
     * T-40: Upload exceeding 5MB max validation limit is rejected by the application.
     */
    public function test_oversized_file_upload_is_rejected(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);

        // Create a 6.5 MB fake file (exceeds max 5120 KB limit)
        $largeFile = UploadedFile::fake()->create('large_ktp.jpg', 6656, 'image/jpeg');

        $response = $this->postJson(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $largeFile,
            'consent' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * T-40 / SEC-37: HTTP responses never leak X-Powered-By header.
     */
    public function test_responses_do_not_contain_x_powered_by(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertNull($response->headers->get('X-Powered-By'));
    }
}
