<?php

namespace App\Services\Billing;

use App\Models\BlacklistEntry;
use App\Models\Item;
use App\Models\Subscription;
use App\Models\Vendor;

class EntitlementService
{
    public const MAX_FREE_ACTIVE_ITEMS = 5;

    public const MAX_FREE_BLACKLIST_ENTRIES = 5;

    public const MAX_FREE_PHOTOS_PER_ITEM = 2;

    public const MAX_PRO_PHOTOS_PER_ITEM = 8;

    /**
     * Determine if the vendor has an active Pro entitlement.
     * Always evaluated server-side per request (SEC-25).
     */
    public function isPro(?Vendor $vendor): bool
    {
        if (! $vendor) {
            return false;
        }

        return $vendor->subscriptions()
            ->where('plan', Subscription::PLAN_PRO)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            })
            ->exists();
    }

    /**
     * Determine if the vendor can activate or create another active item.
     * Free: Max 5 active items.
     * Pro: Unlimited.
     */
    public function canAddActiveItem(Vendor $vendor): bool
    {
        if ($this->isPro($vendor)) {
            return true;
        }

        $activeCount = Item::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->count();

        return $activeCount < self::MAX_FREE_ACTIVE_ITEMS;
    }

    /**
     * Determine if the vendor can add another blacklist entry.
     * Free: Max 5 entries.
     * Pro: Unlimited.
     */
    public function canAddBlacklistEntry(Vendor $vendor): bool
    {
        if ($this->isPro($vendor)) {
            return true;
        }

        $count = BlacklistEntry::where('vendor_id', $vendor->id)->count();

        return $count < self::MAX_FREE_BLACKLIST_ENTRIES;
    }

    /**
     * Determine if the vendor can export transaction & revenue reports (Excel/CSV).
     * Pro only.
     */
    public function canExportReports(Vendor $vendor): bool
    {
        return $this->isPro($vendor);
    }

    /**
     * Determine if the vendor has white-label branding (hide "Powered by JagaSewa").
     * Pro only.
     */
    public function hasWhiteLabel(Vendor $vendor): bool
    {
        return $this->isPro($vendor);
    }

    /**
     * Determine if the vendor can customize WhatsApp notification templates.
     * Pro only.
     */
    public function canCustomizeWhatsApp(Vendor $vendor): bool
    {
        return $this->isPro($vendor);
    }

    /**
     * Get maximum allowed photos per item.
     * Free: 2, Pro: 8.
     */
    public function getMaxPhotosPerItem(Vendor $vendor): int
    {
        return $this->isPro($vendor) ? self::MAX_PRO_PHOTOS_PER_ITEM : self::MAX_FREE_PHOTOS_PER_ITEM;
    }
}
