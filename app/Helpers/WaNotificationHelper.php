<?php

namespace App\Helpers;

use App\Models\Booking;

class WaNotificationHelper
{
    /**
     * Normalize Indonesian phone number to international WhatsApp format (628...).
     */
    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Strip non-digit characters except initial plus
        $cleaned = preg_replace('/[^\d+]/', '', trim($phone));

        if (str_starts_with($cleaned, '+62')) {
            $cleaned = substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '08')) {
            $cleaned = '62'.substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62'.$cleaned;
        }

        // Must start with 62 and be at least 10 digits
        if (! str_starts_with($cleaned, '62') || strlen($cleaned) < 10) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Generate wa.me URL for renter to contact vendor regarding a new booking.
     */
    public static function generateVendorChatUrl(Booking $booking): ?string
    {
        $vendor = $booking->vendor;
        $vendorPhone = self::normalizePhoneNumber($vendor?->wa_number);

        if (! $vendorPhone) {
            return null;
        }

        $itemName = $booking->item?->name ?? 'Barang Rental';
        $vendorName = $vendor->name ?? 'Vendor';
        $renterName = $booking->renter?->name ?? 'Penyewa';
        $startDate = $booking->start_date ? $booking->start_date->format('d M Y') : '-';
        $endDate = $booking->end_date ? $booking->end_date->format('d M Y') : '-';
        $totalPrice = number_format($booking->total_price, 0, ',', '.');

        $message = "Halo {$vendorName},\n\n"
            ."Saya telah mengajukan permohonan booking di JagaSewa:\n"
            ."• No. Referensi: {$booking->reference_code}\n"
            ."• Barang: {$itemName}\n"
            ."• Jadwal Sewa: {$startDate} s/d {$endDate} ({$booking->total_days} Hari)\n"
            ."• Total Biaya: Rp {$totalPrice}\n"
            ."• Nama Penyewa: {$renterName}\n\n"
            .'Mohon untuk diperiksa dan dikonfirmasi pesanannya. Terima kasih!';

        return 'https://wa.me/'.$vendorPhone.'?text='.rawurlencode($message);
    }

    /**
     * Generate custom wa.me URL for arbitrary phone number and text.
     */
    public static function generateCustomChatUrl(?string $phone, string $message): ?string
    {
        $normalizedPhone = self::normalizePhoneNumber($phone);
        if (! $normalizedPhone) {
            return null;
        }

        return 'https://wa.me/'.$normalizedPhone.'?text='.rawurlencode($message);
    }

    /**
     * Generate wa.me URL for vendor to contact renter (e.g. approved, rejected, settled, return_reminder actions).
     * Pro vendors can customize message templates with dynamic placeholder variables.
     */
    public static function generateRenterChatUrl(Booking $booking, string $action = 'approved'): ?string
    {
        $renter = $booking->renter;
        $renterPhone = self::normalizePhoneNumber($renter?->phone);

        if (! $renterPhone) {
            return null;
        }

        $vendor = $booking->vendor;
        $itemName = $booking->item?->name ?? 'Barang Rental';
        $vendorName = $vendor?->name ?? 'Vendor';
        $renterName = $renter->name ?? 'Kak';
        $startDate = $booking->start_date ? $booking->start_date->format('d M Y') : '-';
        $endDate = $booking->end_date ? $booking->end_date->format('d M Y') : '-';
        $bankAccount = $vendor?->bank_account_info ?? '';
        $totalPrice = number_format($booking->total_price, 0, ',', '.');
        $depositAmount = number_format($booking->deposit_paid ?? $booking->item?->deposit_amount ?? 0, 0, ',', '.');

        // Check if Pro vendor has a custom template configured for this action
        $isPro = $vendor && $vendor->isPro();
        $customTemplates = $vendor?->custom_wa_templates ?? [];

        if ($isPro && ! empty($customTemplates[$action]) && trim((string) $customTemplates[$action]) !== '') {
            $template = (string) $customTemplates[$action];
            $replacements = [
                '{nama_penyewa}' => $renterName,
                '{nama}' => $renterName,
                '{nama_barang}' => $itemName,
                '{barang}' => $itemName,
                '{kode_booking}' => $booking->reference_code,
                '{nama_toko}' => $vendorName,
                '{nama_vendor}' => $vendorName,
                '{rekening_toko}' => $bankAccount,
                '{jadwal_sewa}' => "{$startDate} s/d {$endDate}",
                '{tanggal}' => "{$startDate} s/d {$endDate}",
                '{total_biaya}' => "Rp {$totalPrice}",
                '{total}' => "Rp {$totalPrice}",
                '{total_deposit}' => "Rp {$depositAmount}",
            ];

            $message = str_replace(array_keys($replacements), array_values($replacements), $template);

            return 'https://wa.me/'.$renterPhone.'?text='.rawurlencode($message);
        }

        if ($action === 'approved') {
            $message = "Halo {$renterName},\n\n"
                ."Kabar baik! Permohonan booking Anda di {$vendorName} telah DISETUJUI:\n"
                ."• No. Referensi: {$booking->reference_code}\n"
                ."• Barang: {$itemName}\n"
                ."• Jadwal: {$startDate} s/d {$endDate}\n\n"
                .(! empty($bankAccount) ? "Info Pembayaran / Rekening:\n{$bankAccount}\n\n" : '')
                .'Silakan balas pesan ini untuk koordinasi penyerahan / pengiriman barang. Terima kasih!';
        } elseif ($action === 'rejected') {
            $message = "Halo {$renterName},\n\n"
                ."Mohon maaf, permohonan booking Anda untuk {$itemName} (Ref: {$booking->reference_code}) belum dapat kami setujui saat ini.\n\n"
                .'Terima kasih atas pengertian Anda.';
        } elseif ($action === 'return_reminder') {
            $message = "Halo {$renterName},\n\n"
                ."Mengingatkan jadwal sewa untuk {$itemName} (Ref: {$booking->reference_code}) di {$vendorName} dijadwalkan kembali pada {$endDate}.\n\n"
                .'Mohon persiapkan barang dan kelengkapannya. Silakan balas pesan ini jika ada kendala. Terima kasih!';
        } elseif (in_array($action, ['settled', 'closed'])) {
            $settlement = $booking->settlement;
            $depositRemaining = $settlement ? (int) $settlement->deposit_remaining : 0;

            if ($depositRemaining > 0) {
                $refundFormatted = number_format($depositRemaining, 0, ',', '.');
                $message = "Halo {$renterName},\n\n"
                    ."Sewa barang {$itemName} (Ref: {$booking->reference_code}) di {$vendorName} telah SELESAI.\n\n"
                    ."Kondisi barang telah diverifikasi. Sisa uang jaminan (deposit) Anda sebesar Rp {$refundFormatted} akan/telah kami kembalikan.\n\n"
                    ."Terima kasih telah menyewa di {$vendorName}!";
            } elseif ($depositRemaining < 0) {
                $shortfallFormatted = number_format(abs($depositRemaining), 0, ',', '.');
                $message = "Halo {$renterName},\n\n"
                    ."Sewa barang {$itemName} (Ref: {$booking->reference_code}) di {$vendorName} telah SELESAI.\n\n"
                    ."Berdasarkan verifikasi pengembalian, terdapat penyesuaian denda/kerusakan dengan kekurangan bayar sebesar Rp {$shortfallFormatted}.\n"
                    ."Mohon untuk menyelesaikan pembayaran tersebut ke {$vendorName}. Terima kasih!";
            } else {
                $message = "Halo {$renterName},\n\n"
                    ."Sewa barang {$itemName} (Ref: {$booking->reference_code}) di {$vendorName} telah SELESAI.\n\n"
                    ."Barang telah kami terima kembali dalam kondisi baik dan uang jaminan telah disesuaikan (habis pas).\n\n"
                    ."Terima kasih telah menyewa di {$vendorName}!";
            }
        } else {
            $message = "Halo {$renterName},\n\n"
                ."Menghubungi Anda terkait pesanan {$itemName} (Ref: {$booking->reference_code}) di {$vendorName}.\n\n"
                .'Terima kasih!';
        }

        return 'https://wa.me/'.$renterPhone.'?text='.rawurlencode($message);
    }
}
