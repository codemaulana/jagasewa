<?php

namespace App\Services;

use App\Models\Booking;
use App\Services\Billing\EntitlementService;
use Illuminate\Validation\ValidationException;

class PdfDocumentService
{
    /**
     * Check if the vendor is entitled to generate PDF documents.
     */
    public function canGeneratePdf(Booking $booking): bool
    {
        $vendor = $booking->vendor;

        return $vendor && ($vendor->isPro() || app(EntitlementService::class)->canUse($vendor, 'pdf_agreement_invoice'));
    }

    /**
     * Generate standard PDF 1.4 binary for Invoice.
     *
     * @throws ValidationException
     */
    public function generateInvoicePdf(Booking $booking): string
    {
        if (! $this->canGeneratePdf($booking)) {
            throw ValidationException::withMessages([
                'plan' => 'Fitur cetak dan unduh dokumen Invoice PDF hanya tersedia untuk vendor paket Pro.',
            ]);
        }

        $booking->loadMissing(['item', 'vendor', 'renter']);
        $vendor = $booking->vendor;
        $renter = $booking->renter;
        $item = $booking->item;

        $title = 'INVOICE SEWA - '.$booking->reference_code;
        $lines = [
            ['text' => strtoupper($vendor?->name ?? 'JAGASEWA RENTAL'), 'size' => 16, 'bold' => true, 'y' => 780],
            ['text' => 'INVOICE & BUKTI TRANSAKSI SEWA RESMI', 'size' => 11, 'bold' => false, 'y' => 760],
            ['text' => '--------------------------------------------------------------------------------', 'size' => 10, 'bold' => false, 'y' => 745],

            ['text' => 'DATA PESANAN', 'size' => 11, 'bold' => true, 'y' => 725],
            ['text' => 'No. Referensi : '.$booking->reference_code, 'size' => 10, 'bold' => false, 'y' => 708],
            ['text' => 'Tanggal Order : '.($booking->created_at ? $booking->created_at->format('d M Y, H:i').' WIB' : '-'), 'size' => 10, 'bold' => false, 'y' => 692],
            ['text' => 'Status Pesanan: '.strtoupper($booking->status), 'size' => 10, 'bold' => false, 'y' => 676],

            ['text' => 'DATA PENYEWA', 'size' => 11, 'bold' => true, 'y' => 650],
            ['text' => 'Nama Lengkap  : '.($renter?->name ?? 'Penyewa'), 'size' => 10, 'bold' => false, 'y' => 633],
            ['text' => 'No. WhatsApp  : '.($renter?->phone ?? '-'), 'size' => 10, 'bold' => false, 'y' => 617],

            ['text' => 'RINCIAN ITEM & JADWAL SEWA', 'size' => 11, 'bold' => true, 'y' => 590],
            ['text' => 'Nama Barang   : '.($item?->name ?? 'Unit Rental').($item?->item_code ? ' ('.$item->item_code.')' : ''), 'size' => 10, 'bold' => false, 'y' => 573],
            ['text' => 'Periode Sewa  : '.($booking->start_date ? $booking->start_date->format('d M Y') : '-').' s/d '.($booking->end_date ? $booking->end_date->format('d M Y') : '-').' ('.$booking->total_days.' Hari)', 'size' => 10, 'bold' => false, 'y' => 557],

            ['text' => '--------------------------------------------------------------------------------', 'size' => 10, 'bold' => false, 'y' => 537],
            ['text' => 'RINCIAN KEUANGAN', 'size' => 11, 'bold' => true, 'y' => 517],
            ['text' => 'Tarif Sewa    : Rp '.number_format((int) $booking->total_price, 0, ',', '.'), 'size' => 10, 'bold' => false, 'y' => 500],
            ['text' => 'Uang Jaminan  : Rp '.number_format((int) ($booking->deposit_paid ?? $item?->deposit_amount ?? 0), 0, ',', '.'), 'size' => 10, 'bold' => false, 'y' => 484],
            ['text' => 'Total Tagihan : Rp '.number_format((int) ($booking->total_price + ($booking->deposit_paid ?? $item?->deposit_amount ?? 0)), 0, ',', '.'), 'size' => 11, 'bold' => true, 'y' => 460],

            ['text' => 'Status Pembayaran: '.($booking->is_paid_in_full ? 'LUNAS PENUH' : ($booking->is_dp_paid ? 'DP DIBAYAR (Rp '.number_format((int) $booking->down_payment_amount, 0, ',', '.').')' : 'MENUNGGU PEMBAYARAN')), 'size' => 10, 'bold' => false, 'y' => 438],
            ['text' => '--------------------------------------------------------------------------------', 'size' => 10, 'bold' => false, 'y' => 418],

            ['text' => 'CATATAN & KETENTUAN:', 'size' => 9, 'bold' => true, 'y' => 395],
            ['text' => '1. Uang jaminan (deposit) akan disesuaikan atau dikembalikan saat unit selesai diperiksa.', 'size' => 8, 'bold' => false, 'y' => 380],
            ['text' => '2. Keterlambatan pengembalian dikenakan denda sesuai tarif yang berlaku.', 'size' => 8, 'bold' => false, 'y' => 366],
            ['text' => '3. Invoice ini sah dan diterbitkan secara digital melalui platform JagaSewa.', 'size' => 8, 'bold' => false, 'y' => 352],
        ];

        return $this->buildPdfStream($title, $lines);
    }

