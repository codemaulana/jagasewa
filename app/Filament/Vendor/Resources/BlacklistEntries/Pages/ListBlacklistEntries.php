<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries\Pages;

use App\Filament\Vendor\Resources\BlacklistEntries\BlacklistEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlacklistEntries extends ListRecords
{
    protected static string $resource = BlacklistEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Blacklist'),
        ];
    }
}
