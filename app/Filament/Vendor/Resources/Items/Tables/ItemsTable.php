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
                TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Item $record): ?string => $record->size ? "Ukuran: {$record->size}" : null),
                TextColumn::make('price_per_day')
                    ->label('Harga / Hari')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('deposit_amount')
                    ->label('Deposit')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('buffer_summary')
                    ->label('Buffer Kalender')
                    ->state(fn (Item $record): string => "{$record->buffer_before} / {$record->buffer_after} hr")
                    ->tooltip('Jeda sebelum / sesudah sewa'),
                ToggleColumn::make('is_active')
                    ->label('Status Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                SelectFilter::make('size')
                    ->label('Ukuran')
                    ->options([
                        'S' => 'S',
                        'M' => 'M',
                        'L' => 'L',
                        'XL' => 'XL',
                        'XXL' => 'XXL',
                        'All Size' => 'All Size',
                    ]),
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
