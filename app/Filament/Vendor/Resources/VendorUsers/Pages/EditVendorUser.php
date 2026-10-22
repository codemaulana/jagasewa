<?php

namespace App\Filament\Vendor\Resources\VendorUsers\Pages;

use App\Filament\Vendor\Resources\VendorUsers\VendorUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorUser extends EditRecord
{
    protected static string $resource = VendorUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus Staf')
                ->visible(fn ($record) => $record->id !== auth('vendor')->id()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
