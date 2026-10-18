<?php

namespace App\Filament\Vendor\Resources\Items\Pages;

use App\Filament\Vendor\Resources\Items\ItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
