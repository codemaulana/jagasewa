<?php

namespace App\Filament\Vendor\Resources\Bookings\Schemas;

use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Services\IdentityDocumentService;
use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Permohonan Sewa')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('reference_code')
                                    ->label('Kode Booking')
                                    ->weight('bold')
                                    ->copyable(),

                                TextEntry::make('status')
                                    ->label('Status Booking')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        Booking::STATUS_PENDING => 'Menunggu Persetujuan',
                                        Booking::STATUS_APPROVED => 'Disetujui',
                                        Booking::STATUS_ACTIVE => 'Sedang Disewa',
                                        Booking::STATUS_RETURNED => 'Dikembalikan',
                                        Booking::STATUS_CLOSED => 'Selesai',
                                        Booking::STATUS_REJECTED => 'Ditolak',
                                        Booking::STATUS_CANCELLED_TIMEOUT => 'Batal (Batas Waktu)',
                                        Booking::STATUS_CANCELLED_RENTER => 'Batal (Penyewa)',
                                        Booking::STATUS_CANCELLED_VENDOR => 'Batal (Vendor)',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        Booking::STATUS_PENDING => 'warning',
                                        Booking::STATUS_APPROVED => 'info',
                                        Booking::STATUS_ACTIVE => 'success',
                                        Booking::STATUS_RETURNED => 'purple',
                                        Booking::STATUS_CLOSED => 'gray',
                                        Booking::STATUS_REJECTED,
                                        Booking::STATUS_CANCELLED_TIMEOUT,
                                        Booking::STATUS_CANCELLED_RENTER,
                                        Booking::STATUS_CANCELLED_VENDOR => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Diajukan Pada')
                                    ->dateTime('d M Y H:i'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('pending_expires_at')
                                    ->label('Batas Konfirmasi (24 Jam)')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-')
                                    ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_PENDING),

                                TextEntry::make('approved_at')
                                    ->label('Disetujui Pada')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-')
                                    ->visible(fn (Booking $record): bool => ! empty($record->approved_at)),

                                TextEntry::make('activated_at')
                                    ->label('Diserahkan / Aktif Pada')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-')
                                    ->visible(fn (Booking $record): bool => ! empty($record->activated_at)),

                                TextEntry::make('returned_at')
                                    ->label('Dikembalikan Pada')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-')
                                    ->visible(fn (Booking $record): bool => ! empty($record->returned_at)),
                            ]),
                    ]),

                Section::make('Rincian Barang & Finansial')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('item.name')
                                    ->label('Nama Barang')
                                    ->weight('bold'),

                                TextEntry::make('item.size')
                                    ->label('Ukuran / Size')
                                    ->badge()
                                    ->placeholder('-'),

                                TextEntry::make('rental_schedule')
                                    ->label('Jadwal Sewa')
                                    ->state(function (Booking $record): string {
                                        $start = $record->start_date ? Carbon::parse($record->start_date)->format('d M Y') : '-';
                                        $end = $record->end_date ? Carbon::parse($record->end_date)->format('d M Y') : '-';

                                        return "{$start} s/d {$end} ({$record->total_days} hari)";
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_price')
                                    ->label('Total Harga Sewa')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                                    ->weight('bold'),

                                TextEntry::make('deposit_paid')
                                    ->label('Deposit Jaminan')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),

                                TextEntry::make('fine_amount')
                                    ->label('Denda Keterlambatan')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                                    ->visible(fn (Booking $record): bool => $record->fine_amount > 0),
                            ]),
                    ]),

                Section::make('Data Identitas & Kontak Penyewa')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('renter.name')
                                    ->label('Nama Lengkap')
                                    ->weight('bold'),

                                TextEntry::make('renter.phone')
                                    ->label('Nomor WhatsApp')
                                    ->copyable(),
                            ]),

                        TextEntry::make('renter.address')
                            ->label('Alamat Domisili')
                            ->placeholder('Tidak ada alamat tersimpan.')
                            ->columnSpanFull(),

                        TextEntry::make('renter_notes')
                            ->label('Catatan dari Penyewa')
                            ->placeholder('Tidak ada catatan dari penyewa.')
                            ->columnSpanFull(),

                        TextEntry::make('vendor_notes')
                            ->label('Catatan Vendor / Alasan Penolakan')
                            ->placeholder('Tidak ada catatan.')
                            ->columnSpanFull()
                            ->visible(fn (Booking $record): bool => ! empty($record->vendor_notes)),
                    ]),

                Section::make('Verifikasi Dokumen Identitas (KYC & Signed URL)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('ktp_document')
                                    ->label('Dokumen Foto KTP')
                                    ->state(function (Booking $record): string {
                                        $ktp = $record->identityDocuments()->where('doc_type', IdentityDocument::DOC_TYPE_KTP)->first();
                                        if (! $ktp) {
                                            return 'Tidak diunggah';
                                        }
                                        if ($ktp->trashed()) {
                                            return 'Dokumen telah dihapus otomatis (masa retensi habis)';
                                        }

                                        return 'Buka Foto KTP (Tautan Aman 60 Menit)';
                                    })
                                    ->url(function (Booking $record): ?string {
                                        $ktp = $record->identityDocuments()->where('doc_type', IdentityDocument::DOC_TYPE_KTP)->first();
                                        if ($ktp && ! $ktp->trashed()) {
                                            return app(IdentityDocumentService::class)->generateTemporaryUrl($ktp, 60);
                                        }

                                        return null;
                                    })
                                    ->openUrlInNewTab()
                                    ->badge()
                                    ->color(function (Booking $record): string {
                                        $ktp = $record->identityDocuments()->where('doc_type', IdentityDocument::DOC_TYPE_KTP)->first();
                                        if (! $ktp || $ktp->trashed()) {
                                            return 'gray';
                                        }

                                        return 'info';
                                    }),

                                TextEntry::make('selfie_document')
                                    ->label('Foto Selfie dengan Identitas')
                                    ->state(function (Booking $record): string {
                                        $selfie = $record->identityDocuments()->where('doc_type', IdentityDocument::DOC_TYPE_SELFIE)->first();
                                        if (! $selfie) {
                                            return 'Tidak diunggah';
                                        }
                                        if ($selfie->trashed()) {
                                            return 'Dokumen telah dihapus otomatis (masa retensi habis)';
                                        }

                                        return 'Buka Foto Selfie (Tautan Aman 60 Menit)';
                                    })
                                    ->url(function (Booking $record): ?string {
                                        $selfie = $record->identityDocuments()->where('doc_type', IdentityDocument::DOC_TYPE_SELFIE)->first();
                                        if ($selfie && ! $selfie->trashed()) {
                                            return app(IdentityDocumentService::class)->generateTemporaryUrl($selfie, 60);
                                        }

                                        return null;
                                    })
                                    ->openUrlInNewTab()
                                    ->badge()
                                    ->color(function (Booking $record): string {
                                        $selfie = $record->identityDocuments()->where('doc_type', IdentityDocument::DOC_TYPE_SELFIE)->first();
                                        if (! $selfie || $selfie->trashed()) {
                                            return 'gray';
                                        }

                                        return 'info';
                                    }),
                            ]),

                        TextEntry::make('consent_status')
                            ->label('Persetujuan UU PDP')
                            ->state(function (Booking $record): string {
                                $doc = $record->identityDocuments()->first();
                                if (! $doc || ! $doc->consent_given) {
                                    return 'Tidak ada persetujuan tercatat';
                                }
                                $time = $doc->consent_at ? $doc->consent_at->format('d M Y H:i') : '-';

                                return "Disetujui (Versi {$doc->consent_version}) pada {$time} WIB";
                            }),
                    ]),

                Section::make('Komunikasi WhatsApp')
                    ->schema([
                        TextEntry::make('wa_link')
                            ->label('Tautan Chat Langsung (wa.me)')
                            ->state(fn () => 'Buka Obrolan WhatsApp dengan Penyewa')
                            ->url(fn (Booking $record): ?string => WaNotificationHelper::generateRenterChatUrl($record, 'approved'))
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('success'),
                    ])
                    ->visible(fn (Booking $record): bool => in_array($record->status, [Booking::STATUS_APPROVED, Booking::STATUS_ACTIVE])),
            ]);
    }
}
