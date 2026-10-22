<?php

namespace App\Filament\Vendor\Resources\VendorUsers;

use App\Filament\Vendor\Resources\VendorUsers\Pages\CreateVendorUser;
use App\Filament\Vendor\Resources\VendorUsers\Pages\EditVendorUser;
use App\Filament\Vendor\Resources\VendorUsers\Pages\ListVendorUsers;
use App\Filament\Vendor\Resources\VendorUsers\Schemas\VendorUserForm;
use App\Filament\Vendor\Resources\VendorUsers\Tables\VendorUsersTable;
use App\Models\VendorUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorUserResource extends Resource
{
    protected static ?string $model = VendorUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Kelola Staf';

    protected static ?string $modelLabel = 'Staf Toko';

    protected static ?string $pluralModelLabel = 'Daftar Staf Toko';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 85;

    public static function form(Schema $schema): Schema
    {
        return VendorUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorUsersTable::configure($table);
    }

    /**
     * Vendor Isolation & Role Restriction:
     * Only users belonging to the current vendor can be queried.
     */
    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorId);
    }

    /**
     * Staf Operasional tidak diizinkan membuka menu manajemen staf.
     */
    public static function canViewAny(): bool
    {
        $user = auth('vendor')->user();

        return $user && $user->isOwner();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorUsers::route('/'),
            'create' => CreateVendorUser::route('/create'),
            'edit' => EditVendorUser::route('/{record}/edit'),
        ];
    }
}
