<?php

namespace App\Filament\Vendor\Resources\Renters;

use App\Filament\Vendor\Resources\Renters\Pages\ListRenters;
use App\Filament\Vendor\Resources\Renters\Pages\ViewRenter;
use App\Filament\Vendor\Resources\Renters\Schemas\RenterInfolist;
use App\Filament\Vendor\Resources\Renters\Tables\RentersTable;
use App\Models\Renter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RenterResource extends Resource
{
    protected static ?string $model = Renter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Pelanggan & Keamanan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Daftar Pelanggan';

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return RenterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RentersTable::configure($table);
    }

    /**
     * Vendor Isolation: A vendor can only see renters who have placed bookings with their store.
     */
    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');

        return parent::getEloquentQuery()
            ->whereHas('bookings', function (Builder $query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRenters::route('/'),
            'view' => ViewRenter::route('/{record}'),
        ];
    }
}
