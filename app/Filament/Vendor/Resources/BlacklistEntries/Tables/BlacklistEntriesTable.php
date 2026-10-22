<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries\Tables;

use App\Models\BlacklistEntry;
use App\Services\AuditService;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlacklistEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('identity_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        BlacklistEntry::TYPE_WA => 'WhatsApp',
                        BlacklistEntry::TYPE_NIK => 'NIK',
                        BlacklistEntry::TYPE_INSTAGRAM => 'Instagram',
                        default => strtoupper($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        BlacklistEntry::TYPE_WA => 'success',
                        BlacklistEntry::TYPE_NIK => 'warning',
                        BlacklistEntry::TYPE_INSTAGRAM => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('masked_identity')
                    ->label('Identitas Tersamarkan')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->placeholder('******'),

                TextColumn::make('scope')
                    ->label('Cakupan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        BlacklistEntry::SCOPE_SHARED => 'Komunitas Bersama [Pro]',
                        default => 'Internal Toko',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        BlacklistEntry::SCOPE_SHARED => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        BlacklistEntry::STATUS_VERIFIED => 'Terverifikasi',
                        BlacklistEntry::STATUS_DISPUTED => 'Disanggah',
                        BlacklistEntry::STATUS_PENDING => 'Menunggu Review',
                        BlacklistEntry::STATUS_REJECTED => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        BlacklistEntry::STATUS_VERIFIED => 'success',
                        BlacklistEntry::STATUS_DISPUTED => 'warning',
                        BlacklistEntry::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('identity_hash')
                    ->label('Blind Index Hash')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 10).'...'.substr($state, -8))
                    ->copyable()
                    ->copyableState(fn (BlacklistEntry $record): string => $record->identity_hash)
                    ->fontFamily('mono')
                    ->color('gray')
                    ->tooltip('HMAC-SHA256 hash tersimpan'),

                TextColumn::make('created_at')
                    ->label('Ditambahkan Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('identity_type')
                    ->label('Tipe Identitas')
                    ->options([
                        BlacklistEntry::TYPE_WA => 'WhatsApp',
                        BlacklistEntry::TYPE_NIK => 'NIK',
                        BlacklistEntry::TYPE_INSTAGRAM => 'Instagram',
                    ]),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus dari Blacklist?')
                    ->modalDescription('Apakah Anda yakin ingin menghapus entri blacklist ini? Penyewa ini tidak akan lagi memicu peringatan blacklist.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->after(function (BlacklistEntry $record) {
                        AuditService::log(
                            event: 'blacklist_deleted',
                            entityType: 'blacklist_entry',
                            entityId: (string) $record->id,
                            payload: [
                                'identity_type' => $record->identity_type,
                            ],
                            vendorId: $record->vendor_id
                        );
                    }),
            ]);
    }
}
