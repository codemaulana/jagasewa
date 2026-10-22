<?php

namespace App\Filament\Vendor\Resources\Items;

use App\Filament\Vendor\Resources\Items\Pages\CreateItem;
use App\Filament\Vendor\Resources\Items\Pages\EditItem;
use App\Filament\Vendor\Resources\Items\Pages\ListItems;
use App\Filament\Vendor\Resources\Items\Pages\ViewItem;
use App\Filament\Vendor\Resources\Items\Schemas\ItemForm;
use App\Filament\Vendor\Resources\Items\Schemas\ItemInfolist;
use App\Filament\Vendor\Resources\Items\Tables\ItemsTable;
use App\Models\Item;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Katalog Barang';

    protected static ?string $modelLabel = 'Barang';

    protected static ?string $pluralModelLabel = 'Katalog Barang';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
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
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
            'edit' => EditItem::route('/{record}/edit'),
        ];
    }
}
