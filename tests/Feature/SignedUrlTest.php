<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\IdentityDocumentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SignedUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_authenticated_vendor_can_generate_signed_url_for_own_booking_document(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);
        $document = IdentityDocument::factory()->create([
            'booking_id' => $booking->id,
            'renter_id' => $booking->renter_id,
            'storage_path' => 'identity-documents/sample-ktp.jpg',
            'storage_disk' => 's3',
        ]);

        Storage::disk('s3')->put('identity-documents/sample-ktp.jpg', 'fake-image-binary-data');

        $response = $this->actingAs($user, 'vendor')
            ->postJson(route('vendor.identity-documents.signed-url', ['ulid' => $document->ulid]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ulid' => $document->ulid,
                    'doc_type' => $document->doc_type,
                    'expires_in_minutes' => 60,
                ],
            ]);

        $signedUrl = $response->json('data.signed_url');
        $this->assertNotEmpty($signedUrl);
        $this->assertStringContainsString('signature=', $signedUrl);

        // Verify audit log recorded without raw path
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'identity_document_signed_url_generated',
            'entity_id' => (string) $document->id,
        ]);

        $auditLog = AuditLog::latest('id')->first();
        $this->assertNotNull($auditLog);
        $this->assertArrayNotHasKey('storage_path', $auditLog->payload ?? []);
    }

    public function test_unauthenticated_user_cannot_generate_signed_url(): void
    {
        $document = IdentityDocument::factory()->create();

        $response = $this->postJson(route('vendor.identity-documents.signed-url', ['ulid' => $document->ulid]));

        $response->assertStatus(401);
    }

    public function test_signed_url_allows_viewing_document_content(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);
        $document = IdentityDocument::factory()->create([
            'booking_id' => $booking->id,
            'storage_path' => 'identity-documents/sample-view.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $fakeContent = 'fake-jpeg-image-bytes-content';
        Storage::disk('s3')->put('identity-documents/sample-view.jpg', $fakeContent);

        $service = app(IdentityDocumentService::class);
        $validSignedUrl = $service->generateTemporaryUrl($document, 60);

        $response = $this->get($validSignedUrl);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->assertEquals($fakeContent, $response->getContent());
    }

    /**
     * Signed URL sudah expired -> HTTP 403.
     */
    public function test_expired_signed_url_returns_403(): void
    {
        $document = IdentityDocument::factory()->create([
            'storage_path' => 'identity-documents/sample-expired.jpg',
        ]);
        Storage::disk('s3')->put('identity-documents/sample-expired.jpg', 'content');

        // Create a signed URL that expired 1 minute ago
        $expiredUrl = URL::temporarySignedRoute(
            'vendor.identity-documents.secure-view',
            Carbon::now('Asia/Jakarta')->subMinute(),
            ['ulid' => $document->ulid]
        );

        $response = $this->get($expiredUrl);

        $response->assertStatus(403);
    }

    public function test_tampered_signed_url_returns_403(): void
    {
        $document = IdentityDocument::factory()->create([
            'storage_path' => 'identity-documents/sample-tamper.jpg',
        ]);
        Storage::disk('s3')->put('identity-documents/sample-tamper.jpg', 'content');

        $validUrl = URL::temporarySignedRoute(
            'vendor.identity-documents.secure-view',
            Carbon::now('Asia/Jakarta')->addMinutes(60),
            ['ulid' => $document->ulid]
        );

        // Tamper with the signature
        $tamperedUrl = $validUrl.'fake_tampered_signature';

        $response = $this->get($tamperedUrl);

        $response->assertStatus(403);
    }

    /**
     * Vendor A mengakses / generate signed URL dokumen milik Vendor B -> 404.
     */
    public function test_vendor_a_generate_signed_url_for_vendor_b_document_returns_404(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $bookingB = Booking::factory()->create(['vendor_id' => $vendorB->id]);
        $documentB = IdentityDocument::factory()->create([
            'booking_id' => $bookingB->id,
            'renter_id' => $bookingB->renter_id,
        ]);

        // Vendor A attempts to generate signed URL for Vendor B's document
        $response = $this->actingAs($userA, 'vendor')
            ->postJson(route('vendor.identity-documents.signed-url', ['ulid' => $documentB->ulid]));

        // MUST be 404 to prevent IDOR
        $response->assertStatus(404);
    }
}
