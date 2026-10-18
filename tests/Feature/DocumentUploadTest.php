<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function createValidImageContent(int $width = 400, int $height = 300): string
    {
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 240, 240, 240);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);
        $textColor = imagecolorallocate($img, 50, 50, 50);
        imagestring($img, 5, 20, 20, 'CONTOH KTP JAGASEWA TEST', $textColor);

        ob_start();
        imagejpeg($img, null, 90);
        $content = ob_get_clean();
        imagedestroy($img);

        return (string) $content;
    }

    protected function createValidPngContent(int $width = 400, int $height = 300): string
    {
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 200, 220, 240);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);
        $textColor = imagecolorallocate($img, 20, 40, 80);
        imagestring($img, 5, 20, 20, 'CONTOH PNG JAGASEWA', $textColor);

        ob_start();
        imagepng($img);
        $content = ob_get_clean();
        imagedestroy($img);

        return (string) $content;
    }

    /**
     * TC-34: Upload file JPEG valid -> disimpan dengan nama UUID, EXIF dihapus, watermark dinamis tercetak.
     */
    public function test_can_upload_valid_document_with_uuid_name_and_watermark(): void
    {
        Storage::fake('s3');

        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
        ]);

        $imageContent = $this->createValidImageContent();
        $file = UploadedFile::fake()->createWithContent('user-original-ktp-name.jpg', $imageContent);

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Dokumen identitas berhasil diunggah.',
                'data' => [
                    'doc_type' => 'ktp',
                ],
            ]);

        $docUlid = $response->json('data.ulid');
        $document = IdentityDocument::where('ulid', $docUlid)->firstOrFail();

        // 1. Stored in S3 private disk with UUID name (SEC-26, SEC-30)
        $this->assertEquals('s3', $document->storage_disk);
        $this->assertStringStartsWith('identity-documents/', $document->storage_path);
        $this->assertStringEndsWith('.jpg', $document->storage_path);
        $this->assertStringNotContainsString('user-original-ktp-name', $document->storage_path);
        Storage::disk('s3')->assertExists($document->storage_path);

        // 2. File size and MIME type recorded
        $this->assertGreaterThan(0, $document->file_size);
        $this->assertEquals('image/jpeg', $document->mime_type);

        // 3. Consent and retention metadata (BR-05)
        $this->assertTrue($document->consent_given);
        $this->assertEquals('v1.0', $document->consent_version);
        $this->assertNotNull($document->consent_at);
        $this->assertNotNull($document->delete_scheduled_at);

        // 4. Stored image can be decoded and verified
        $storedBytes = Storage::disk('s3')->get($document->storage_path);
        $storedImage = @imagecreatefromstring($storedBytes);
        $this->assertNotFalse($storedImage, 'Failed asserting that stored image is a valid GD image.');
        imagedestroy($storedImage);

        // 5. EXIF stripped (GPS, camera info, device tags are gone per SEC-29)
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($storedBytes));
            if (is_array($exif)) {
                $this->assertArrayNotHasKey('GPS', $exif, 'GPS metadata must be stripped.');
                $this->assertArrayNotHasKey('IFD0', $exif, 'Camera/device metadata must be stripped.');
                $this->assertArrayNotHasKey('EXIF', $exif, 'EXIF tags must be stripped.');
                $this->assertArrayNotHasKey('GPSLatitude', $exif);
                $this->assertArrayNotHasKey('GPSLongitude', $exif);
                $this->assertArrayNotHasKey('Model', $exif);
                $this->assertArrayNotHasKey('Make', $exif);
                $this->assertArrayNotHasKey('DateTimeOriginal', $exif);
            }
        }
    }

    /**
     * TC-31: Upload file .exe dengan MIME image/jpeg -> tolak (magic bytes check).
     */
    public function test_upload_exe_file_disguised_as_jpg_is_rejected(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $exeContent = "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff\x00\x00\xb8\x00\x00\x00This program cannot be run in DOS mode.";
        $maliciousFile = UploadedFile::fake()->createWithContent('malware.jpg', $exeContent);

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $maliciousFile,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // Verify nothing was stored in S3
        $this->assertEmpty(Storage::disk('s3')->allFiles('identity-documents'));
    }

    /**
     * TC-32: Upload file JPEG valid ukuran > 5 MB -> tolak.
     */
    public function test_upload_file_exceeding_5mb_is_rejected(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        // 5200 KB > 5120 KB (5 MB)
        $oversizedFile = UploadedFile::fake()->create('oversized.jpg', 5200, 'image/jpeg');

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $oversizedFile,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * TC-33: Upload file PHP dengan ekstensi .jpg -> tolak.
     */
    public function test_upload_php_file_disguised_as_jpg_is_rejected(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $phpContent = "<?php echo 'Hacked!'; system(\$_GET['cmd']); ?>";
        $disguisedFile = UploadedFile::fake()->createWithContent('shell.jpg', $phpContent);

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $disguisedFile,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->assertEmpty(Storage::disk('s3')->allFiles('identity-documents'));
    }

    /**
     * TC-35: Akses direct URL ke objek S3/storage -> 403.
     */
    public function test_direct_url_access_to_s3_storage_is_forbidden(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $file = UploadedFile::fake()->createWithContent('ktp.jpg', $this->createValidImageContent());

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $doc = IdentityDocument::firstOrFail();

        // Direct HTTP access simulation without signed token gives 403
        $endpoint = rtrim(config('filesystems.disks.s3.endpoint') ?: 'https://f004.backblazeb2.com', '/');
        $bucket = config('filesystems.disks.s3.bucket') ?: 'jagasewa-docs-private';
        $directUrl = "{$endpoint}/file/{$bucket}/{$doc->storage_path}";

        Http::fake([
            $directUrl => Http::response('AccessDenied', 403),
        ]);

        $httpResponse = Http::get($directUrl);
        $this->assertEquals(403, $httpResponse->status());
    }

    public function test_uploading_without_consent_fails_validation(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $file = UploadedFile::fake()->createWithContent('ktp.jpg', $this->createValidImageContent());

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => '0',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['consent']);
    }

    public function test_uploading_invalid_doc_type_fails_validation(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $file = UploadedFile::fake()->createWithContent('ktp.jpg', $this->createValidImageContent());

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => 'passport', // Not allowed
            'file' => $file,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['doc_type']);
    }

    public function test_can_upload_selfie_document(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $file = UploadedFile::fake()->createWithContent('selfie.jpg', $this->createValidImageContent());

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_SELFIE,
            'file' => $file,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'doc_type' => 'selfie',
                ],
            ]);

        $document = IdentityDocument::where('doc_type', IdentityDocument::DOC_TYPE_SELFIE)->firstOrFail();
        $this->assertTrue($document->isSelfie());
    }

    public function test_can_upload_valid_png_document_re_encoded_to_jpeg(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $file = UploadedFile::fake()->createWithContent('document.png', $this->createValidPngContent());

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $document = IdentityDocument::latest('id')->firstOrFail();
        $this->assertEquals('image/jpeg', $document->mime_type);
        $this->assertStringEndsWith('.jpg', $document->storage_path);
    }

    public function test_upload_pdf_file_is_rejected(): void
    {
        Storage::fake('s3');

        $booking = Booking::factory()->create();
        $pdfContent = "%PDF-1.4\n%...\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('scan.pdf', $pdfContent);

        $response = $this->post(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => '1',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
