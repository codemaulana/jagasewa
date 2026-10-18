<?php

namespace App\Filament\Vendor\Resources\Bookings;

use App\Filament\Vendor\Resources\Bookings\Pages\ListBookings;
use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Vendor\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Vendor\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Daftar Booking';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Daftar Booking';

    protected static ?string $recordTitleAttribute = 'reference_code';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forCurrentVendor()
            ->with(['item', 'renter']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', Booking::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }
}
