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
     * Generate wa.me URL for vendor to contact renter (e.g. approved/rejected action).
     */
    public static function generateRenterChatUrl(Booking $booking, string $action = 'approved'): ?string
    {
        $renter = $booking->renter;
        $renterPhone = self::normalizePhoneNumber($renter?->phone);

        if (! $renterPhone) {
            return null;
        }

        $itemName = $booking->item?->name ?? 'Barang Rental';
        $vendorName = $booking->vendor?->name ?? 'Vendor';
        $renterName = $renter->name ?? 'Kak';
        $startDate = $booking->start_date ? $booking->start_date->format('d M Y') : '-';
        $endDate = $booking->end_date ? $booking->end_date->format('d M Y') : '-';

        if ($action === 'approved') {
            $message = "Halo {$renterName},\n\n"
                ."Kabar baik! Permohonan booking Anda di {$vendorName} telah DISETUJUI:\n"
                ."• No. Referensi: {$booking->reference_code}\n"
                ."• Barang: {$itemName}\n"
                ."• Jadwal: {$startDate} s/d {$endDate}\n\n"
                .'Silakan balas pesan ini untuk koordinasi penyerahan / pengiriman barang. Terima kasih!';
        } else {
            $message = "Halo {$renterName},\n\n"
                ."Mohon maaf, permohonan booking Anda untuk {$itemName} (Ref: {$booking->reference_code}) belum dapat kami setujui saat ini.\n\n"
                .'Terima kasih atas pengertian Anda.';
        }

        return 'https://wa.me/'.$renterPhone.'?text='.rawurlencode($message);
    }
}
