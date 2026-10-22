<?php

namespace App\Services;

use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorBlacklistCheck;
use App\Services\Billing\EntitlementService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BlacklistService
{
    /**
     * Get blacklist entries matching a renter for a specific vendor (including verified shared entries).
     *
     * @return Collection<int, BlacklistEntry>
     */
    public function getMatchesForRenter(Renter $renter, int $vendorId): Collection
    {
        $hashes = [];

        // 1. WhatsApp / Phone Hash
        if (! empty($renter->phone_hash)) {
            $hashes[] = $renter->phone_hash;
        }
        if (! empty($renter->phone)) {
            $hashes[] = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, $renter->phone);
        }

        // 2. NIK Hash
        if (! empty($renter->nik_hash)) {
            $hashes[] = $renter->nik_hash;
        }
        if (! empty($renter->nik)) {
            $hashes[] = IdentityHashService::hashIdentity(IdentityHashService::TYPE_NIK, $renter->nik);
        }

        // 3. Instagram Hash (if available)
        if (! empty($renter->instagram_handle_hash)) {
            $hashes[] = $renter->instagram_handle_hash;
        }

        $hashes = array_values(array_unique(array_filter($hashes)));

        if (empty($hashes)) {
            return collect();
        }

        return BlacklistEntry::query()
            ->where(function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId)
                    ->orWhere(function ($q) {
                        $q->where('scope', BlacklistEntry::SCOPE_SHARED)
                            ->where('verification_status', BlacklistEntry::STATUS_VERIFIED);
                    });
            })
            ->whereIn('identity_hash', $hashes)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Get blacklist entries matching a booking's renter for the booking's vendor.
     *
     * @return Collection<int, BlacklistEntry>
     */
    public function getMatchesForBooking(Booking $booking): Collection
    {
        if (! $booking->renter) {
            return collect();
        }

        return $this->getMatchesForRenter($booking->renter, (int) $booking->vendor_id);
    }

    /**
     * Determine if a renter is blacklisted by a specific vendor or in community blacklist.
     */
    public function isBlacklisted(Renter $renter, int $vendorId): bool
    {
        return $this->getMatchesForRenter($renter, $vendorId)->isNotEmpty();
    }

    /**
     * Determine if a booking's renter is blacklisted.
     */
    public function isBookingBlacklisted(Booking $booking): bool
    {
        return $this->getMatchesForBooking($booking)->isNotEmpty();
    }

    /**
     * Get remaining checks for the vendor in current month.
     * Returns -1 if unlimited (Pro).
     */
    public function getRemainingMonthlyChecks(Vendor $vendor): int
    {
        $entitlement = app(EntitlementService::class);
        $limit = $entitlement->limitFor($vendor, 'blacklist_checks_per_month');

        if ($limit === -1) {
            return -1; // Unlimited for Pro
        }

        $tracker = VendorBlacklistCheck::forCurrentMonth($vendor);

        return max(0, $limit - (int) $tracker->checks_count);
    }

    /**
     * Search blacklist database across vendor's private records and shared community records.
     * Enforces monthly quota for Free plan (10 checks/month).
     *
     * @throws ValidationException
     */
    public function searchBlacklist(Vendor $vendor, string $identityType, string $rawValue): array
    {
        $cleaned = trim($rawValue);
        if ($cleaned === '') {
            return [
                'success' => false,
                'message' => 'Identitas pencarian tidak boleh kosong.',
                'matches' => [],
            ];
        }

        // 1. Enforce quota for Free plan
        $isPro = $vendor->isPro();
        $tracker = VendorBlacklistCheck::forCurrentMonth($vendor);
        $limit = app(EntitlementService::class)->limitFor($vendor, 'blacklist_checks_per_month');

        if (! $isPro && $limit !== -1 && $tracker->checks_count >= $limit) {
            throw ValidationException::withMessages([
                'identity' => 'Batas kuota pencarian blacklist 10 kali/bulan untuk paket Free telah habis. Silakan perpanjang atau upgrade ke paket Pro untuk pencarian tanpa batas.',
            ]);
        }

        // Increment check counter
        $tracker->increment('checks_count');

        // 2. Hash input according to identity type
        $hash = IdentityHashService::hashIdentity($identityType, $cleaned);
        $maskedInput = BlacklistEntry::maskValue($identityType, $cleaned);

        // 3. Query matching entries
        $entries = BlacklistEntry::query()
            ->where('identity_hash', $hash)
            ->where(function ($query) use ($vendor) {
                $query->where('vendor_id', $vendor->id)
                    ->orWhere(function ($q) {
                        $q->where('scope', BlacklistEntry::SCOPE_SHARED)
                            ->where('verification_status', BlacklistEntry::STATUS_VERIFIED);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        // 4. Secure result transformation (NEVER expose other vendor's raw data / PII)
        $matches = $entries->map(function (BlacklistEntry $entry) use ($vendor) {
            $isOwn = ((int) $entry->vendor_id === (int) $vendor->id);

            return [
                'ulid' => $entry->ulid,
                'is_own' => $isOwn,
                'source' => $isOwn ? 'Blacklist Toko Anda' : 'Laporan Komunitas Terverifikasi',
                'identity_type' => $entry->identity_type,
                'masked_identity' => $entry->masked_identity ?: '******',
                'reason' => $entry->reason,
                'verification_status' => $entry->verification_status,
                'is_disputed' => (bool) $entry->is_disputed,
                'reported_at' => $entry->created_at ? $entry->created_at->format('d M Y') : '-',
            ];
        })->toArray();

        return [
            'success' => true,
            'queried_type' => $identityType,
            'masked_query' => $maskedInput,
            'remaining_checks' => $this->getRemainingMonthlyChecks($vendor),
            'has_match' => count($matches) > 0,
            'matches' => $matches,
        ];
    }

    /**
     * Submit a new report to Shared Blacklist (Pro Feature).
     *
     * @param  array{identity_type: string, raw_identity: string, reason: string, evidence?: string, evidence_file_path?: string}  $data
     *
     * @throws ValidationException
     */
    public function reportToSharedBlacklist(Vendor $vendor, array $data): BlacklistEntry
    {
        // Enforce Pro entitlement
        if (! $vendor->isPro() && ! app(EntitlementService::class)->canUse($vendor, 'shared_blacklist_report')) {
            throw ValidationException::withMessages([
                'plan' => 'Fitur lapor blacklist bersama hanya tersedia untuk vendor paket Pro.',
            ]);
        }

        $type = $data['identity_type'];
        $raw = trim($data['raw_identity']);
        $hash = IdentityHashService::hashIdentity($type, $raw);
        $masked = BlacklistEntry::maskValue($type, $raw);

        // Check if already reported by this vendor
        $existing = BlacklistEntry::where('vendor_id', $vendor->id)
            ->where('identity_hash', $hash)
            ->first();

        if ($existing) {
            $existing->update([
                'scope' => BlacklistEntry::SCOPE_SHARED,
                'verification_status' => BlacklistEntry::STATUS_VERIFIED,
                'reason' => $data['reason'] ?? $existing->reason,
                'evidence' => $data['evidence'] ?? $existing->evidence,
                'evidence_file_path' => $data['evidence_file_path'] ?? $existing->evidence_file_path,
                'masked_identity' => $masked,
            ]);

            return $existing;
        }

        return BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => $type,
            'identity_hash' => $hash,
            'masked_identity' => $masked,
            'scope' => BlacklistEntry::SCOPE_SHARED,
            'verification_status' => BlacklistEntry::STATUS_VERIFIED,
            'reason' => $data['reason'],
            'evidence' => $data['evidence'] ?? null,
            'evidence_file_path' => $data['evidence_file_path'] ?? null,
        ]);
    }

    /**
     * Submit a dispute for a blacklist entry.
     */
    public function submitDispute(string $ulid, string $reason): bool
    {
        $entry = BlacklistEntry::where('ulid', $ulid)->first();
        if (! $entry) {
            return false;
        }

        return $entry->update([
            'is_disputed' => true,
            'dispute_reason' => $reason,
            'disputed_at' => now('Asia/Jakarta'),
            'dispute_status' => 'pending',
            'verification_status' => BlacklistEntry::STATUS_DISPUTED,
        ]);
    }
}
