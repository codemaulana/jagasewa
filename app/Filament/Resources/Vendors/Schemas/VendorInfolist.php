<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VendorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ulid')
                    ->label('ULID'),
                TextEntry::make('name')
                    ->label('Nama Rental / Toko'),
                TextEntry::make('slug')
                    ->label('Slug URL'),
                TextEntry::make('email')
                    ->label('Email'),
                TextEntry::make('wa_number')
                    ->label('Nomor WhatsApp'),
                IconEntry::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i'),
                TextEntry::make('updated_at')
                    ->label('Pembaruan Terakhir')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
