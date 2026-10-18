<?php

namespace App\Filament\Vendor\Resources\Bookings\Tables;

use App\Models\Booking;
use App\Models\Item;
use Carbon\Carbon;
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
                        $start = $record->start_date ? Carbon::parse($record->start_date)->format('d M Y') : '-';
                        $end = $record->end_date ? Carbon::parse($record->end_date)->format('d M Y') : '-';

                        return "{$start} - {$end}";
                    })
                    ->description(fn (Booking $record): string => "{$record->total_days} hari")
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('start_date', $direction)),

                TextColumn::make('total_price')
                    ->label('Total Biaya')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->description(fn (Booking $record): ?string => $record->deposit_paid > 0 ? 'Dep: Rp '.number_format((int) $record->deposit_paid, 0, ',', '.') : null)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Booking::STATUS_PENDING => 'Menunggu',
                        Booking::STATUS_APPROVED => 'Disetujui',
                        Booking::STATUS_ACTIVE => 'Sedang Sewa',
                        Booking::STATUS_RETURNED => 'Dikembalikan',
                        Booking::STATUS_CLOSED => 'Selesai',
                        Booking::STATUS_REJECTED => 'Ditolak',
                        Booking::STATUS_CANCELLED_TIMEOUT => 'Batal (Timeout)',
                        Booking::STATUS_CANCELLED_RENTER => 'Batal (Penyewa)',
                        Booking::STATUS_CANCELLED_VENDOR => 'Batal (Vendor)',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Booking::STATUS_PENDING => 'warning',
                        Booking::STATUS_APPROVED => 'info',
                        Booking::STATUS_ACTIVE => 'success',
                        Booking::STATUS_RETURNED => 'purple',
                        Booking::STATUS_CLOSED => 'gray',
                        Booking::STATUS_REJECTED,
                        Booking::STATUS_CANCELLED_TIMEOUT,
                        Booking::STATUS_CANCELLED_RENTER,
                        Booking::STATUS_CANCELLED_VENDOR => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Booking')
                    ->options([
                        Booking::STATUS_PENDING => 'Menunggu Persetujuan',
                        Booking::STATUS_APPROVED => 'Disetujui',
                        Booking::STATUS_ACTIVE => 'Sedang Disewa',
                        Booking::STATUS_RETURNED => 'Dikembalikan',
                        Booking::STATUS_CLOSED => 'Selesai',
                        Booking::STATUS_REJECTED => 'Ditolak',
                        Booking::STATUS_CANCELLED_TIMEOUT => 'Batal (Timeout)',
                        Booking::STATUS_CANCELLED_RENTER => 'Batal (Penyewa)',
                        Booking::STATUS_CANCELLED_VENDOR => 'Batal (Vendor)',
                    ]),

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
