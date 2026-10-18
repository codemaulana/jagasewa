<?php

namespace App\Filament\Vendor\Resources\Items\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Barang'),
                                TextEntry::make('slug')
                                    ->label('Slug URL'),
                                TextEntry::make('ulid')
                                    ->label('ULID Identitas'),
                            ]),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Tidak ada deskripsi.')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                    ]),

                Section::make('Atribut Rental')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('size')
                                    ->label('Ukuran / Size')
                                    ->placeholder('-')
                                    ->badge(),
                                TextEntry::make('included_items')
                                    ->label('Kelengkapan Set')
                                    ->badge()
                                    ->placeholder('-'),
                            ]),
                        TextEntry::make('care_instructions')
                            ->label('Instruksi Perawatan & Cuci')
                            ->placeholder('Tidak ada instruksi khusus.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Finansial & Buffer Kalender')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('price_per_day')
                                    ->label('Harga Sewa / Hari')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                                TextEntry::make('deposit_amount')
                                    ->label('Deposit Jaminan')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                                TextEntry::make('fine_per_day')
                                    ->label('Denda Keterlambatan / Hari')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('buffer_before')
                                    ->label('Buffer Sebelum Sewa')
                                    ->formatStateUsing(fn ($state) => "{$state} hari"),
                                TextEntry::make('buffer_after')
                                    ->label('Buffer Sesudah Sewa')
                                    ->formatStateUsing(fn ($state) => "{$state} hari"),
                            ]),
                    ]),

                Section::make('Galeri Foto')
                    ->schema([
                        ImageEntry::make('photo_paths')
                            ->label('Foto Barang')
                            ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
