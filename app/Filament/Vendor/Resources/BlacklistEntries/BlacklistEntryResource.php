<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries;

use App\Filament\Vendor\Resources\BlacklistEntries\Pages\CreateBlacklistEntry;
use App\Filament\Vendor\Resources\BlacklistEntries\Pages\ListBlacklistEntries;
use App\Filament\Vendor\Resources\BlacklistEntries\Schemas\BlacklistEntryForm;
use App\Filament\Vendor\Resources\BlacklistEntries\Tables\BlacklistEntriesTable;
use App\Models\BlacklistEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlacklistEntryResource extends Resource
{
    protected static ?string $model = BlacklistEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|\UnitEnum|null $navigationGroup = 'Pelanggan & Keamanan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Blacklist';

    protected static ?string $modelLabel = 'Blacklist';

    protected static ?string $pluralModelLabel = 'Blacklist Penyewa';

    protected static ?string $recordTitleAttribute = 'reason';

    public static function form(Schema $schema): Schema
    {
        return BlacklistEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlacklistEntriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forCurrentVendor();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlacklistEntries::route('/'),
            'create' => CreateBlacklistEntry::route('/create'),
        ];
    }
}
