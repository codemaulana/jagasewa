<?php

namespace App\Filament\Vendor\Resources\VendorUsers\Pages;

use App\Filament\Vendor\Resources\VendorUsers\VendorUserResource;
use App\Services\Billing\EntitlementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateVendorUser extends CreateRecord
{
    protected static string $resource = VendorUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor) {
            abort(403);
        }

        $entitlement = app(EntitlementService::class);
        $currentCount = $vendor->users()->count();
        $limit = $entitlement->limitFor($vendor, 'staff_users');

        if ($limit !== -1 && $currentCount >= $limit) {
            throw ValidationException::withMessages([
                'data.email' => "Batas kuota staf toko untuk paket Free Anda telah tercapai (maksimal {$limit} akun). Upgrade ke paket Pro untuk menambah hingga 5 staf operasional.",
            ]);
        }

        $data['vendor_id'] = $vendor->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
