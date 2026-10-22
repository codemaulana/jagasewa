<?php

namespace App\Filament\Vendor\Resources\Items\Pages;

use App\Filament\Vendor\Resources\Items\ItemResource;
use App\Services\Billing\EntitlementService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $vendor = auth('vendor')->user()?->vendor;

        // Enforce read-only state for over-limit items on Free tier
        if ($vendor && app(EntitlementService::class)->isItemReadOnly($vendor, $this->record)) {
            throw ValidationException::withMessages([
                'data.name' => 'Barang ini berstatus read-only karena melebihi kuota 5 barang aktif pada paket Free. Upgrade ke Pro untuk mengedit barang ini kembali.',
            ]);
        }

        $isActive = (bool) ($data['is_active'] ?? true);
        $wasActive = (bool) ($this->record->is_active ?? false);

        // SEC-25: Enforce Freemium 5 active items limit if activating an inactive item
        if ($isActive && ! $wasActive && $vendor && ! app(EntitlementService::class)->canAddActiveItem($vendor)) {
            throw ValidationException::withMessages([
                'data.is_active' => 'Batas paket Free tercapai (maksimal 5 barang aktif). Upgrade ke Pro untuk mengaktifkan barang tanpa batas.',
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

        // Clean up any photos removed by vendor from storage
        $originalPhotos = $this->record->photo_paths ?? [];
        $newPhotos = $data['photo_paths'] ?? [];

        if (is_array($originalPhotos) && is_array($newPhotos)) {
            $removedPhotos = array_diff($originalPhotos, $newPhotos);
            if (! empty($removedPhotos)) {
                $hasS3 = ! empty(config('filesystems.disks.s3.key')) && ! empty(config('filesystems.disks.s3.bucket'));
                $diskName = $hasS3 || app()->environment('testing') ? 's3' : 'public';
                $disk = Storage::disk($diskName);

                foreach ($removedPhotos as $removed) {
                    try {
                        $disk->delete($removed);
                    } catch (\Throwable) {
                        // ignore error if file was already removed
                    }
                }
            }
        }

        return $data;
    }
}
