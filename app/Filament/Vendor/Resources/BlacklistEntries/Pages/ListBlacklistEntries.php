<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries\Pages;

use App\Filament\Vendor\Resources\BlacklistEntries\BlacklistEntryResource;
use App\Models\BlacklistEntry;
use App\Services\BlacklistService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBlacklistEntries extends ListRecords
{
    protected static string $resource = BlacklistEntryResource::class;

    protected function getHeaderActions(): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        $remainingChecks = $vendor ? app(BlacklistService::class)->getRemainingMonthlyChecks($vendor) : 0;
        $quotaText = $remainingChecks === -1 ? 'Tanpa Batas (Paket Pro)' : "Sisa {$remainingChecks}/10 Cek Bulan Ini (Paket Free)";

        return [
            Action::make('check_blacklist')
                ->label('Cek Blacklist Bersama')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->modalHeading('Pencarian Database Blacklist Bersama & Internal')
                ->modalDescription("Cek apakah nomor WhatsApp, NIK KTP, atau akun Instagram pernah dilaporkan melakukan penipuan atau perusakan barang. Kuota Anda: {$quotaText}.")
                ->modalSubmitActionLabel('Cari di Database')
                ->form([
                    Select::make('identity_type')
                        ->label('Jenis Identitas')
                        ->options([
                            BlacklistEntry::TYPE_WA => 'Nomor WhatsApp / HP',
                            BlacklistEntry::TYPE_NIK => 'NIK (KTP)',
                            BlacklistEntry::TYPE_INSTAGRAM => 'Akun Instagram',
                        ])
                        ->default(BlacklistEntry::TYPE_WA)
                        ->required(),

                    TextInput::make('identity_value')
                        ->label('Nomor / Akun yang Dicari')
                        ->required()
                        ->placeholder('Contoh: 081234567890 atau 3201xxxxxxxxxxxx')
                        ->helperText('Identitas otomatis di-hash via HMAC-SHA256 untuk melindungi kerahasiaan.'),
                ])
                ->action(function (array $data, BlacklistService $service) {
                    $vendor = auth('vendor')->user()?->vendor;
                    if (! $vendor) {
                        return;
                    }

                    $result = $service->searchBlacklist($vendor, $data['identity_type'], $data['identity_value']);

                    if ($result['has_match']) {
                        $matches = $result['matches'];
                        $reasons = collect($matches)->pluck('reason')->implode(' | ');
                        Notification::make()
                            ->title('PERINGATAN: Ditemukan di Blacklist!')
                            ->body("Identitas {$result['masked_query']} cocok dengan ".count($matches)." catatan blacklist: {$reasons}")
                            ->danger()
                            ->persistent()
                            ->send();
                    } else {
                        $remaining = $result['remaining_checks'];
                        $remainingInfo = $remaining === -1 ? 'Pencarian tanpa batas (Pro)' : "Sisa kuota bulan ini: {$remaining} cek";
                        Notification::make()
                            ->title('Hasil Pencarian: Bersih')
                            ->body("Identitas {$result['masked_query']} tidak ditemukan dalam daftar hitam internal maupun komunitas. ({$remainingInfo})")
                            ->success()
                            ->send();
                    }
                }),

            CreateAction::make()
                ->label('Tambah Blacklist'),
        ];
    }
}
