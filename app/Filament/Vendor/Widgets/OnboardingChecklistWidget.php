<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Vendor;
use Filament\Widgets\Widget;

class OnboardingChecklistWidget extends Widget
{
    protected string $view = 'filament.vendor.widgets.onboarding-checklist-widget';

    protected static ?int $sort = 3;

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

        $hasProfile = ! empty($vendor->description) || ! empty($vendor->logo_path) || ! empty($vendor->address);
        $hasItems = Item::where('vendor_id', $vendor->id)->exists();
        $hasBookings = Booking::where('vendor_id', $vendor->id)->exists();

        $completedSteps = ($hasProfile ? 1 : 0) + ($hasItems ? 1 : 0) + ($hasBookings ? 1 : 0);
        $progressPercent = (int) round(($completedSteps / 3) * 100);

        return [
            'vendor' => $vendor,
            'storeUrl' => url('/b/'.$vendor->slug),
            'hasProfile' => $hasProfile,
            'hasItems' => $hasItems,
            'hasBookings' => $hasBookings,
            'completedSteps' => $completedSteps,
            'progressPercent' => $progressPercent,
        ];
    }
}
