<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\BlacklistEntry;
use App\Models\Item;
use App\Models\Vendor;
use App\Services\Billing\EntitlementService;
use Filament\Widgets\Widget;

class StoreLinkAndPlanWidget extends Widget
{
    protected string $view = 'filament.vendor.widgets.store-link-and-plan-widget';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getVendor(): ?Vendor
    {
        return auth('vendor')->user()?->vendor;
    }

    protected function getViewData(): array
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            return [];
        }

        $entitlement = app(EntitlementService::class);
        $isPro = $entitlement->isPro($vendor);
        $activeItems = Item::where('vendor_id', $vendor->id)->where('is_active', true)->count();
        $totalItems = Item::where('vendor_id', $vendor->id)->count();
        $blacklistCount = BlacklistEntry::where('vendor_id', $vendor->id)->count();
        $activeSubscription = $vendor->activeSubscription()->first();
        $pendingSubscription = $vendor->pendingSubscription()->first();

        return [
            'vendor' => $vendor,
            'storeUrl' => url('/b/'.$vendor->slug),
            'isPro' => $isPro,
            'activeItems' => $activeItems,
            'totalItems' => $totalItems,
            'maxActiveItems' => $isPro ? 'Tanpa Batas' : EntitlementService::MAX_FREE_ACTIVE_ITEMS,
            'blacklistCount' => $blacklistCount,
            'maxBlacklist' => $isPro ? 'Tanpa Batas' : EntitlementService::MAX_FREE_BLACKLIST_ENTRIES,
            'activeSubscription' => $activeSubscription,
            'pendingSubscription' => $pendingSubscription,
            'inGracePeriod' => $entitlement->isInGracePeriod($vendor),
            'isNearExpiration' => $entitlement->isNearExpiration($vendor),
            'daysUntilExpiration' => $entitlement->getDaysUntilExpiration($vendor),
        ];
    }
}
