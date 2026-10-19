<?php

namespace App\Filament\Vendor\Resources\Items\Pages;

use App\Filament\Vendor\Resources\Items\ItemResource;
use App\Services\Billing\EntitlementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        $vendorId = $vendor?->id ?? session('vendor_id');

        if ($vendorId) {
            $data['vendor_id'] = $vendorId;
        }

        $isActive = (bool) ($data['is_active'] ?? true);

        // SEC-25: Enforce Freemium 5 active items limit on Free tier
        if ($isActive && $vendor && ! app(EntitlementService::class)->canAddActiveItem($vendor)) {
            throw ValidationException::withMessages([
                'data.name' => 'Batas paket Free tercapai (maksimal 5 barang aktif). Upgrade ke Pro untuk menambah barang tanpa batas.',
            ]);
        }

        return $data;
    }
}
