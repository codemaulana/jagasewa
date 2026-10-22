<?php

namespace App\Filament\Vendor\Resources\VendorUsers\Pages;

use App\Filament\Vendor\Resources\VendorUsers\VendorUserResource;
use App\Services\Billing\EntitlementService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorUsers extends ListRecords
{
    protected static string $resource = VendorUserResource::class;

    protected function getHeaderActions(): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        $entitlement = app(EntitlementService::class);
        $currentCount = $vendor ? $vendor->users()->count() : 1;
        $limit = $vendor ? $entitlement->limitFor($vendor, 'staff_users') : 1;
        $canAdd = $limit === -1 || $currentCount < $limit;

        return [
            CreateAction::make()
                ->label('Tambah Staf Baru')
                ->visible($canAdd),
        ];
    }
}
