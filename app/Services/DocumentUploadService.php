<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\IdentityDocument;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentUploadService
{
    /**
     * Maximum allowed file size in bytes (5 MB).
     */
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Allowed MIME types detected by magic bytes.
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Upload, validate, strip EXIF, auto-watermark, and store an identity document.
     *
     * @throws ValidationException
     */
    public function upload(
        UploadedFile $file,
        Booking $booking,
        string $docType,
        bool $consentGiven = true,
        string $consentVersion = 'v1.0'
    ): IdentityDocument {
        // 1. Validate doc_type
        if (! in_array($docType, [IdentityDocument::DOC_TYPE_KTP, IdentityDocument::DOC_TYPE_SELFIE], true)) {
            throw ValidationException::withMessages([
                'doc_type' => 'Tipe dokumen tidak valid. Hanya KTP atau selfie yang diperbolehkan.',
            ]);
        }

        // 2. Validate file size (<= 5 MB)
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran file tidak boleh melebihi 5 MB.',
            ]);
        }

        $realPath = $file->getRealPath() ?: $file->path();

        // Validate file content / magic bytes via PHP finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = false;
        if (! empty($realPath) && file_exists($realPath)) {
            $detectedMime = @finfo_file($finfo, $realPath);
        }
        if (! $detectedMime) {
            $content = @file_get_contents($realPath);
            if ($content !== false) {
                $detectedMime = finfo_buffer($finfo, $content);
            }
        }
        finfo_close($finfo);

        if (! in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'file' => "Format file tidak didukung ({$detectedMime}). Hanya gambar JPG, PNG, atau WEBP asli yang diperbolehkan.",
            ]);
        }

        // Extra check against embedded executable or PHP script tags
        $sample = @file_get_contents($realPath, false, null, 0, 1024);
        if ($sample !== false && (stripos($sample, '<?php') !== false || stripos($sample, '<script') !== false)) {
            throw ValidationException::withMessages([
                'file' => 'File gambar tidak valid atau mengandung skrip berbahaya.',
            ]);
        }

        // 4. Create GD image resource based on detected MIME
        $image = false;
        if (! empty($realPath) && file_exists($realPath)) {
            $image = match ($detectedMime) {
                'image/jpeg' => @imagecreatefromjpeg($realPath),
                'image/png' => @imagecreatefrompng($realPath),
                'image/webp' => @imagecreatefromwebp($realPath),
                default => false,
            };
        }

        if ($image === false) {
            $content = @file_get_contents($realPath);
            if ($content !== false) {
                $image = @imagecreatefromstring($content);
            }
        }

        if ($image === false) {
            throw ValidationException::withMessages([
                'file' => 'File gambar rusak atau tidak dapat diproses.',
            ]);
        }

        // 5. Apply dynamic watermark: "VERIFIKASI JAGASEWA - [VENDOR_ULID] - [TANGGAL]"
        $vendorUlid = $booking->vendor?->ulid ?? ($booking->vendor_id ? (string) $booking->vendor_id : 'UNKNOWN');
        $dateStamp = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $watermarkText = "VERIFIKASI JAGASEWA - {$vendorUlid} - {$dateStamp}";

        $this->applyWatermark($image, $watermarkText);

        // Re-encode to clean JPEG stream without original EXIF metadata
        ob_start();
        imagejpeg($image, null, 85);
        $encodedContent = ob_get_clean();
        imagedestroy($image);

        if (empty($encodedContent)) {
            throw ValidationException::withMessages([
                'file' => 'Gagal melakukan re-encode gambar.',
            ]);
        }

        // Store to private storage disk with random UUID filename
        $uuid = (string) Str::uuid();
        $storagePath = "identity-documents/{$uuid}.jpg";
        $storageDisk = 's3';

        Storage::disk($storageDisk)->put($storagePath, $encodedContent, [
            'visibility' => 'private',
            'ContentType' => 'image/jpeg',
        ]);

        // 8. Create and persist IdentityDocument record
        return IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $booking->renter_id,
            'doc_type' => $docType,
            'storage_path' => $storagePath,
            'storage_disk' => $storageDisk,
            'file_size' => strlen($encodedContent),
            'mime_type' => 'image/jpeg',
            'consent_given' => $consentGiven,
            'consent_version' => $consentVersion,
            'consent_at' => Carbon::now('Asia/Jakarta'),
            'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->addDays(config('jagasewa.retention.closed_days', 7)),
        ]);
    }

    /**
     * Stamp dynamic cross watermark on the GD image.
     *
     * @param  \GdImage  $image
     */
    protected function applyWatermark($image, string $watermarkText): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Built-in GD Font 5 (9x15 px per character)
        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $textWidth = $charWidth * strlen($watermarkText);

        // 1. Center prominent banner with semi-transparent dark background
        $bannerHeight = $charHeight + 20;
        $bannerY = (int) (($height - $bannerHeight) / 2);
        $bannerColor = imagecolorallocatealpha($image, 0, 0, 0, 60);

        if ($bannerColor !== false) {
            imagefilledrectangle($image, 0, $bannerY, $width, $bannerY + $bannerHeight, $bannerColor);
        }

        $textX = (int) max(10, ($width - $textWidth) / 2);
        $textY = $bannerY + 10;

        $shadowColor = imagecolorallocate($image, 0, 0, 0);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        // Text shadow + white text
        imagestring($image, $font, $textX + 1, $textY + 1, $watermarkText, $shadowColor);
        imagestring($image, $font, $textX, $textY, $watermarkText, $textColor);

        // 2. Anti-crop repeating watermarks at top and bottom margins
        $topY = (int) max(10, $height * 0.08);
        $bottomY = (int) min($height - $charHeight - 10, $height * 0.90);
        $secondaryTextColor = imagecolorallocatealpha($image, 255, 255, 255, 45);

        if ($secondaryTextColor !== false) {
            imagestring($image, $font, $textX, $topY, $watermarkText, $secondaryTextColor);
            imagestring($image, $font, $textX, $bottomY, $watermarkText, $secondaryTextColor);
        }
    }
}
