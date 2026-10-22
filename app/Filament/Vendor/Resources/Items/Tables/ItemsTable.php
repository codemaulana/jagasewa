<?php

namespace App\Filament\Vendor\Resources\Items\Tables;

use App\Models\Item;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_paths')
                    ->label('Foto')
                    ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                    ->visibility('private')
                    ->circular()
                    ->stacked()
                    ->limit(2),

                TextColumn::make('item_code')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->description(function (Item $record): ?string {
                        $parts = [];
                        if ($record->category) {
                            $parts[] = $record->category;
                        }
                        if ($record->size) {
                            $parts[] = "Size: {$record->size}";
                        }

                        return ! empty($parts) ? implode(' · ', $parts) : null;
                    })
                    ->weight('semibold'),

                TextColumn::make('unit_status')
                    ->label('Kondisi Unit')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Item::UNIT_STATUS_AVAILABLE => 'Tersedia',
                        Item::UNIT_STATUS_RENTED => 'Disewa',
                        Item::UNIT_STATUS_LAUNDRY => 'Laundry',
                        Item::UNIT_STATUS_MAINTENANCE => 'Perbaikan',
                        default => 'Tersedia',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        Item::UNIT_STATUS_AVAILABLE => 'success',
                        Item::UNIT_STATUS_RENTED => 'info',
                        Item::UNIT_STATUS_LAUNDRY => 'warning',
                        Item::UNIT_STATUS_MAINTENANCE => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('price_per_day')
                    ->label('Harga / Hari')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->description(fn (Item $record): ?string => $record->package_price_3d ? '3 Hari: Rp '.number_format((int) $record->package_price_3d, 0, ',', '.') : null)
                    ->sortable(),

                TextColumn::make('deposit_amount')
                    ->label('Deposit')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('buffer_summary')
                    ->label('Jeda Buffer')
                    ->state(fn (Item $record): string => "{$record->buffer_before} / {$record->buffer_after} hr")
                    ->tooltip('Jeda sebelum / sesudah sewa'),

                ToggleColumn::make('is_active')
                    ->label('Katalog'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(Item::CATEGORIES),
                SelectFilter::make('unit_status')
                    ->label('Status Operasional')
                    ->options([
                        Item::UNIT_STATUS_AVAILABLE => 'Tersedia',
                        Item::UNIT_STATUS_RENTED => 'Disewa',
                        Item::UNIT_STATUS_LAUNDRY => 'Laundry',
                        Item::UNIT_STATUS_MAINTENANCE => 'Perbaikan',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Katalog Publik'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
