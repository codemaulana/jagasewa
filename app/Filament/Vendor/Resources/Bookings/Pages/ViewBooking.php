<?php

namespace App\Filament\Vendor\Resources\Bookings\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Services\BlacklistService;
use App\Services\BookingStatusService;
use App\Services\FineCalculatorService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
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
                ->label('Tandai Barang Diserahkan (Aktifkan)')
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Tandai Barang Diserahkan / Aktif')
                ->modalDescription('Apakah barang rental telah diserahkan atau dikirimkan ke penyewa? Status booking akan berubah menjadi Sedang Disewa (Aktif).')
                ->modalSubmitActionLabel('Ya, Tandai Aktif')
                ->visible(fn (Booking $record): bool => $record->status === Booking::STATUS_APPROVED)
                ->action(function (Booking $record, BookingStatusService $statusService) {
                    $statusService->activate($record);
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

                    Repeater::make('damage_items')
                        ->label('Daftar Kerusakan Barang (Opsional)')
                        ->schema([
                            TextInput::make('description')
                                ->label('Deskripsi Kerusakan')
                                ->placeholder('Contoh: Kancing copot / noda membandel')
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

                    $statusService->closeWithSettlement(
                        $record,
                        $calcResult->toSettlementAttributes([
                            'damage_notes' => $data['damage_notes'] ?? null,
                        ]),
                        $damageItems
                    );

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

            Action::make('contact_wa')
                ->label('Hubungi Penyewa via WA')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(fn (Booking $record): ?string => WaNotificationHelper::generateRenterChatUrl(
                    $record,
                    $record->status === Booking::STATUS_CLOSED ? 'settled' : 'approved'
                ))
                ->openUrlInNewTab()
                ->visible(fn (Booking $record): bool => in_array($record->status, [Booking::STATUS_APPROVED, Booking::STATUS_ACTIVE, Booking::STATUS_CLOSED])),
        ];
    }
}
