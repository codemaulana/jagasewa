<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case RETURNED = 'returned';
    case CLOSED = 'closed';
    case REJECTED = 'rejected';
    case CANCELLED_TIMEOUT = 'cancelled_timeout';
    case CANCELLED_RENTER = 'cancelled_renter';
    case CANCELLED_VENDOR = 'cancelled_vendor';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Konfirmasi',
            self::APPROVED => 'Disetujui',
            self::ACTIVE => 'Sedang Disewa',
            self::RETURNED => 'Dikembalikan',
            self::CLOSED => 'Selesai',
            self::REJECTED => 'Ditolak',
            self::CANCELLED_TIMEOUT => 'Batal (Kedaluwarsa)',
            self::CANCELLED_RENTER => 'Batal oleh Penyewa',
            self::CANCELLED_VENDOR => 'Batal oleh Vendor',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::ACTIVE => 'primary',
            self::RETURNED, self::CLOSED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED_TIMEOUT, self::CANCELLED_RENTER, self::CANCELLED_VENDOR => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::ACTIVE => 'heroicon-o-truck',
            self::RETURNED => 'heroicon-o-arrow-path-rounded-square',
            self::CLOSED => 'heroicon-o-check-badge',
            self::REJECTED => 'heroicon-o-x-circle',
            self::CANCELLED_TIMEOUT, self::CANCELLED_RENTER, self::CANCELLED_VENDOR => 'heroicon-o-x-mark',
        };
    }
}
