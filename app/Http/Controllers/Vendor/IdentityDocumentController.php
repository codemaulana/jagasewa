<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\IdentityDocument;
use App\Services\IdentityDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IdentityDocumentController extends Controller
{
    public function __construct(
        protected IdentityDocumentService $documentService
    ) {}

    /**
     * Generate signed URL for an identity document (IDOR-03 / SEC-27).
     * Must be authenticated and belong to current vendor.
     */
    public function signedUrl(Request $request, string $ulid): JsonResponse
    {
        $vendorUser = Auth::guard('vendor')->user();
        if (! $vendorUser) {
            abort(401);
        }

        // Vendor isolation check: document's booking must belong to authenticated vendor
        $document = IdentityDocument::where('ulid', $ulid)
            ->whereHas('booking', function ($query) use ($vendorUser) {
                $query->where('vendor_id', $vendorUser->vendor_id);
            })
            ->first();

        if (! $document) {
            abort(404, 'Dokumen identitas tidak ditemukan.');
        }

        $signedUrl = $this->documentService->generateTemporaryUrl($document, 60);

        return response()->json([
            'success' => true,
            'data' => [
                'ulid' => $document->ulid,
                'doc_type' => $document->doc_type,
                'signed_url' => $signedUrl,
                'expires_in_minutes' => 60,
            ],
        ]);
    }

    /**
     * Securely view/stream the private document via signed route (TC-40).
     */
    public function secureView(Request $request, string $ulid)
    {
        $document = IdentityDocument::where('ulid', $ulid)->firstOrFail();

        $disk = Storage::disk($document->storage_disk ?: 's3');
        if (! $disk->exists($document->storage_path)) {
            abort(404, 'File dokumen tidak ditemukan di penyimpanan.');
        }

        return response($disk->get($document->storage_path), 200, [
            'Content-Type' => $document->mime_type ?: 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$document->ulid.'.jpg"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
        ]);
    }
}
