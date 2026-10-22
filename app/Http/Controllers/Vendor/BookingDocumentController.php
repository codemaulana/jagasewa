<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PdfDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingDocumentController extends Controller
{
    public function __construct(
        protected PdfDocumentService $pdfService
    ) {}

    /**
     * View or download invoice for a booking.
     */
    public function invoice(Request $request, Booking $booking): Response
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor || (int) $booking->vendor_id !== (int) $vendor->id) {
            abort(404);
        }

        if (! $this->pdfService->canGeneratePdf($booking)) {
            abort(403, 'Fitur cetak invoice dan dokumen PDF resmi hanya tersedia untuk vendor paket Pro.');
        }

        if ($request->query('download') === 'pdf') {
            $pdfContent = $this->pdfService->generateInvoicePdf($booking);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-'.$booking->reference_code.'.pdf"',
                'Content-Length' => strlen($pdfContent),
            ]);
        }

        $booking->loadMissing(['item', 'renter', 'vendor']);

        return response()->view('documents.invoice', [
            'booking' => $booking,
            'vendor' => $vendor,
            'item' => $booking->item,
            'renter' => $booking->renter,
        ]);
    }

    /**
     * View or download rental agreement (surat perjanjian sewa) for a booking.
     */
    public function agreement(Request $request, Booking $booking): Response
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor || (int) $booking->vendor_id !== (int) $vendor->id) {
            abort(404);
        }

        if (! $this->pdfService->canGeneratePdf($booking)) {
            abort(403, 'Fitur cetak surat perjanjian sewa PDF resmi hanya tersedia untuk vendor paket Pro.');
        }

        if ($request->query('download') === 'pdf') {
            $pdfContent = $this->pdfService->generateAgreementPdf($booking);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="perjanjian-sewa-'.$booking->reference_code.'.pdf"',
                'Content-Length' => strlen($pdfContent),
            ]);
        }

        $booking->loadMissing(['item', 'renter', 'vendor']);

        return response()->view('documents.rental-agreement', [
            'booking' => $booking,
            'vendor' => $vendor,
            'item' => $booking->item,
            'renter' => $booking->renter,
        ]);
    }
}
