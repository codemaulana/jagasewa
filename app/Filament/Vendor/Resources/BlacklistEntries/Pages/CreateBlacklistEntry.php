<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries\Pages;

use App\Filament\Vendor\Resources\BlacklistEntries\BlacklistEntryResource;
use App\Models\BlacklistEntry;
use App\Services\AuditService;
use App\Services\IdentityHashService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateBlacklistEntry extends CreateRecord
{
    protected static string $resource = BlacklistEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');

        if ($vendorId) {
            $data['vendor_id'] = $vendorId;
        }

        $type = $data['identity_type'] ?? BlacklistEntry::TYPE_WA;
        $rawValue = (string) ($data['identity_value'] ?? '');

        $hash = IdentityHashService::hashIdentity($type, $rawValue);

        // Check if hash already exists for this vendor
        $exists = BlacklistEntry::where('vendor_id', $vendorId)
            ->where('identity_hash', $hash)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.identity_value' => 'Identitas ini sudah ada di dalam daftar blacklist toko Anda.',
            ]);
        }

        $data['identity_hash'] = $hash;
        $data['scope'] = BlacklistEntry::SCOPE_PRIVATE;

        unset($data['identity_value']);

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditService::log(
            event: 'blacklist_created',
            entityType: 'blacklist_entry',
            entityId: (string) $this->record->id,
            payload: [
                'identity_type' => $this->record->identity_type,
                'scope' => $this->record->scope,
                'reason' => $this->record->reason,
            ],
            vendorId: $this->record->vendor_id
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
