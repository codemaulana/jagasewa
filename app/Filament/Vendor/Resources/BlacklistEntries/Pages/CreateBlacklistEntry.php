<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries\Pages;

use App\Filament\Vendor\Resources\BlacklistEntries\BlacklistEntryResource;
use App\Models\BlacklistEntry;
use App\Services\AuditService;
use App\Services\Billing\EntitlementService;
use App\Services\IdentityHashService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateBlacklistEntry extends CreateRecord
{
    protected static string $resource = BlacklistEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        $vendorId = $vendor?->id ?? session('vendor_id');

        if ($vendorId) {
            $data['vendor_id'] = $vendorId;
        }

        // SEC-25: Enforce Freemium 5 blacklist entries limit on Free tier
        if ($vendor && ! app(EntitlementService::class)->canAddBlacklistEntry($vendor)) {
            throw ValidationException::withMessages([
                'data.identity_value' => 'Batas paket Free tercapai (maksimal 5 entri blacklist). Upgrade ke Pro untuk menambahkan blacklist tanpa batas.',
            ]);
        }

        $type = $data['identity_type'] ?? BlacklistEntry::TYPE_WA;
        $rawValue = (string) ($data['identity_value'] ?? '');
        $requestedScope = $data['scope'] ?? BlacklistEntry::SCOPE_PRIVATE;

        // If requesting shared blacklist, check Pro entitlement
        if ($requestedScope === BlacklistEntry::SCOPE_SHARED && (! $vendor || ! $vendor->isPro())) {
            throw ValidationException::withMessages([
                'data.scope' => 'Fitur lapor blacklist bersama komunitas hanya tersedia untuk vendor paket Pro. Silakan pilih "Internal Toko Anda Saja" atau upgrade akun.',
            ]);
        }

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
        $data['masked_identity'] = BlacklistEntry::maskValue($type, $rawValue);
        $data['scope'] = $requestedScope;
        $data['verification_status'] = BlacklistEntry::STATUS_VERIFIED;

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
