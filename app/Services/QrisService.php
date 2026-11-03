<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Subscription;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\DB;

class QrisService
{
    /**
     * Generate dynamic QRIS string and base64 SVG QR code image.
     *
     * @return array<string, mixed>
     */
    public function generateDynamicQris(
        int $amount,
        string $referenceCode,
        string $merchantName = 'JAGASEWA RENTAL',
        string $city = 'JAKARTA',
        ?string $nmid = null,
        int $expiredMinutes = 30
    ): array {
        $cleanMerchant = strtoupper(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $merchantName) ?: 'JAGASEWA', 0, 25));
        $cleanCity = strtoupper(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $city) ?: 'JAKARTA', 0, 15));
        $cleanNmid = $nmid ?: 'ID1020000000000';

        // 1. Build EMVCo payload without CRC
        $payload = $this->buildEmvcoPayload($amount, $referenceCode, $cleanMerchant, $cleanCity, $cleanNmid);

        // 2. Append Tag 63 (CRC16)
        $payloadWithTag63 = $payload.'6304';
        $checksum = $this->calculateCrc16($payloadWithTag63);
        $fullQrisString = $payloadWithTag63.$checksum;

        // 3. Render QR Code to SVG Data URI
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'imageBase64' => true,
            'scale' => 6,
        ]);

        $qrCode = new QRCode($options);
        $qrImageDataUri = $qrCode->render($fullQrisString);

        return [
            'success' => true,
            'reference_code' => $referenceCode,
            'amount' => $amount,
            'qris_content' => $fullQrisString,
            'qr_image_data_uri' => $qrImageDataUri,
            'merchant_name' => $cleanMerchant,
            'city' => $cleanCity,
            'expires_at' => Carbon::now('Asia/Jakarta')->addMinutes($expiredMinutes)->toIso8601String(),
        ];
    }

    /**
     * Build the raw EMVCo payload according to ASPI / Bank Indonesia QRIS specifications.
     */
    protected function buildEmvcoPayload(
        int $amount,
        string $referenceCode,
        string $merchantName,
        string $city,
        string $nmid
    ): string {
        $emv = '';

        // Tag 00: Payload Format Indicator (01)
        $emv .= $this->formatTag('00', '01');

        // Tag 01: Point of Initiation Method (12 = Dynamic QR)
        $emv .= $this->formatTag('01', '12');

        // Tag 26: Merchant Account Information (QRIS)
        $sub26 = $this->formatTag('00', 'ID.CO.QRIS.WWW')
            .$this->formatTag('01', $nmid)
            .$this->formatTag('02', 'UME');
        $emv .= $this->formatTag('26', $sub26);

        // Tag 52: Merchant Category Code
        $emv .= $this->formatTag('52', '5812');

        // Tag 53: Transaction Currency (360 = IDR)
        $emv .= $this->formatTag('53', '360');

        // Tag 54: Transaction Amount
        $emv .= $this->formatTag('54', (string) max(1, $amount));

        // Tag 58: Country Code (ID)
        $emv .= $this->formatTag('58', 'ID');

        // Tag 59: Merchant Name
        $emv .= $this->formatTag('59', $merchantName);

        // Tag 60: Merchant City
        $emv .= $this->formatTag('60', $city);

        // Tag 62: Additional Data Field (Reference Bill Number)
        $sub62 = $this->formatTag('01', $referenceCode);
        $emv .= $this->formatTag('62', $sub62);

        return $emv;
    }

    /**
     * Format an EMVCo TLV (Tag-Length-Value) chunk.
     */
    protected function formatTag(string $tag, string $value): string
    {
        $len = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);

        return $tag.$len.$value;
    }

    /**
     * Calculate 16-bit CRC checksum (CRC-16/CCITT-FALSE).
     * Polynomial: 0x1021, Initial: 0xFFFF.
     */
    public function calculateCrc16(string $payload): string
    {
        $crc = 0xFFFF;
        $len = strlen($payload);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Mark a booking or subscription as settled/paid via QRIS.
     *
     * @return array<string, mixed>
     */
    public function settlePayment(string $referenceCode, string $provider = 'qris', ?string $externalId = null): array
    {
        return DB::transaction(function () use ($referenceCode, $provider, $externalId) {
            $now = Carbon::now('Asia/Jakarta');

            // 1. Try resolving as Booking
            $booking = Booking::where('reference_code', $referenceCode)
                ->lockForUpdate()
                ->first();

            if ($booking) {
                if (! $booking->is_paid_in_full) {
                    $booking->is_paid_in_full = true;
                    $booking->paid_in_full_at = $now;
                    $refInfo = $externalId ? " (Ref: {$externalId})" : '';
                    $booking->payment_notes = "Pelunasan via QRIS Dinamis [{$provider}]{$refInfo}";
                    $booking->lock_version = ($booking->lock_version ?? 0) + 1;
                    $booking->save();

                    AuditService::log(
                        'booking_qris_settled',
                        'booking',
                        (string) $booking->id,
                        [
                            'reference_code' => $booking->reference_code,
                            'provider' => $provider,
                            'amount' => $booking->total_price + $booking->deposit_paid,
                            'paid_at' => $now->toIso8601String(),
                        ],
                        $booking->vendor_id
                    );
                }

                return [
                    'success' => true,
                    'type' => 'booking',
                    'status' => 'settled',
                    'reference_code' => $booking->reference_code,
                    'paid_at' => $booking->paid_in_full_at?->toIso8601String(),
                ];
            }

            // 2. Try resolving as Subscription
            $subscription = Subscription::where('gateway_subscription_id', $referenceCode)
                ->orWhere('ulid', $referenceCode)
                ->lockForUpdate()
                ->first();

            if ($subscription) {
                if ($subscription->status !== Subscription::STATUS_ACTIVE) {
                    $subscription->status = Subscription::STATUS_ACTIVE;
                    $subscription->current_period_start = $subscription->current_period_start ?? $now;
                    $cycle = $subscription->billing_cycle ?? Subscription::CYCLE_MONTHLY;
                    $subscription->current_period_end = match ($cycle) {
                        Subscription::CYCLE_ANNUALLY => $now->copy()->addYear(),
                        Subscription::CYCLE_QUARTERLY => $now->copy()->addMonths(3),
                        default => $now->copy()->addMonth(),
                    };
                    $subscription->save();

                    AuditService::log(
                        'subscription_qris_settled',
                        'subscription',
                        (string) $subscription->id,
                        [
                            'gateway_subscription_id' => $subscription->gateway_subscription_id,
                            'provider' => $provider,
                            'paid_at' => $now->toIso8601String(),
                        ],
                        $subscription->vendor_id
                    );
                }

                return [
                    'success' => true,
                    'type' => 'subscription',
                    'status' => 'settled',
                    'reference_code' => $subscription->gateway_subscription_id ?? (string) $subscription->ulid,
                    'paid_at' => $now->toIso8601String(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Pesanan dengan kode referensi tersebut tidak ditemukan.',
            ];
        });
    }

    /**
     * Check if a transaction is already settled.
     */
    public function isSettled(string $referenceCode): bool
    {
        $booking = Booking::where('reference_code', $referenceCode)->first();
        if ($booking) {
            return (bool) $booking->is_paid_in_full;
        }

        $subscription = Subscription::where('gateway_subscription_id', $referenceCode)
            ->orWhere('ulid', $referenceCode)
            ->first();
        if ($subscription) {
            return $subscription->status === Subscription::STATUS_ACTIVE;
        }

        return false;
    }
}
