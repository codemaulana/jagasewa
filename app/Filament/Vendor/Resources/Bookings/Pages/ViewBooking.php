<?php

namespace App\Filament\Vendor\Resources\Bookings\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Services\BlacklistService;
use App\Services\BookingStatusService;
use App\Services\FineCalculatorService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (app(BlacklistService::class)->isBookingBlacklisted($this->record)) {
            Notification::make()
                ->title('Peringatan: Penyewa Terdaftar di Blacklist')
                ->body('Identitas penyewa ini cocok dengan catatan blacklist Anda. Harap periksa riwayat dan rincian sebelum menyetujui permohonan booking.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui Booking')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Permohonan Booking')
                ->modalDescription(function (Booking $record): string {
                    $matches = app(BlacklistService::class)->getMatchesForBooking($record);
                    if ($matches->isNotEmpty()) {
                        $reasons = $matches->pluck('reason')->filter()->implode(', ');
                        $reasonSuffix = $reasons ? " (Alasan blacklist: {$reasons})" : '';

                        return "PERINGATAN: Penyewa ini terdaftar dalam daftar hitam (blacklist) Anda{$reasonSuffix}. Apakah Anda tetap yakin ingin menyetujui booking ini?";
                    }

                    return 'Apakah Anda yakin ingin menyetujui booking ini? Status akan berubah menjadi Disetujui.';
                })
                ->modalSubmitActionLabel('Ya, Setujui')
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_PENDING)
                ->action(function (Booking $record, BookingStatusService $statusService) {
                    $statusService->approve($record);
                    $record->refresh();

                    $waUrl = WaNotificationHelper::generateRenterChatUrl($record, 'approved');

                    $notification = Notification::make()
                        ->title('Booking Berhasil Disetujui')
                        ->body('Status booking telah diperbarui menjadi Disetujui.')
                        ->success();

                    if ($waUrl) {
                        $notification->actions([
                            Action::make('chat_wa')
                                ->label('Kirim WhatsApp ke Penyewa')
                                ->url($waUrl, shouldOpenInNewTab: true)
                                ->button(),
                        ]);
                    }

                    $notification->send();
                }),

            Action::make('record_payment')
                ->label('Catat Pembayaran')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn (Booking $record): bool => in_array($record->status, [Booking::STATUS_PENDING, Booking::STATUS_APPROVED, Booking::STATUS_ACTIVE]) && ! $record->is_paid_in_full)
                ->modalHeading('Catat Pembayaran DP / Pelunasan')
                ->modalDescription('Catat bukti pembayaran uang muka (DP) atau pelunasan penuh yang diterima dari penyewa.')
                ->modalSubmitActionLabel('Simpan Pembayaran')
                ->form([
                    Select::make('payment_type')
                        ->label('Jenis Pembayaran')
                        ->options([
                            'dp' => 'Pembayaran Uang Muka (DP)',
                            'full' => 'Pelunasan Penuh (Sewa + Deposit)',
                        ])
                        ->default(fn (Booking $record) => $record->is_dp_paid ? 'full' : 'dp')
                        ->required()
                        ->live(),

                    TextInput::make('amount')
                        ->label('Nominal Pembayaran (Rp)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->prefix('Rp')
                        ->required()
                        ->default(fn (Booking $record, $get) => $get('payment_type') === 'full'
                            ? max(1, (int) ($record->total_price + $record->deposit_paid - $record->down_payment_amount))
                            : (int) ceil(($record->total_price + $record->deposit_paid) * 0.5)),

                    FileUpload::make('payment_proof')
                        ->label('Unggah Bukti Transfer / Kwitansi')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                        ->directory('payment_proofs')
                        ->visibility('private')
                        ->maxSize(5120)
                        ->helperText('Format JPG, PNG, atau WebP maks 5MB.'),

                    Textarea::make('payment_notes')
                        ->label('Catatan Rekening / Pembayaran')
                        ->placeholder('Contoh: Ditransfer ke rekening BCA toko an. Pemilik')
                        ->maxLength(500),
                ])
                ->action(function (Booking $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        $locked = Booking::where('id', $record->id)->lockForUpdate()->firstOrFail();
                        $now = now('Asia/Jakarta');
                        $isFull = ($data['payment_type'] ?? 'dp') === 'full';

                        if ($isFull) {
                            $locked->update([
                                'is_paid_in_full' => true,
                                'paid_in_full_at' => $now,
                                'payment_proof_path' => $data['payment_proof'] ?? $locked->payment_proof_path,
                                'payment_notes' => $data['payment_notes'] ?? $locked->payment_notes,
                            ]);
                        } else {
                            $locked->update([
                                'is_dp_paid' => true,
                                'dp_paid_at' => $now,
                                'down_payment_amount' => (int) $data['amount'],
                                'payment_proof_path' => $data['payment_proof'] ?? $locked->payment_proof_path,
                                'payment_notes' => $data['payment_notes'] ?? $locked->payment_notes,
                            ]);
                        }
                    });

                    $record->refresh();
                    Notification::make()
                        ->title('Pembayaran Berhasil Dicatat')
                        ->body('Data pembayaran pesanan sewa telah diperbarui.')
                        ->success()
                        ->send();
                }),

            Action::make('reject')
                ->label('Tolak Booking')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Permohonan Booking')
                ->modalDescription('Apakah Anda yakin ingin menolak booking ini? Slot kalender akan dibebaskan kembali.')
                ->modalSubmitActionLabel('Tolak Booking')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan (Opsional)')
                        ->placeholder('Contoh: Barang sedang pemeliharaan / identitas tidak jelas')
                        ->maxLength(500),
                ])
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_PENDING)
                ->action(function (Booking $record, array $data, BookingStatusService $statusService) {
                    $reason = $data['rejection_reason'] ?? null;
                    $statusService->reject($record, $reason);
                    $record->refresh();

                    $waUrl = WaNotificationHelper::generateRenterChatUrl($record, 'rejected');

                    $notification = Notification::make()
                        ->title('Booking Telah Ditolak')
                        ->body('Permohonan booking telah ditolak dan slot kalender telah dibuka kembali.')
                        ->danger();

                    if ($waUrl) {
                        $notification->actions([
                            Action::make('chat_wa')
                                ->label('Kirim Info ke Penyewa via WA')
                                ->url($waUrl, shouldOpenInNewTab: true)
                                ->button(),
                        ]);
                    }

                    $notification->send();
                }),

            Action::make('activate')
                ->label('Serah Terima Barang (Aktifkan)')
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->modalHeading('Serah Terima Barang & Pemeriksaan Fisik Awal')
                ->modalDescription('Catat metode penyerahan dan checklist kondisi fisik sebelum unit dibawa oleh penyewa.')
                ->modalSubmitActionLabel('Konfirmasi Serah Terima')
                ->form([
                    Select::make('handover_method')
                        ->label('Metode Pengambilan / Penyerahan')
                        ->options([
                            'pickup' => 'Ambil Sendiri di Toko',
                            'instant_courier' => 'Kurir Instan (GoSend / GrabExpress)',
                            'delivery_courier' => 'Ekspedisi (Paxel / JNE / J&T)',
                        ])
                        ->default('pickup')
                        ->required(),

                    TextInput::make('tracking_number')
                        ->label('Nomor Resi / Tautan Lacak (Opsional)')
                        ->placeholder('Contoh: PXL-2610-1234 atau tautan live tracking GoSend')
                        ->maxLength(100),

                    CheckboxList::make('handover_checklist')
                        ->label('Checklist Kondisi & Kelengkapan Awal')
                        ->options([
                            'fisik_mulus' => 'Kondisi fisik barang bersih, mulus, dan bebas cacat baru',
                            'fungsi_normal' => 'Fungsi tombol, mekanisme, dan komponen beroperasi normal',
                            'aksesoris_lengkap' => 'Seluruh kelengkapan & aksesoris paket telah disertakan',
                            'identitas_sesuai' => 'Identitas penerima telah diverifikasi sesuai permohonan',
                        ])
                        ->default(['fisik_mulus', 'fungsi_normal', 'aksesoris_lengkap', 'identitas_sesuai']),

                    Textarea::make('handover_notes')
                        ->label('Catatan Kondisi Serah-Terima (Opsional)')
                        ->placeholder('Keterangan kondisi fisik barang saat diserahkan...')
                        ->maxLength(500),

                    FileUpload::make('handover_photos')
                        ->label('Dokumentasi Foto Kondisi Sebelum Sewa [Pro]')
                        ->multiple()
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                        ->directory('handover_photos')
                        ->visibility('private')
                        ->maxFiles(5)
                        ->maxSize(5120)
                        ->helperText('Maksimal 5 foto dokumentasi kondisi awal barang.')
                        ->columnSpanFull(),
                ])
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_APPROVED)
                ->action(function (Booking $record, array $data, BookingStatusService $statusService) {
                    DB::transaction(function () use ($record, $data, $statusService) {
                        $locked = Booking::where('id', $record->id)->lockForUpdate()->firstOrFail();
                        $locked->update([
                            'handover_method' => $data['handover_method'] ?? 'pickup',
                            'tracking_number' => $data['tracking_number'] ?? null,
                            'handover_checklist' => $data['handover_checklist'] ?? [],
                            'handover_notes' => $data['handover_notes'] ?? null,
                            'handover_photos' => $data['handover_photos'] ?? [],
                        ]);
                        $statusService->activate($locked);
                    });

                    $record->refresh();

                    Notification::make()
                        ->title('Booking Telah Aktif')
                        ->body('Barang berhasil ditandai telah diserahkan dan masa sewa sedang berjalan.')
                        ->success()
                        ->send();
                }),

            Action::make('mark_returned')
                ->label('Tandai Barang Dikembalikan')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('purple')
                ->requiresConfirmation()
                ->modalHeading('Tandai Barang Telah Dikembalikan')
                ->modalDescription('Apakah barang rental telah diterima kembali dari penyewa? Status akan berubah menjadi Dikembalikan dan siap untuk proses settlement.')
                ->modalSubmitActionLabel('Ya, Barang Diterima')
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_ACTIVE)
                ->action(function (Booking $record, BookingStatusService $statusService) {
                    $statusService->markReturned($record);
                    $record->refresh();

                    Notification::make()
                        ->title('Barang Telah Dikembalikan')
                        ->body('Status booking berhasil diperbarui menjadi Dikembalikan. Silakan periksa kondisi barang untuk proses settlement.')
                        ->success()
                        ->send();
                }),

            Action::make('settle')
                ->label('Penyelesaian Sewa')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('success')
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_RETURNED)
                ->modalHeading('Penyelesaian Sewa & Perhitungan Denda')
                ->modalDescription('Periksa tanggal aktual pengembalian dan rincian kerusakan barang. Sistem menghitung denda dan deposit secara otomatis (BR-03).')
                ->modalSubmitActionLabel('Konfirmasi Penyelesaian')
                ->form([
                    DatePicker::make('actual_return_date')
                        ->label('Tanggal Kembali Aktual')
                        ->default(now(FineCalculatorService::TIMEZONE)->toDateString())
                        ->required()
                        ->live(),

                    CheckboxList::make('return_checklist')
                        ->label('Checklist Kondisi Pengembalian')
                        ->options([
                            'semua_kembali' => 'Seluruh kelengkapan unit telah dikembalikan lengkap',
                            'kebersihan_standar' => 'Kondisi kebersihan dalam batas toleransi wajar',
                            'bebas_kerusakan_fatal' => 'Unit tidak mengalami kerusakan fatal atau hilang',
                        ])
                        ->default(['semua_kembali', 'kebersihan_standar', 'bebas_kerusakan_fatal']),

                    Repeater::make('damage_items')
                        ->label('Daftar Kerusakan Barang (Opsional)')
                        ->schema([
                            TextInput::make('description')
                                ->label('Deskripsi Kerusakan')
                                ->placeholder('Contoh: Kancing copot / noda membandel / lecet bodi')
                                ->required(),
                            TextInput::make('amount')
                                ->label('Biaya Perbaikan (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required()
                                ->live(debounce: 500),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->live()
                        ->addActionLabel('+ Tambah Item Kerusakan'),

                    FileUpload::make('return_photos')
                        ->label('Foto Bukti Kerusakan / Kondisi Pengembalian [Pro]')
                        ->multiple()
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                        ->directory('return_photos')
                        ->visibility('private')
                        ->maxFiles(5)
                        ->maxSize(5120)
                        ->helperText('Lampirkan foto bukti noda / cacat fisik / kerusakan yang ditemukan saat pengembalian.')
                        ->columnSpanFull(),

                    Placeholder::make('calculation_summary')
                        ->label('Ringkasan Perhitungan Real-Time')
                        ->content(function ($get, Booking $record, FineCalculatorService $calculator) {
                            $actualReturnDate = $get('actual_return_date') ?: now(FineCalculatorService::TIMEZONE)->toDateString();
                            $damageItems = $get('damage_items') ?: [];
                            $damageTotal = $calculator->calculateTotalDamage($damageItems);

                            $result = $calculator->calculateForBooking($record, $actualReturnDate, $damageTotal);

                            return new HtmlString(view('filament.vendor.bookings.settlement-summary', [
                                'result' => $result,
                                'booking' => $record,
                            ])->render());
                        }),

                    Textarea::make('damage_notes')
                        ->label('Catatan Kondisi Barang / Vendor (Opsional)')
                        ->placeholder('Keterangan tambahan kondisi saat barang dikembalikan...')
                        ->maxLength(500),
                ])
                ->action(function (Booking $record, array $data, BookingStatusService $statusService, FineCalculatorService $calculator) {
                    $actualReturnDate = $data['actual_return_date'] ?? now(FineCalculatorService::TIMEZONE)->toDateString();
                    $damageItems = $data['damage_items'] ?? [];
                    $damageTotal = $calculator->calculateTotalDamage($damageItems);

                    $calcResult = $calculator->calculateForBooking($record, $actualReturnDate, $damageTotal);

                    DB::transaction(function () use ($record, $data, $statusService, $calcResult, $damageItems) {
                        $locked = Booking::where('id', $record->id)->lockForUpdate()->firstOrFail();
                        $locked->update([
                            'return_checklist' => $data['return_checklist'] ?? [],
                            'return_photos' => $data['return_photos'] ?? [],
                        ]);

                        $statusService->closeWithSettlement(
                            $locked,
                            $calcResult->toSettlementAttributes([
                                'damage_notes' => $data['damage_notes'] ?? null,
                            ]),
                            $damageItems
                        );
                    });

                    $record->refresh();

                    $outcomeMsg = $calcResult->hasRefund
                        ? 'Sisa uang jaminan yang harus dikembalikan ke penyewa: Rp '.number_format($calcResult->refund, 0, ',', '.')
                        : ($calcResult->hasShortfall
                            ? 'Kekurangan bayar yang ditagihkan ke penyewa: Rp '.number_format($calcResult->shortfall, 0, ',', '.')
                            : 'Uang jaminan habis pas (Rp 0).');

                    $notification = Notification::make()
                        ->title('Penyelesaian Sewa Berhasil')
                        ->body("Pesanan telah ditutup (closed). {$outcomeMsg}")
                        ->success();

                    $waUrl = WaNotificationHelper::generateRenterChatUrl($record, 'settled');
                    if ($waUrl) {
                        $notification->actions([
                            Action::make('chat_wa_settled')
                                ->label('Kirim Info ke Penyewa via WA')
                                ->url($waUrl, shouldOpenInNewTab: true)
                                ->button(),
                        ]);
                    }

                    $notification->send();
                }),

            Action::make('remind_wa')
                ->label('Pengingat Pengembalian via WA')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning')
                ->url(fn (Booking $record): ?string => WaNotificationHelper::generateRenterChatUrl($record, 'return_reminder'))
                ->openUrlInNewTab()
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_ACTIVE),

            Action::make('contact_wa')
                ->label('Hubungi Penyewa via WA')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(fn (Booking $record): ?string => WaNotificationHelper::generateRenterChatUrl(
                    $record,
                    match ($record->status) {
                        Booking::STATUS_CLOSED => 'settled',
                        Booking::STATUS_REJECTED => 'rejected',
                        default => 'approved',
                    }
                ))
                ->openUrlInNewTab()
                ->visible(fn (Booking $record): bool => in_array($record->status, [
                    Booking::STATUS_APPROVED,
                    Booking::STATUS_ACTIVE,
                    Booking::STATUS_CLOSED,
                    Booking::STATUS_REJECTED,
                ])),
        ];
    }
}
