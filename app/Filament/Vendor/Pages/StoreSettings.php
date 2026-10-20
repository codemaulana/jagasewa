<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Schema $form
 */
class StoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Toko';

    protected static ?string $title = 'Pengaturan Profil Toko';

    protected static ?int $navigationSort = 80;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $vendor = $this->getVendor();

        if ($vendor) {
            $this->data = [
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'wa_number' => $vendor->wa_number,
                'description' => $vendor->description,
                'address' => $vendor->address,
                'bank_account_info' => $vendor->bank_account_info,
                'logo_path' => $vendor->logo_path,
                'banner_path' => $vendor->banner_path,
            ];

            $this->form->fill($this->data);
        }
    }

    public function getVendor(): ?Vendor
    {
        return auth('vendor')->user()?->vendor;
    }

    public function form(Schema $schema): Schema
    {
        $diskName = config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public';

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitas Toko')
                    ->description('Informasi nama, alamat web, dan deskripsi toko rental Anda.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Rental / Toko')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Nama brand rental yang tampil kepada penyewa.'),
                                TextInput::make('slug')
                                    ->label('Slug URL Toko')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText(fn () => 'Alamat etalase: /b/'.($this->data['slug'] ?? '')),
                            ]),
                        Textarea::make('description')
                            ->label('Deskripsi Toko')
                            ->placeholder('Jelaskan jenis barang yang Anda sewakan, area jangkauan, atau keunggulan toko Anda...')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Textarea::make('address')
                            ->label('Alamat / Lokasi Toko')
                            ->placeholder('Alamat toko fisik atau titik temu pengambilan/pengembalian barang...')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Kontak & Rekening Pembayaran')
                    ->description('Nomor WhatsApp untuk dihubungi penyewa dan informasi rekening tujuan pembayaran.')
                    ->schema([
                        TextInput::make('wa_number')
                            ->label('Nomor WhatsApp Resmi Toko')
                            ->required()
                            ->maxLength(30)
                            ->placeholder('+6281234567890')
                            ->helperText('Format internasional (+628...). Digunakan untuk tombol chat WhatsApp dan konfirmasi booking.'),
                        Textarea::make('bank_account_info')
                            ->label('Informasi Rekening Bank / Pembayaran')
                            ->placeholder("Contoh:\nBCA 1234567890 a.n. Rental Jaya\nMandiri 0987654321 a.n. Rental Jaya")
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Ditampilkan kepada penyewa saat pesanan disetujui untuk transfer biaya sewa dan deposit.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media & Branding Toko')
                    ->description('Unggah logo toko dan gambar banner sampul etalase katalog publik.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Logo Toko')
                                    ->image()
                                    ->disk($diskName)
                                    ->directory('vendor_logos')
                                    ->visibility('private')
                                    ->maxSize(2048)
                                    ->openable()
                                    ->downloadable()
                                    ->helperText('Format PNG/JPG/WebP, rasio 1:1 disarankan, maksimal 2MB.'),
                                FileUpload::make('banner_path')
                                    ->label('Banner Sampul Toko')
                                    ->image()
                                    ->disk($diskName)
                                    ->directory('vendor_banners')
                                    ->visibility('private')
                                    ->maxSize(5120)
                                    ->openable()
                                    ->downloadable()
                                    ->helperText('Format PNG/JPG/WebP, rasio lebar 16:9 atau 3:1 disarankan, maksimal 5MB.'),
                            ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment('start')
                            ->key('form-actions'),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        $vendor = $this->getVendor();
        $storeUrl = $vendor ? url('/b/'.$vendor->slug) : '#';

        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon('heroicon-o-check')
                ->submit('form'),
            Action::make('view_store')
                ->label('Lihat Etalase Publik')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url($storeUrl, shouldOpenInNewTab: true),
        ];
    }

    public function save(): void
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            return;
        }

        $state = $this->form->getState();

        // Check if old files need removal when replaced or cleared
        $diskName = config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public';
        $disk = Storage::disk($diskName);

        if (! empty($vendor->logo_path) && ($vendor->logo_path !== ($state['logo_path'] ?? null))) {
            try {
                $disk->delete($vendor->logo_path);
            } catch (\Throwable) {
                // ignore deletion error
            }
        }

        if (! empty($vendor->banner_path) && ($vendor->banner_path !== ($state['banner_path'] ?? null))) {
            try {
                $disk->delete($vendor->banner_path);
            } catch (\Throwable) {
                // ignore deletion error
            }
        }

        $vendor->update([
            'name' => $state['name'],
            'wa_number' => $state['wa_number'],
            'description' => $state['description'] ?? null,
            'address' => $state['address'] ?? null,
            'bank_account_info' => $state['bank_account_info'] ?? null,
            'logo_path' => $state['logo_path'] ?? null,
            'banner_path' => $state['banner_path'] ?? null,
        ]);

        Notification::make()
            ->title('Pengaturan toko berhasil disimpan.')
            ->success()
            ->send();
    }
}
