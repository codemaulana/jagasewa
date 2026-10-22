<?php

namespace App\Services\Billing;

use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Subscription;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EntitlementService
{
    public const MAX_FREE_ACTIVE_ITEMS = 5;

    public const MAX_FREE_BLACKLIST_ENTRIES = 5;

    public const MAX_FREE_PHOTOS_PER_ITEM = 5;

    public const MAX_PRO_PHOTOS_PER_ITEM = 8;

    /**
     * Determine if the vendor has an active Pro entitlement or is within the grace period.
     * Always evaluated server-side per request (SEC-25).
     */
    public function isPro(?Vendor $vendor): bool
    {
        if (! $vendor || ! $vendor->exists || ! $vendor->id) {
            return false;
        }

        $graceDays = (int) config('plans.grace_period_days', 3);

        return $vendor->subscriptions()
            ->where('plan', Subscription::PLAN_PRO)
            ->where(function ($query) use ($graceDays) {
                // 1. Active and not expired
                $query->where(function ($sub) {
                    $sub->where('status', Subscription::STATUS_ACTIVE)
                        ->where(function ($date) {
                            $date->whereNull('current_period_end')
                                ->orWhere('current_period_end', '>', now());
                        });
                })
                // 2. Or in past_due within grace period
                    ->orWhere(function ($sub) use ($graceDays) {
                        $sub->where('status', Subscription::STATUS_PAST_DUE)
                            ->whereNotNull('current_period_end')
                            ->where('current_period_end', '>', now()->subDays($graceDays));
                    });
            })
            ->exists();
    }

    /**
     * Check if a specific feature is enabled for the vendor's plan.
     */
    public function canUse(?Vendor $vendor, string $feature): bool
    {
        if (! $vendor) {
            return false;
        }

        $planKey = $this->isPro($vendor) ? 'pro' : 'free';
        $features = config("plans.plans.{$planKey}.features", []);

        return (bool) ($features[$feature] ?? false);
    }

    /**
     * Get the numeric limit for a given key under the vendor's plan.
     * Returns -1 for unlimited.
     */
    public function limitFor(?Vendor $vendor, string $key): int
    {
        if (! $vendor) {
            return (int) config("plans.plans.free.limits.{$key}", 0);
        }

        $planKey = $this->isPro($vendor) ? 'pro' : 'free';

        return (int) config("plans.plans.{$planKey}.limits.{$key}", -1);
    }

    /**
     * Get the current usage count for a given resource.
     */
    public function usageFor(?Vendor $vendor, string $key): int
    {
        if (! $vendor || ! $vendor->id) {
            return 0;
        }

        return match ($key) {
            'active_items' => Item::where('vendor_id', $vendor->id)->where('is_active', true)->count(),
            'total_items' => Item::where('vendor_id', $vendor->id)->count(),
            'blacklist_entries' => BlacklistEntry::where('vendor_id', $vendor->id)->count(),
            'staff_users' => $vendor->users()->count(),
            'monthly_bookings' => Booking::where('vendor_id', $vendor->id)
                ->whereMonth('created_at', now('Asia/Jakarta')->month)
                ->whereYear('created_at', now('Asia/Jakarta')->year)
                ->count(),
            default => 0,
        };
    }

    /**
     * Determine if a resource usage currently exceeds the plan limit.
     */
    public function isOverLimit(?Vendor $vendor, string $key): bool
    {
        $limit = $this->limitFor($vendor, $key);
        if ($limit === -1) {
            return false;
        }

        return $this->usageFor($vendor, $key) > $limit;
    }

    /**
     * Determine if a specific item is read-only due to exceeding Free tier limit upon downgrade.
     */
    public function isItemReadOnly(?Vendor $vendor, ?Item $item): bool
    {
        if (! $vendor || ! $item || ! $item->is_active) {
            return false;
        }

        if ($this->isPro($vendor)) {
            return false;
        }

        $limit = $this->limitFor($vendor, 'active_items');
        if ($limit === -1) {
            return false;
        }

        $activeItemIds = Item::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        $overLimitIds = array_slice($activeItemIds, $limit);

        return in_array($item->id, $overLimitIds, true);
    }

    /**
     * Determine if the vendor can activate or create another active item.
     */
    public function canAddActiveItem(Vendor $vendor): bool
    {
        $limit = $this->limitFor($vendor, 'active_items');
        if ($limit === -1) {
            return true;
        }

        return $this->usageFor($vendor, 'active_items') < $limit;
    }

    /**
     * Determine if the vendor can add another blacklist entry.
     */
    public function canAddBlacklistEntry(Vendor $vendor): bool
    {
        $limit = $this->limitFor($vendor, 'blacklist_entries');
        if ($limit === -1) {
            return true;
        }

        return $this->usageFor($vendor, 'blacklist_entries') < $limit;
    }

    /**
     * Determine if the vendor can export transaction & revenue reports (Excel/CSV).
     */
    public function canExportReports(Vendor $vendor): bool
    {
        return $this->canUse($vendor, 'export_reports');
    }

    /**
     * Determine if the vendor has white-label branding privileges.
     */
    public function hasWhiteLabel(Vendor $vendor): bool
    {
        return $this->canUse($vendor, 'white_label');
    }

    /**
     * Determine if the vendor can customize WhatsApp notification templates.
     */
    public function canCustomizeWhatsApp(Vendor $vendor): bool
    {
        return $this->canUse($vendor, 'custom_wa_templates');
    }

    /**
     * Get maximum allowed photos per item.
     */
    public function getMaxPhotosPerItem(Vendor $vendor): int
    {
        return $this->limitFor($vendor, 'photos_per_item');
    }

    /**
     * Check if vendor is in grace period (expired <= 3 days ago).
     */
    public function isInGracePeriod(?Vendor $vendor): bool
    {
        if (! $vendor) {
            return false;
        }

        $sub = $vendor->subscriptions()
            ->where('plan', Subscription::PLAN_PRO)
            ->where('status', Subscription::STATUS_PAST_DUE)
            ->latest()
            ->first();

        if (! $sub || ! $sub->current_period_end) {
            return false;
        }

        $graceDays = (int) config('plans.grace_period_days', 3);
        $periodEnd = Carbon::parse($sub->current_period_end)->timezone('Asia/Jakarta');

        return now('Asia/Jakarta')->greaterThan($periodEnd)
            && now('Asia/Jakarta')->lessThanOrEqualTo($periodEnd->copy()->addDays($graceDays));
    }

    /**
     * Get days remaining until active subscription expires.
     * Returns null if Free or no end date.
     */
    public function getDaysUntilExpiration(?Vendor $vendor): ?int
    {
        if (! $vendor) {
            return null;
        }

        $sub = $vendor->activeSubscription;
        if (! $sub || ! $sub->current_period_end) {
            return null;
        }

        $diff = now('Asia/Jakarta')->diffInDays($sub->current_period_end, false);

        return (int) $diff;
    }

    /**
     * Check if subscription is near expiration (<= 7 days).
     */
    public function isNearExpiration(?Vendor $vendor): bool
    {
        $days = $this->getDaysUntilExpiration($vendor);

        return $days !== null && $days <= 7 && $days >= 0;
    }

    /**
     * Get full entitlement payload for the vendor.
     *
     * @return array<string, mixed>
     */
    public function getEntitlements(?Vendor $vendor): array
    {
        if (! $vendor) {
            return [];
        }

        $isPro = $this->isPro($vendor);
        $planKey = $isPro ? 'pro' : 'free';
        $planConfig = config("plans.plans.{$planKey}", []);
        $activeSub = $vendor->activeSubscription;
        $inGrace = $this->isInGracePeriod($vendor);
        $daysUntilExp = $this->getDaysUntilExpiration($vendor);

        return [
            'vendor_id' => $vendor->id,
            'vendor_ulid' => $vendor->ulid,
            'plan' => $planKey,
            'plan_name' => $planConfig['display_name'] ?? ucfirst($planKey),
            'status' => $isPro ? ($inGrace ? 'grace_period' : 'active') : 'free',
            'is_pro' => $isPro,
            'in_grace_period' => $inGrace,
            'near_expiration' => $this->isNearExpiration($vendor),
            'days_until_expiration' => $daysUntilExp,
            'current_period_end' => $activeSub?->current_period_end?->toIso8601String(),
            'current_period_end_formatted' => $activeSub?->current_period_end?->translatedFormat('d F Y, H:i').' WIB',
            'limits' => [
                'active_items' => $this->limitFor($vendor, 'active_items'),
                'photos_per_item' => $this->limitFor($vendor, 'photos_per_item'),
                'blacklist_entries' => $this->limitFor($vendor, 'blacklist_entries'),
                'monthly_bookings' => $this->limitFor($vendor, 'monthly_bookings'),
                'staff_users' => $this->limitFor($vendor, 'staff_users'),
            ],
            'usage' => [
                'active_items' => $this->usageFor($vendor, 'active_items'),
                'total_items' => $this->usageFor($vendor, 'total_items'),
                'blacklist_entries' => $this->usageFor($vendor, 'blacklist_entries'),
                'monthly_bookings' => $this->usageFor($vendor, 'monthly_bookings'),
                'staff_users' => $this->usageFor($vendor, 'staff_users'),
            ],
            'features' => $planConfig['features'] ?? [],
            'can_add_item' => $this->canAddActiveItem($vendor),
            'can_add_blacklist' => $this->canAddBlacklistEntry($vendor),
            'has_over_limit_items' => $this->isOverLimit($vendor, 'active_items'),
        ];
    }

    /**
     * Apply downgrade to Free tier without deleting data.
     * Over-limit items remain stored but are flagged read-only / blocked from additions.
     */
    public function applyDowngrade(Vendor $vendor): void
    {
        // Data is strictly preserved (tidak dihapus).
        // Excess active items are marked as read-only / flagged for vendor notification.
        Log::info("Vendor [{$vendor->ulid}] downgraded to Free tier. Data preserved safely.");
    }
}
