<?php

namespace App\Filament\Vendor\Resources\Renters\Pages;

use App\Filament\Vendor\Resources\Renters\RenterResource;
use Filament\Resources\Pages\ListRecords;

class ListRenters extends ListRecords
{
    protected static string $resource = RenterResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
