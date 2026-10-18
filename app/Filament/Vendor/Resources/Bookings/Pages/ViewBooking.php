<?php

namespace App\Filament\Vendor\Resources\Bookings\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Services\BookingStatusService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui Booking')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Permohonan Booking')
                ->modalDescription('Apakah Anda yakin ingin menyetujui booking ini? Status akan berubah menjadi Disetujui.')
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

            Action::make('contact_wa')
                ->label('Hubungi Penyewa via WA')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(fn (Booking $record): ?string => WaNotificationHelper::generateRenterChatUrl($record, 'approved'))
                ->openUrlInNewTab()
                ->visible(fn (Booking $record): bool => in_array($record->status, [Booking::STATUS_APPROVED, Booking::STATUS_ACTIVE])),
        ];
    }
}
