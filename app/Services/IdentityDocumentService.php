<?php

namespace App\Services;

use App\Models\IdentityDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

class IdentityDocumentService
{
    /**
     * Generate temporary signed URL for an identity document (TTL in minutes, default 60).
     */
    public function generateTemporaryUrl(IdentityDocument $document, int $ttlMinutes = 60): string
    {
        $expiresAt = Carbon::now('Asia/Jakarta')->addMinutes($ttlMinutes);

        // Audit log generation without raw storage path
        AuditService::log(
            'identity_document_signed_url_generated',
            'identity_document',
            (string) $document->id,
            [
                'document_ulid' => $document->ulid,
                'doc_type' => $document->doc_type,
                'ttl_minutes' => $ttlMinutes,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            $document->booking?->vendor_id
        );

        // 2. Try S3 direct temporaryUrl if configured with real credentials (not testing)
        $hasS3Config = ! empty(config('filesystems.disks.s3.bucket')) && ! empty(config('filesystems.disks.s3.key'));
        if ($hasS3Config && ! app()->environment('testing')) {
            try {
                return Storage::disk('s3')->temporaryUrl($document->storage_path, $expiresAt);
            } catch (Throwable) {
                // Fallback to application signed route
            }
        }

        // 3. Application signed route (secure URL validated by signature & expiration timestamp)
        return URL::temporarySignedRoute(
            'vendor.identity-documents.secure-view',
            $expiresAt,
            ['ulid' => $document->ulid]
        );
    }
}
