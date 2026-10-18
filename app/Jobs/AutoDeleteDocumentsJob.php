<?php

namespace App\Jobs;

use App\Models\IdentityDocument;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AutoDeleteDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to automatically delete expired identity documents (BR-05, SEC-31).
     *
     * @return int Total number of documents purged
     */
    public function handle(): int
    {
        $now = Carbon::now('Asia/Jakarta');

        $eligibleDocuments = IdentityDocument::eligibleForDeletion($now)
            ->with('booking')
            ->get();

        $purgedCount = 0;

        foreach ($eligibleDocuments as $doc) {
            DB::transaction(function () use ($doc, $now, &$purgedCount) {
                // Lock row to prevent race conditions during deletion
                $lockedDoc = IdentityDocument::where('id', $doc->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedDoc || $lockedDoc->trashed()) {
                    return;
                }

                $disk = $lockedDoc->storage_disk ?: 's3';

                // Physically remove file from private storage disk if it exists
                try {
                    if (! empty($lockedDoc->storage_path) && Storage::disk($disk)->exists($lockedDoc->storage_path)) {
                        Storage::disk($disk)->delete($lockedDoc->storage_path);
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to delete identity document file from storage: {$e->getMessage()}", [
                        'document_id' => $lockedDoc->id,
                        'booking_id' => $lockedDoc->booking_id,
                    ]);
                }

                // Soft-delete the document model record
                $lockedDoc->delete();

                // Audit log event without raw storage path or PII (BR-05, SEC-31)
                AuditService::log(
                    'document_deleted',
                    'identity_document',
                    (string) $lockedDoc->id,
                    [
                        'booking_id' => $lockedDoc->booking_id,
                        'doc_type' => $lockedDoc->doc_type,
                        'deleted_at' => $now->toIso8601String(),
                    ],
                    $lockedDoc->booking?->vendor_id
                );

                $purgedCount++;
            });
        }

        return $purgedCount;
    }
}
