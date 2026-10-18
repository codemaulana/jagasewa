<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Rental / Toko')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email Toko')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('wa_number')
                    ->label('Nomor WhatsApp')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
