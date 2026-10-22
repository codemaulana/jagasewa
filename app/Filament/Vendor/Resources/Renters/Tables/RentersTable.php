<?php

namespace App\Filament\Vendor\Resources\Renters\Tables;

use App\Models\Booking;
use App\Models\Renter;
use App\Models\VendorRenter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RentersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Renter $record): ?string => $record->address ? substr($record->address, 0, 40).'...' : null),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->placeholder('-'),

                TextColumn::make('reliability_status')
                    ->label('Reliabilitas')
                    ->badge()
                    ->state(function (Renter $record): string {
                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                        $profile = VendorRenter::where('vendor_id', $vendorId)
                            ->where('renter_id', $record->id)
                            ->first();

                        if (! $profile) {
                            $profile = new VendorRenter([
                                'vendor_id' => $vendorId,
                                'renter_id' => $record->id,
                            ]);
                            $profile->recalculateMetrics();
                        }

                        return $profile->reliability_label;
                    })
                    ->color(function (Renter $record): string {
                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                        $profile = VendorRenter::where('vendor_id', $vendorId)
                            ->where('renter_id', $record->id)
                            ->first();

                        $tag = $profile?->reliability_tag ?? VendorRenter::RELIABILITY_NORMAL;

                        return match ($tag) {
                            VendorRenter::RELIABILITY_TRUSTED => 'success',
                            VendorRenter::RELIABILITY_CAUTION => 'warning',
                            VendorRenter::RELIABILITY_PROBLEMATIC => 'danger',
                            default => 'info',
                        };
                    }),

                TextColumn::make('vendor_bookings_count')
                    ->label('Total Sewa')
                    ->state(function (Renter $record): string {
                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                        $count = Booking::where('vendor_id', $vendorId)
                            ->where('renter_id', $record->id)
                            ->count();

                        return "{$count} pesanan";
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->withCount(['bookings as vendor_bookings_count' => function ($q) {
                            $q->where('vendor_id', auth('vendor')->user()?->vendor_id ?? session('vendor_id'));
                        }])->orderBy('vendor_bookings_count', $direction);
                    }),

                TextColumn::make('vendor_total_spent')
                    ->label('Total Transaksi')
                    ->state(function (Renter $record): string {
                        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
                        $spent = Booking::where('vendor_id', $vendorId)
                            ->where('renter_id', $record->id)
                            ->whereIn('status', [Booking::STATUS_CLOSED, Booking::STATUS_ACTIVE, Booking::STATUS_RETURNED])
                            ->sum('total_price');

                        return 'Rp '.number_format((int) $spent, 0, ',', '.');
                    })
                    ->fontFamily('mono')
                    ->weight('bold'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Rincian'),
            ])
            ->defaultSort('name', 'asc');
    }
}
