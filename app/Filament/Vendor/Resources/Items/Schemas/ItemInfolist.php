<?php

namespace App\Filament\Vendor\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama & Status Operasional')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Barang')
                                    ->weight('bold'),
                                TextEntry::make('category')
                                    ->label('Kategori')
                                    ->badge(),
                                TextEntry::make('item_code')
                                    ->label('Kode Barang')
                                    ->fontFamily('mono')
                                    ->placeholder('-'),
                                TextEntry::make('unit_status')
                                    ->label('Status Unit')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        Item::UNIT_STATUS_AVAILABLE => 'Tersedia',
                                        Item::UNIT_STATUS_RENTED => 'Disewa',
                                        Item::UNIT_STATUS_LAUNDRY => 'Laundry',
                                        Item::UNIT_STATUS_MAINTENANCE => 'Perbaikan',
                                        default => 'Tersedia',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        Item::UNIT_STATUS_AVAILABLE => 'success',
                                        Item::UNIT_STATUS_RENTED => 'info',
                                        Item::UNIT_STATUS_LAUNDRY => 'warning',
                                        Item::UNIT_STATUS_MAINTENANCE => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('slug')
                                    ->label('Slug URL'),
                                TextEntry::make('ulid')
                                    ->label('ULID Identitas')
                                    ->fontFamily('mono'),
                            ]),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Tidak ada deskripsi.')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Tampil di Katalog Publik')
                            ->boolean(),
                    ]),

                Section::make('Atribut Rental & Kondisi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('size')
                                    ->label('Ukuran / Size')
                                    ->placeholder('-')
                                    ->badge(),
                                TextEntry::make('condition')
                                    ->label('Kondisi Fisik')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('-'),
                                TextEntry::make('included_items')
                                    ->label('Kelengkapan Komponen')
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
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('price_per_day')
                                    ->label('Harga Sewa / Hari')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                                TextEntry::make('package_price_3d')
                                    ->label('Tarif Paket 3 Hari')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp '.number_format((int) $state, 0, ',', '.') : '-')
                                    ->placeholder('-'),
                                TextEntry::make('deposit_amount')
                                    ->label('Deposit Jaminan')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                                TextEntry::make('fine_per_day')
                                    ->label('Denda Telat / Hari')
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

                Section::make('Riwayat Sewa Barang')
                    ->description('Daftar seluruh pesanan sewa yang pernah menggunakan barang ini.')
                    ->schema([
                        ViewEntry::make('rental_history')
                            ->hiddenLabel()
                            ->view('filament.vendor.items.rental-history')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
