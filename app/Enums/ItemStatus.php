<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ItemStatus: string implements HasColor, HasIcon, HasLabel
{
    case AVAILABLE = 'available';
    case RENTED = 'rented';
    case LAUNDRY = 'laundry';
    case MAINTENANCE = 'maintenance';
    case LOST = 'lost';
    case RETIRED = 'retired';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::RENTED => 'Sedang Disewa',
            self::LAUNDRY => 'Sedang Laundry',
            self::MAINTENANCE => 'Dalam Perbaikan',
            self::LOST => 'Hilang',
            self::RETIRED => 'Nonaktif',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::RENTED => 'info',
            self::LAUNDRY => 'warning',
            self::MAINTENANCE => 'danger',
            self::LOST => 'danger',
            self::RETIRED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::AVAILABLE => 'heroicon-o-check-circle',
            self::RENTED => 'heroicon-o-truck',
            self::LAUNDRY => 'heroicon-o-sparkles',
            self::MAINTENANCE => 'heroicon-o-wrench-screwdriver',
            self::LOST => 'heroicon-o-exclamation-triangle',
            self::RETIRED => 'heroicon-o-archive-box-x-mark',
        };
    }
}
