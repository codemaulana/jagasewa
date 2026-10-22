<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum VendorPlan: string implements HasColor, HasIcon, HasLabel
{
    case FREE = 'free';
    case PRO = 'pro';

    public function getLabel(): string
    {
        return match ($this) {
            self::FREE => 'Paket Free',
            self::PRO => 'Paket Pro',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FREE => 'gray',
            self::PRO => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FREE => 'heroicon-o-user',
            self::PRO => 'heroicon-o-sparkles',
        };
    }
}
