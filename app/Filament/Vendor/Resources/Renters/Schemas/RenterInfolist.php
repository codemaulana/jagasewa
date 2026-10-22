<?php

namespace App\Filament\Vendor\Resources\Renters\Schemas;

use App\Models\Renter;
use App\Models\VendorRenter;
use App\Services\BlacklistService;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RenterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil Pelanggan')
                    ->description('Informasi kontak dasar pelanggan yang tersimpan pada pesanan sewa.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Lengkap')
                                    ->weight('bold'),

                                TextEntry::make('phone')
                                    ->label('Nomor WhatsApp')
                                    ->copyable()
                                    ->placeholder('-'),

                                TextEntry::make('blacklist_status')
                                    ->label('Pemeriksaan Blacklist')
                                    ->badge()
                                    ->state(function (Renter $record): string {
                                        $vendorId = (int) (auth('vendor')->user()?->vendor_id ?? session('vendor_id'));

                                        return app(BlacklistService::class)->isBlacklisted($record, $vendorId)
                                            ? 'Terdaftar di Blacklist'
                                            : 'Bebas Catatan Hitam';
                                    })
                                    ->color(function (Renter $record): string {
                                        $vendorId = (int) (auth('vendor')->user()?->vendor_id ?? session('vendor_id'));

                                        return app(BlacklistService::class)->isBlacklisted($record, $vendorId)
                                            ? 'danger'
                                            : 'success';
                                    }),
                            ]),

                        TextEntry::make('address')
                            ->label('Alamat Domisili')
                            ->placeholder('Tidak ada data alamat.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan Internal & Tingkat Reliabilitas')
                    ->description('Status keandalan penyewa dan memo internal toko (hanya dapat dilihat oleh vendor Anda).')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reliability_badge')
                                    ->label('Penanda Reliabilitas')
                                    ->badge()
                                    ->state(function (Renter $record): string {
                                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                                        $profile = VendorRenter::where('vendor_id', $vendorId)
                                            ->where('renter_id', $record->id)
                                            ->first();

                                        return $profile ? $profile->reliability_label : 'Standar';
                                    })
                                    ->color(function (Renter $record): string {
                                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                                        $profile = VendorRenter::where('vendor_id', $vendorId)
                                            ->where('renter_id', $record->id)
                                            ->first();

                                        $tag = $profile?->reliability_tag ?? VendorRenter::RELIABILITY_NORMAL;

                                        return match ($tag) {
                                            VendorRenter::RELIABILITY_TRUSTED => 'success',
                                            VendorRenter::RELIABILITY_CAUTION => 'warning',
                                            VendorRenter::RELIABILITY_PROBLEMATIC => 'danger',
                                            default => 'info',
                                        };
                                    }),

                                TextEntry::make('internal_notes_display')
                                    ->label('Memo Khusus Toko')
                                    ->state(function (Renter $record): string {
                                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                                        $profile = VendorRenter::where('vendor_id', $vendorId)
                                            ->where('renter_id', $record->id)
                                            ->first();

                                        return $profile?->internal_notes ?: 'Belum ada catatan internal khusus untuk pelanggan ini. Klik tombol "Catatan & Status Pelanggan" di atas untuk menambahkan memo.';
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Riwayat Pesanan Sewa di Toko Anda')
                    ->description('Daftar seluruh transaksi yang pernah dilakukan oleh penyewa ini pada toko Anda.')
                    ->schema([
                        ViewEntry::make('customer_booking_history')
                            ->hiddenLabel()
                            ->view('filament.vendor.renters.booking-history')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
