<?php

namespace App\Filament\Vendor\Resources\Items\Schemas;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Helpers\FormatHelper;
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
                                    ->formatStateUsing(fn ($state) => ItemStatus::tryFrom($state)?->getLabel() ?? $state)
                                    ->color(fn ($state) => ItemStatus::tryFrom($state)?->getColor() ?? 'gray')
                                    ->icon(fn ($state) => ItemStatus::tryFrom($state)?->getIcon()),
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
                                    ->formatStateUsing(fn ($state) => ItemCondition::tryFrom($state)?->getLabel() ?? $state)
                                    ->color(fn ($state) => ItemCondition::tryFrom($state)?->getColor() ?? 'info')
                                    ->icon(fn ($state) => ItemCondition::tryFrom($state)?->getIcon())
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
                                    ->extraAttributes(['class' => 'tabular-nums font-medium'])
                                    ->formatStateUsing(fn ($state) => FormatHelper::rupiah($state)),
                                TextEntry::make('package_price_3d')
                                    ->label('Tarif Paket 3 Hari')
                                    ->extraAttributes(['class' => 'tabular-nums font-medium'])
                                    ->formatStateUsing(fn ($state) => $state ? FormatHelper::rupiah($state) : '-')
                                    ->placeholder('-'),
                                TextEntry::make('deposit_amount')
                                    ->label('Deposit Jaminan')
                                    ->extraAttributes(['class' => 'tabular-nums font-medium'])
                                    ->formatStateUsing(fn ($state) => FormatHelper::rupiah($state)),
                                TextEntry::make('fine_per_day')
                                    ->label('Denda Telat / Hari')
                                    ->extraAttributes(['class' => 'tabular-nums font-medium'])
                                    ->formatStateUsing(fn ($state) => FormatHelper::rupiah($state)),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('buffer_before')
                                    ->label('Buffer Sebelum Sewa')
                                    ->extraAttributes(['class' => 'tabular-nums'])
                                    ->formatStateUsing(fn ($state) => FormatHelper::days($state)),
                                TextEntry::make('buffer_after')
                                    ->label('Buffer Sesudah Sewa')
                                    ->extraAttributes(['class' => 'tabular-nums'])
                                    ->formatStateUsing(fn ($state) => FormatHelper::days($state)),
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
