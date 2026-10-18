<?php

namespace App\Filament\Vendor\Resources\Items\Pages;

use App\Filament\Vendor\Resources\Items\ItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');

        if ($vendorId) {
            $data['vendor_id'] = $vendorId;
        }

        return $data;
    }
}