    /**
     * Generate standard PDF 1.4 binary for Rental Agreement (Surat Perjanjian Sewa).
     *
     * @throws ValidationException
     */
    public function generateAgreementPdf(Booking $booking): string
    {
        if (! $this->canGeneratePdf($booking)) {
            throw ValidationException::withMessages([
                'plan' => 'Fitur cetak surat perjanjian sewa PDF hanya tersedia untuk vendor paket Pro.',
            ]);
        }

        $booking->loadMissing(['item', 'vendor', 'renter']);
        $vendor = $booking->vendor;
        $renter = $booking->renter;
        $item = $booking->item;

        $title = 'SURAT PERJANJIAN SEWA - '.$booking->reference_code;
        $lines = [
            ['text' => 'SURAT PERJANJIAN SEWA MENYEWA', 'size' => 15, 'bold' => true, 'y' => 780],
            ['text' => 'Nomor Kontrak: '.$booking->reference_code, 'size' => 10, 'bold' => false, 'y' => 762],
            ['text' => '--------------------------------------------------------------------------------', 'size' => 10, 'bold' => false, 'y' => 748],

            ['text' => 'PIHAK PERTAMA (VENDOR / PEMILIK)', 'size' => 10, 'bold' => true, 'y' => 730],
            ['text' => 'Nama Toko   : '.($vendor?->name ?? 'Vendor JagaSewa'), 'size' => 9, 'bold' => false, 'y' => 715],
            ['text' => 'Kontak WA   : '.($vendor?->wa_number ?? '-'), 'size' => 9, 'bold' => false, 'y' => 701],

            ['text' => 'PIHAK KEDUA (PENYEWA)', 'size' => 10, 'bold' => true, 'y' => 678],
            ['text' => 'Nama        : '.($renter?->name ?? 'Penyewa'), 'size' => 9, 'bold' => false, 'y' => 663],
            ['text' => 'No. Telepon : '.($renter?->phone ?? '-'), 'size' => 9, 'bold' => false, 'y' => 649],

            ['text' => 'OBJEK SEWA & JANGKA WAKTU', 'size' => 10, 'bold' => true, 'y' => 625],
            ['text' => 'Barang      : '.($item?->name ?? 'Unit Rental').($item?->item_code ? ' ('.$item->item_code.')' : ''), 'size' => 9, 'bold' => false, 'y' => 610],
            ['text' => 'Kondisi Awal: '.($item?->condition ?? 'Sangat Baik'), 'size' => 9, 'bold' => false, 'y' => 596],
            ['text' => 'Durasi      : '.($booking->start_date ? $booking->start_date->format('d M Y') : '-').' s/d '.($booking->end_date ? $booking->end_date->format('d M Y') : '-').' ('.$booking->total_days.' hari)', 'size' => 9, 'bold' => false, 'y' => 582],

            ['text' => 'PASAL 1: HAK DAN KEWAJIBAN', 'size' => 9, 'bold' => true, 'y' => 555],
            ['text' => '1. Pihak Kedua wajib memelihara dan memperlakukan unit sewa dengan itikad baik.', 'size' => 8, 'bold' => false, 'y' => 541],
            ['text' => '2. Dilarang memindahtangankan atau menyewakan kembali unit kepada pihak ketiga.', 'size' => 8, 'bold' => false, 'y' => 527],

            ['text' => 'PASAL 2: UANG JAMINAN & KERUSAKAN', 'size' => 9, 'bold' => true, 'y' => 503],
            ['text' => '1. Deposit jaminan sebesar Rp '.number_format((int) ($booking->deposit_paid ?? $item?->deposit_amount ?? 0), 0, ',', '.').' dititipkan sebagai pengaman.', 'size' => 8, 'bold' => false, 'y' => 489],
            ['text' => '2. Apabila terjadi kerusakan/noda permanen, biaya perbaikan dipotong dari uang jaminan.', 'size' => 8, 'bold' => false, 'y' => 475],
            ['text' => '3. Keterlambatan dikenakan denda sebesar Rp '.number_format((int) ($item?->fine_per_day ?? 0), 0, ',', '.').' per hari keterlambatan.', 'size' => 8, 'bold' => false, 'y' => 461],

            ['text' => '--------------------------------------------------------------------------------', 'size' => 10, 'bold' => false, 'y' => 440],
            ['text' => 'TANDA TANGAN PARA PIHAK', 'size' => 9, 'bold' => true, 'y' => 418],
            ['text' => 'Pihak Pertama (Vendor)                             Pihak Kedua (Penyewa)', 'size' => 9, 'bold' => false, 'y' => 395],
            ['text' => '( '.($vendor?->name ?? 'Vendor').' )                                  ( '.($renter?->name ?? 'Penyewa').' )', 'size' => 9, 'bold' => false, 'y' => 320],
        ];

        return $this->buildPdfStream($title, $lines);
    }

    /**
     * Build minimal valid PDF 1.4 stream without external libraries.
     *
     * @param  list<array{text: string, size: int, bold: bool, y: int}>  $lines
     */
    protected function buildPdfStream(string $title, array $lines): string
    {
        $contentStream = '';
        foreach ($lines as $line) {
            $font = ! empty($line['bold']) ? '/F2' : '/F1';
            $size = (int) $line['size'];
            $y = (int) $line['y'];
            $safeText = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line['text']);

            $contentStream .= "BT {$font} {$size} Tf 50 {$y} Td ({$safeText}) Tj ET\n";
        }

        $streamLen = strlen($contentStream);

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>';
        $objects[4] = "<< /Length {$streamLen} >>\nstream\n{$contentStream}endstream";
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[6] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        for ($i = 1; $i <= 6; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= "{$i} 0 obj\n".$objects[$i]."\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 7\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 6; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }
}
