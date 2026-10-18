<?php

namespace App\Filament\Vendor\Resources\Bookings\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'Daftar Booking Masuk';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola permintaan booking sewa barang dari calon penyewa.';
    }
}
