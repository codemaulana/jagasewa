<?php

namespace App\Filament\Vendor\Resources\Bookings\Schemas;

use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Services\BlacklistService;
use App\Services\IdentityDocumentService;
use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Peringatan: Penyewa Terdaftar di Blacklist')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->schema([
                        TextEntry::make('blacklist_warning_detail')
                            ->label('Catatan Blacklist Vendor Anda')
                            ->state(function (Booking $record): string {
                                $matches = app(BlacklistService::class)->getMatchesForBooking($record);
                                if ($matches->isEmpty()) {
                                    return '-';
                                }

                                return $matches->map(function (BlacklistEntry $match) {
                                    $typeName = match ($match->identity_type) {
                                        BlacklistEntry::TYPE_WA => 'Nomor WhatsApp',
                                        BlacklistEntry::TYPE_NIK => 'NIK KTP',
                                        BlacklistEntry::TYPE_INSTAGRAM => 'Instagram',
                                        default => strtoupper($match->identity_type),
                                    };
                                    $date = $match->created_at ? $match->created_at->format('d M Y') : '-';

                                    return "[{$typeName}] {$match->reason} (Dicatat: {$date})";
                                })->implode("\n");
                            })
                            ->color('danger')
                            ->weight('bold')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Booking $record): bool => app(BlacklistService::class)->isBookingBlacklisted($record)),

                Section::make('Linimasa Aktivitas Pesanan')
                    ->description('Tahapan riwayat alur dari permohonan hingga selesai.')
                    ->schema([
                        ViewEntry::make('activity_timeline')
                            ->hiddenLabel()
                            ->view('filament.vendor.bookings.activity-timeline')
                            ->columnSpanFull(),
                    ]),

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
                                        Booking::STATUS_PENDING,
                                        Booking::STATUS_RETURNED => 'warning',
                                        Booking::STATUS_APPROVED,
                                        Booking::STATUS_ACTIVE => 'info',
                                        Booking::STATUS_CLOSED => 'success',
                                        Booking::STATUS_REJECTED => 'danger',
                                        Booking::STATUS_CANCELLED_TIMEOUT,
                                        Booking::STATUS_CANCELLED_RENTER,
                                        Booking::STATUS_CANCELLED_VENDOR => 'gray',
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

                                TextEntry::make('item.category')
                                    ->label('Kategori')
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

                Section::make('Pencatatan Pembayaran (DP & Pelunasan)')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('payment_status_summary')
                                    ->label('Status Pembayaran')
                                    ->badge()
                                    ->state(function (Booking $record): string {
                                        if ($record->is_paid_in_full) {
                                            return 'Lunas Penuh';
                                        }
                                        if ($record->is_dp_paid) {
                                            return 'DP Terbayar (Rp '.number_format($record->down_payment_amount, 0, ',', '.').')';
                                        }

                                        return 'Belum Ada Pembayaran Tercatat';
                                    })
                                    ->color(fn (Booking $record): string => $record->is_paid_in_full ? 'success' : ($record->is_dp_paid ? 'info' : 'warning')),

                                TextEntry::make('dp_paid_at')
                                    ->label('Waktu Bayar DP')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),

                                TextEntry::make('paid_in_full_at')
                                    ->label('Waktu Pelunasan')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('payment_proof_link')
                                    ->label('Bukti Pembayaran')
                                    ->state(fn (Booking $record): string => $record->payment_proof_path ? 'Buka Bukti Transfer' : 'Tidak dilampirkan')
                                    ->url(fn (Booking $record): ?string => $record->payment_proof_url)
                                    ->openUrlInNewTab()
                                    ->badge()
                                    ->color(fn (Booking $record): string => $record->payment_proof_path ? 'info' : 'gray'),
                                TextEntry::make('payment_notes')
                                    ->label('Catatan Pembayaran')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Informasi Serah-Terima (Handover)')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('handover_method')
                                    ->label('Metode Pengambilan')
                                    ->state(fn (Booking $record): string => match ($record->handover_method) {
                                        'pickup' => 'Ambil Sendiri di Toko',
                                        'instant_courier' => 'Kurir Instan (GoSend/Grab)',
                                        'delivery_courier' => 'Ekspedisi (Paxel/JNE/J&T)',
                                        default => $record->handover_method ?: '-',
                                    })
                                    ->badge(),
                                TextEntry::make('tracking_number')
                                    ->label('Nomor Resi / Tautan Lacak')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('handover_checklist')
                                    ->label('Checklist Kondisi Awal')
                                    ->badge()
                                    ->placeholder('Tidak ada catatan checklist'),
                            ]),
                        TextEntry::make('handover_notes')
                            ->label('Catatan Kondisi Saat Penyerahan')
                            ->placeholder('Tidak ada catatan khusus saat penyerahan.')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Booking $record): bool => ! empty($record->activated_at) || ! empty($record->handover_method)),

                Section::make('Foto Dokumentasi & Inspeksi Fisik (Sebelum & Sesudah Sewa)')
                    ->description('Dokumentasi foto kondisi unit saat penyerahan dan saat diterima kembali.')
                    ->schema([
                        ViewEntry::make('inspection_photos')
                            ->hiddenLabel()
                            ->view('filament.vendor.bookings.inspection-photos')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Booking $record): bool => ! empty($record->activated_at) || ! empty($record->handover_photos) || ! empty($record->return_photos)),

                Section::make('Data Identitas & Kontak Penyewa')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('renter.name')
                                    ->label('Nama Lengkap')
                                    ->weight('bold'),

                                TextEntry::make('renter.phone')
                                    ->label('Nomor WhatsApp')
                                    ->copyable(),

                                TextEntry::make('blacklist_status')
                                    ->label('Status Blacklist')
                                    ->badge()
                                    ->state(function (Booking $record): string {
                                        return app(BlacklistService::class)->isBookingBlacklisted($record)
                                            ? 'Terdaftar di Blacklist'
                                            : 'Bersih (Bebas Blacklist)';
                                    })
                                    ->color(function (Booking $record): string {
                                        return app(BlacklistService::class)->isBookingBlacklisted($record)
                                            ? 'danger'
                                            : 'success';
                                    }),
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

                Section::make('Hasil Penyelesaian Sewa & Denda (Settlement)')
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('settlement.actual_return_date')
                                    ->label('Tanggal Kembali Aktual')
                                    ->date('d M Y'),

                                TextEntry::make('settlement.late_days')
                                    ->label('Keterlambatan')
                                    ->formatStateUsing(fn ($state) => "{$state} hari"),

                                TextEntry::make('settlement.late_fine')
                                    ->label('Denda Keterlambatan')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('settlement.damage_deduction')
                                    ->label('Potongan Kerusakan')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),

                                TextEntry::make('settlement.total_deduction')
                                    ->label('Total Potongan')
                                    ->weight('bold')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),

                                TextEntry::make('settlement.deposit_status')
                                    ->label('Status Uang Jaminan (Deposit)')
                                    ->badge()
                                    ->state(function (Booking $record): string {
                                        $s = $record->settlement;
                                        if (! $s) {
                                            return '-';
                                        }

                                        return $s->deposit_remaining >= 0
                                            ? 'Pengembalian: Rp '.number_format($s->refund_amount, 0, ',', '.')
                                            : 'Kekurangan: -Rp '.number_format($s->shortfall_amount, 0, ',', '.');
                                    })
                                    ->color(function (Booking $record): string {
                                        $s = $record->settlement;

                                        return ($s && $s->deposit_remaining < 0) ? 'danger' : 'success';
                                    }),
                            ]),

                        TextEntry::make('settlement.damage_notes')
                            ->label('Catatan Kondisi Barang')
                            ->placeholder('Tidak ada catatan khusus.')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_CLOSED && $record->settlement()->exists()),
            ]);
    }
}
