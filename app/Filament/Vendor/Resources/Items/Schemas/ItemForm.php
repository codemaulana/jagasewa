<?php

namespace App\Filament\Vendor\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->description('Detail nama, pengenal URL, dan deskripsi barang rental.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Barang / Kostum')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->label('Slug URL')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(
                                        table: 'items',
                                        column: 'slug',
                                        ignorable: fn (?Item $record) => $record,
                                        modifyRuleUsing: fn ($rule) => $rule->where('vendor_id', auth('vendor')->user()?->vendor_id ?? session('vendor_id'))
                                    )
                                    ->helperText('Pengenal unik URL barang, contoh: genshin-raiden-shogun-m'),
                            ]),
                        Textarea::make('description')
                            ->label('Deskripsi Barang')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Status Aktif (Tampil di Katalog Publik)')
                            ->default(true)
                            ->required(),
                    ]),

                Section::make('Atribut Rental (Cosplay / Kamera / Pakaian)')
                    ->description('Spesifikasi ukuran, kelengkapan komponen sewa, dan petunjuk perawatan.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('size')
                                    ->label('Ukuran / Size')
                                    ->options([
                                        'S' => 'S',
                                        'M' => 'M',
                                        'L' => 'L',
                                        'XL' => 'XL',
                                        'XXL' => 'XXL',
                                        'All Size' => 'All Size',
                                        'Free Size' => 'Free Size',
                                        'Custom' => 'Custom / Lainnya',
                                    ])
                                    ->searchable()
                                    ->placeholder('Pilih ukuran jika ada'),
                                TagsInput::make('included_items')
                                    ->label('Kelengkapan Set (Included Items)')
                                    ->placeholder('Ketik komponen lalu Enter')
                                    ->suggestions([
                                        'Kostum',
                                        'Wig',
                                        'Aksesoris Kepala',
                                        'Prop / Senjata',
                                        'Sepatu / Boots',
                                        'Sarung Tangan',
                                        'Baterai Cadangan',
                                        'Lensa Kit',
                                        'Memory Card',
                                    ]),
                            ]),
                        Textarea::make('care_instructions')
                            ->label('Instruksi Perawatan & Cuci')
                            ->placeholder('Contoh: Dry clean only, wig jangan disisir kasar saat basah, simpan di tempat kering.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Ketentuan Finansial (Integer Rupiah)')
                    ->description('Tarif sewa harian, deposit jaminan, dan denda keterlambatan per hari.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('price_per_day')
                                    ->label('Harga Sewa / Hari')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->prefix('Rp')
                                    ->helperText('Tarif per hari (wajib > 0)'),
                                TextInput::make('deposit_amount')
                                    ->label('Uang Jaminan / Deposit')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->helperText('Deposit pengaman kerusakan/keterlambatan'),
                                TextInput::make('fine_per_day')
                                    ->label('Denda Keterlambatan / Hari')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->helperText('Denda per hari jika terlambat'),
                            ]),
                    ]),

                Section::make('Smart Buffer Calendar')
                    ->description('Jeda waktu otomatis sebelum dan sesudah sewa untuk laundry, pengiriman, dan inspeksi.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('buffer_before')
                                    ->label('Jeda Sebelum Sewa (Hari)')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Hari jeda persiapan/laundry sebelum sewa'),
                                TextInput::make('buffer_after')
                                    ->label('Jeda Sesudah Sewa (Hari)')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Hari jeda pengecekan/laundry setelah kembali'),
                            ]),
                    ]),

                Section::make('Galeri Foto Barang')
                    ->description('Unggah foto barang rental ke penyimpanan S3 privat. Foto pertama akan menjadi foto utama pada katalog toko.')
                    ->schema([
                        FileUpload::make('photo_paths')
                            ->label('Foto Barang')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->openable()
                            ->downloadable()
                            ->image()
                            ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                            ->directory('item_photos')
                            ->visibility('private')
                            ->maxFiles(fn () => auth('vendor')->user()?->vendor?->isPro() ? 8 : 5)
                            ->maxSize(5120)
                            ->helperText(fn () => auth('vendor')->user()?->vendor?->isPro()
                                ? 'Paket Pro: Maksimal 8 foto gambar (JPG, PNG, WebP), maksimal 5MB per file. Geser untuk mengatur urutan.'
                                : 'Paket Free: Maksimal 5 foto gambar (JPG, PNG, WebP), maksimal 5MB per file. Upgrade ke Pro untuk hingga 8 foto. Geser untuk mengatur urutan.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
