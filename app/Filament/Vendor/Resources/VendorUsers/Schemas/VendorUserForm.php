<?php

namespace App\Filament\Vendor\Resources\VendorUsers\Schemas;

use App\Models\VendorUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun Staf')
                    ->description('Tentukan hak akses staf operasional atau pemilik toko.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'vendor_users',
                                column: 'email',
                                ignorable: fn (?VendorUser $record) => $record
                            ),

                        Select::make('role')
                            ->label('Peran & Hak Akses')
                            ->options(VendorUser::ROLE_LABELS)
                            ->default(VendorUser::ROLE_OPERATIONAL)
                            ->required()
                            ->helperText('Staf operasional hanya dapat mengelola pesanan, serah-terima, dan kondisi fisik tanpa melihat omzet/rekening toko.'),

                        TextInput::make('password')
                            ->label('Kata Sandi (Password)')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Kosongkan jika tidak ingin mengubah kata sandi.'
                                : 'Minimal 8 karakter.'),
                    ]),
            ]);
    }
}
