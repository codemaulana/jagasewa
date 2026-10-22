<?php

namespace App\Filament\Vendor\Resources\Items\Schemas;

use App\Models\Item;
use App\Services\Billing\EntitlementService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('over_limit_notice')
                    ->hiddenLabel()
                    ->content(new HtmlString('
                        <div class="p-4 bg-warning-bg border border-warning-border rounded-lg text-text-main text-sm">
                            <span class="font-bold">Barang Berstatus Read-Only:</span>
                            Barang ini melebihi batas kuota 15 barang aktif pada paket Free Anda. Data Anda tersimpan aman dan tidak dihapus, namun tidak dapat diubah hingga Anda memperpanjang atau upgrade ke paket Pro.
                        </div>
                    '))
                    ->visible(fn (?Item $record) => $record && app(EntitlementService::class)->isItemReadOnly(auth('vendor')->user()?->vendor, $record))
                    ->columnSpanFull(),

                Section::make('Informasi Utama & Inventaris')
                    ->description('Detail nama, kategori, kode inventaris, dan status operasional barang.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Barang / Unit')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                                Select::make('category')
                                    ->label('Kategori Barang')
                                    ->options(Item::CATEGORIES)
                                    ->required()
                                    ->default('Lainnya')
                                    ->searchable(),
                                TextInput::make('item_code')
                                    ->label('Kode Barang / Barcode')
                                    ->placeholder('Contoh: CAM-001 / KOS-02')
                                    ->maxLength(50)
                                    ->helperText('Kode unik internal inventaris toko'),
                            ]),
                        Grid::make(2)
                            ->schema([
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
                                    ->helperText('Pengenal unik URL barang di etalase toko'),
                                Select::make('unit_status')
                                    ->label('Status Operasional Unit')
                                    ->options([
                                        Item::UNIT_STATUS_AVAILABLE => 'Tersedia (Siap Sewa)',
                                        Item::UNIT_STATUS_RENTED => 'Sedang Disewa',
                                        Item::UNIT_STATUS_LAUNDRY => 'Laundry / Pembersihan',
                                        Item::UNIT_STATUS_MAINTENANCE => 'Perbaikan / Maintenance',
                                    ])
                                    ->default(Item::UNIT_STATUS_AVAILABLE)
                                    ->required(),
                            ]),
                        Textarea::make('description')
                            ->label('Deskripsi Barang')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Tampilkan di Katalog Publik Toko')
                            ->default(true)
                            ->required(),
                    ]),

                Section::make('Atribut Rental (Varian, Kondisi & Kelengkapan)')
                    ->description('Spesifikasi ukuran/varian, kondisi fisik terkini, dan kelengkapan aksesoris.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('size')
                                    ->label('Ukuran / Varian')
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
                                Select::make('condition')
                                    ->label('Kondisi Fisik Barang')
                                    ->options(Item::CONDITIONS)
                                    ->default('Sangat Baik')
                                    ->required(),
                                TagsInput::make('included_items')
                                    ->label('Kelengkapan Komponen (Included)')
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
                                        'Charger',
                                        'Hardcase',
                                    ]),
                            ]),
                        Textarea::make('care_instructions')
                            ->label('Petunjuk Penggunaan & Perawatan')
                            ->placeholder('Contoh: Dry clean only, lensa jangan disentuh kaca langsung, simpan di dry box.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Ketentuan Finansial (Integer Rupiah)')
                    ->description('Tarif sewa harian, paket durasi, nilai uang jaminan deposit, dan denda.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('price_per_day')
                                    ->label('Tarif / Hari')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->prefix('Rp')
                                    ->helperText('Wajib > 0'),
                                TextInput::make('package_price_3d')
                                    ->label('Tarif Paket 3 Hari (Opsional)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->placeholder('Contoh: 180000')
                                    ->helperText('Diskon sewa weekend / 3 hari'),
                                TextInput::make('deposit_amount')
                                    ->label('Deposit Jaminan')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->helperText('Jaminan pengaman kerusakan'),
                                TextInput::make('fine_per_day')
                                    ->label('Denda Telat / Hari')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->helperText('Denda per hari keterlambatan'),
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
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
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
