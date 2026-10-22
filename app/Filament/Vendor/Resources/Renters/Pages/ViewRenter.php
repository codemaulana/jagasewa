<?php

namespace App\Filament\Vendor\Resources\Renters\Pages;

use App\Filament\Vendor\Resources\Renters\RenterResource;
use App\Models\Renter;
use App\Models\VendorRenter;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRenter extends ViewRecord
{
    protected static string $resource = RenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_notes')
                ->label('Catatan & Status Pelanggan')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->modalHeading('Perbarui Catatan Internal Pelanggan')
                ->modalDescription('Catatan ini hanya dapat dilihat oleh vendor Anda dan tidak akan ditampilkan kepada penyewa.')
                ->fillForm(function (Renter $record): array {
                    $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                    $profile = VendorRenter::firstOrCreate([
                        'vendor_id' => $vendorId,
                        'renter_id' => $record->id,
                    ]);

                    return [
                        'reliability_tag' => $profile->reliability_tag,
                        'internal_notes' => $profile->internal_notes,
                    ];
                })
                ->form([
                    Select::make('reliability_tag')
                        ->label('Penanda Reliabilitas')
                        ->options(VendorRenter::RELIABILITY_LABELS)
                        ->required(),
                    Textarea::make('internal_notes')
                        ->label('Catatan Khusus Vendor')
                        ->placeholder('Contoh: Pelanggan langganan, selalu mengembalikan unit tepat waktu dan bersih...')
                        ->rows(4)
                        ->maxLength(1000),
                ])
                ->action(function (Renter $record, array $data): void {
                    $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                    $profile = VendorRenter::firstOrCreate([
                        'vendor_id' => $vendorId,
                        'renter_id' => $record->id,
                    ]);

                    $profile->update([
                        'reliability_tag' => $data['reliability_tag'] ?? VendorRenter::RELIABILITY_NORMAL,
                        'internal_notes' => $data['internal_notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Catatan Pelanggan Disimpan')
                        ->body('Data catatan internal dan penanda reliabilitas pelanggan berhasil diperbarui.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
