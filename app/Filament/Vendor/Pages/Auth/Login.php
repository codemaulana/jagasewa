<?php

namespace App\Filament\Vendor\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Masuk ke Dashboard Toko';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola ketersediaan barang, jadwal sewa, dan pesanan toko Anda.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->placeholder('nama@toko-rental.com');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }
}
