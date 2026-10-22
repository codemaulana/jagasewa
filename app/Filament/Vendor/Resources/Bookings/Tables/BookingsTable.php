<?php

namespace App\Filament\Vendor\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Support\Tables\MoneyColumn;
use App\Filament\Support\Tables\StatusColumn;
use App\Helpers\FormatHelper;
use App\Models\Booking;
use App\Models\Item;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kode booking disalin')
                    ->weight('bold'),

                TextColumn::make('item.name')
                    ->label('Barang')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Booking $record): ?string => $record->item?->size ? "Ukuran: {$record->item->size}" : null),

                TextColumn::make('renter.name')
                    ->label('Penyewa')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Booking $record): ?string => $record->renter?->phone ? "WA: {$record->renter->phone}" : null),

                TextColumn::make('rental_dates')
                    ->label('Periode Sewa')
                    ->state(function (Booking $record): string {
                        $start = FormatHelper::date($record->start_date);
                        $end = FormatHelper::date($record->end_date);

                        return "{$start} - {$end}";
                    })
                    ->description(fn (Booking $record): string => FormatHelper::days($record->total_days))
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('start_date', $direction)),

                MoneyColumn::make('total_price')
                    ->label('Total Biaya')
                    ->description(fn (Booking $record): ?string => $record->deposit_paid > 0 ? 'Dep: '.FormatHelper::rupiah($record->deposit_paid) : null)
                    ->sortable(),

                StatusColumn::make('status')
                    ->label('Status')
                    ->forEnum(BookingStatus::class)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->formatStateUsing(fn ($state) => FormatHelper::date($state, 'd M Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Booking')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $status) => [$status->value => $status->getLabel()])),

                SelectFilter::make('item_id')
                    ->label('Barang')
                    ->options(fn () => Item::forCurrentVendor()->pluck('name', 'id')->toArray()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
