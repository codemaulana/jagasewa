<?php

namespace App\Filament\Vendor\Resources\Items\Tables;

use App\Enums\ItemStatus;
use App\Filament\Support\Tables\MoneyColumn;
use App\Filament\Support\Tables\StatusColumn;
use App\Helpers\FormatHelper;
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
                    ->extraAttributes(['class' => 'tabular-nums font-mono'])
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

                StatusColumn::make('unit_status')
                    ->label('Kondisi Unit')
                    ->forEnum(ItemStatus::class)
                    ->sortable(),

                MoneyColumn::make('price_per_day')
                    ->label('Harga / Hari')
                    ->description(fn (Item $record): ?string => $record->package_price_3d ? '3 Hari: '.FormatHelper::rupiah($record->package_price_3d) : null)
                    ->sortable(),

                MoneyColumn::make('deposit_amount')
                    ->label('Deposit')
                    ->sortable(),

                TextColumn::make('buffer_summary')
                    ->label('Jeda Buffer')
                    ->state(fn (Item $record): string => "{$record->buffer_before} / {$record->buffer_after} hr")
                    ->tooltip('Jeda sebelum / sesudah sewa'),

                ToggleColumn::make('is_active')
                    ->label('Katalog'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->formatStateUsing(fn ($state) => FormatHelper::date($state, 'd M Y'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(Item::CATEGORIES),
                SelectFilter::make('unit_status')
                    ->label('Status Operasional')
                    ->options(collect(ItemStatus::cases())->mapWithKeys(fn (ItemStatus $status) => [$status->value => $status->getLabel()])),
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
