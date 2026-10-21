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

        // Enforce maximum photos per item based on plan (Free: 5, Pro: 8)
        if ($vendor) {
            $maxPhotos = app(EntitlementService::class)->getMaxPhotosPerItem($vendor);
            if (! empty($data['photo_paths']) && is_array($data['photo_paths']) && count($data['photo_paths']) > $maxPhotos) {
                throw ValidationException::withMessages([
                    'data.photo_paths' => "Batas kuota foto terlampaui (maksimal {$maxPhotos} foto).",
                ]);
            }
        }

        return $data;
    }
}
