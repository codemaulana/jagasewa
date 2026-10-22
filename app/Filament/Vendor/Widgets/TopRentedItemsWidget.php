<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Booking;
use App\Models\Item;
use App\Services\Billing\EntitlementService;
use Filament\Widgets\Widget;

class TopRentedItemsWidget extends Widget
{
    protected string $view = 'filament.vendor.widgets.top-rented-items-widget';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor) {
            return [
                'isPro' => false,
                'items' => collect(),
                'topItem' => null,
            ];
        }

        $entitlement = app(EntitlementService::class);
        $isPro = $entitlement->isPro($vendor);

        $query = Item::where('vendor_id', $vendor->id)
            ->withCount(['bookings' => function ($q) {
                $q->whereIn('status', [
                    Booking::STATUS_ACTIVE,
                    Booking::STATUS_RETURNED,
                    Booking::STATUS_CLOSED,
                ]);
            }])
            ->withSum(['bookings' => function ($q) {
                $q->whereIn('status', [
                    Booking::STATUS_ACTIVE,
                    Booking::STATUS_RETURNED,
                    Booking::STATUS_CLOSED,
                ]);
            }], 'total_price')
            ->orderByDesc('bookings_count')
            ->take(5);

        $items = $query->get();
        $topItem = $items->first();

        return [
            'isPro' => $isPro,
            'items' => $items,
            'topItem' => $topItem,
        ];
    }
}
