<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentUploadController extends Controller
{
    public function __construct(
        protected DocumentUploadService $uploadService
    ) {}

    /**
     * Handle public identity document upload for a booking.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_ulid' => ['required', 'string', 'exists:bookings,ulid'],
            'doc_type' => ['required', 'string', 'in:'.IdentityDocument::DOC_TYPE_KTP.','.IdentityDocument::DOC_TYPE_SELFIE],
            'file' => ['required', 'file', 'max:5120'], // Max 5 MB in KB
            'consent' => ['required', 'accepted'],
        ], [
            'booking_ulid.required' => 'ID Booking wajib disertakan.',
            'booking_ulid.exists' => 'Booking tidak ditemukan.',
            'doc_type.required' => 'Tipe dokumen wajib dipilih.',
            'doc_type.in' => 'Tipe dokumen harus berupa KTP atau Selfie.',
            'file.required' => 'File foto dokumen wajib diunggah.',
            'file.max' => 'Ukuran file tidak boleh melebihi 5 MB.',
            'consent.required' => 'Persetujuan pemrosesan data identitas wajib dicentang.',
            'consent.accepted' => 'Persetujuan pemrosesan data identitas wajib disetujui sesuai UU PDP.',
        ]);

        $booking = Booking::with('vendor')
            ->where('ulid', $validated['booking_ulid'])
            ->firstOrFail();

        $document = $this->uploadService->upload(
            file: $request->file('file'),
            booking: $booking,
            docType: $validated['doc_type'],
            consentGiven: true,
            consentVersion: 'v1.0'
        );

        return response()->json([
            'success' => true,
            'message' => 'Dokumen identitas berhasil diunggah.',
            'data' => [
                'ulid' => $document->ulid,
                'doc_type' => $document->doc_type,
                'file_size' => $document->file_size,
                'created_at' => $document->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
