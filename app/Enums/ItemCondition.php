<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ItemCondition: string implements HasColor, HasIcon, HasLabel
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case DAMAGED = 'damaged';

    public function getLabel(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Sangat Baik (Seperti Baru)',
            self::GOOD => 'Baik (Normal)',
            self::FAIR => 'Cukup (Ada Bekas Pakai)',
            self::DAMAGED => 'Rusak / Perlu Perbaikan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EXCELLENT, self::GOOD => 'success',
            self::FAIR => 'warning',
            self::DAMAGED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::EXCELLENT => 'heroicon-o-sparkles',
            self::GOOD => 'heroicon-o-check-circle',
            self::FAIR => 'heroicon-o-information-circle',
            self::DAMAGED => 'heroicon-o-exclamation-circle',
        };
    }
}
