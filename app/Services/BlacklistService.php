<?php

namespace App\Services;

use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\Renter;
use Illuminate\Support\Collection;

class BlacklistService
{
    /**
     * Get blacklist entries matching a renter for a specific vendor.
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
            ->where('vendor_id', $vendorId)
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
     * Determine if a renter is blacklisted by a specific vendor.
     */
    public function isBlacklisted(Renter $renter, int $vendorId): bool
    {
        return $this->getMatchesForRenter($renter, $vendorId)->isNotEmpty();
    }

    /**
     * Determine if a booking's renter is blacklisted by the vendor.
     */
    public function isBookingBlacklisted(Booking $booking): bool
    {
        return $this->getMatchesForBooking($booking)->isNotEmpty();
    }
}
