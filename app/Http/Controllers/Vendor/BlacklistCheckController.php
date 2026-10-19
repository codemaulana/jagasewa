<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BlacklistEntry;
use App\Services\IdentityHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlacklistCheckController extends Controller
{
    /**
     * Check if a phone number or NIK is on the vendor's private blacklist.
     * Protected by auth:vendor and throttle:blacklist.check (30/minute).
     */
    public function check(Request $request): JsonResponse
    {
        $vendor = $request->user('vendor');
        $vendorId = (int) $vendor->vendor_id;

        $phone = $request->query('phone') ?? $request->query('wa');
        $nik = $request->query('nik');

        if (empty($phone) && empty($nik)) {
            return response()->json([
                'message' => 'Please provide phone or nik query parameter to check.',
                'blacklisted' => false,
                'matches' => [],
            ], 422);
        }

        $hashes = [];
        if (! empty($phone)) {
            $hashes[] = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, (string) $phone);
        }
        if (! empty($nik)) {
            $hashes[] = IdentityHashService::hashIdentity(IdentityHashService::TYPE_NIK, (string) $nik);
        }

        $matches = BlacklistEntry::query()
            ->where('vendor_id', $vendorId)
            ->whereIn('identity_hash', $hashes)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get(['id', 'category', 'reason', 'severity', 'created_at']);

        $isBlacklisted = $matches->isNotEmpty();

        if ($isBlacklisted) {
            Log::info("Blacklist hit on check for Vendor [{$vendorId}]", [
                'vendor_id' => $vendorId,
                'matches_count' => $matches->count(),
            ]);
        }

        return response()->json([
            'blacklisted' => $isBlacklisted,
            'matches_count' => $matches->count(),
            'matches' => $matches,
        ]);
    }
}
