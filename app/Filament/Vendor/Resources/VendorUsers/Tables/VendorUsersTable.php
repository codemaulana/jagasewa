<?php

namespace App\Filament\Vendor\Resources\VendorUsers\Tables;

use App\Models\VendorUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Staf')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        VendorUser::ROLE_OWNER => 'Pemilik Toko',
                        default => 'Staf Operasional',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        VendorUser::ROLE_OWNER => 'success',
                        default => 'info',
                    }),

                TextColumn::make('last_login_at')
                    ->label('Terakhir Masuk')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum pernah login')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ditambahkan Pada')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->label('Ubah'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (VendorUser $record): bool => $record->id !== auth('vendor')->id()),
            ]);
    }
}
