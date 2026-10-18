<?php

namespace App\Filament\Vendor\Pages\Auth;

use App\Models\Vendor;
use App\Models\VendorUser;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SensitiveParameter;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getStoreNameFormComponent(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getWaNumberFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getStoreNameFormComponent(): Component
    {
        return TextInput::make('store_name')
            ->label('Nama Rental / Toko')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nama Pemilik / Pengelola')
            ->required()
            ->maxLength(255);
    }

    protected function getWaNumberFormComponent(): Component
    {
        return TextInput::make('wa_number')
            ->label('Nomor WhatsApp')
            ->required()
            ->tel()
            ->maxLength(20)
            ->placeholder('081234567890');
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $baseSlug = Str::slug($data['store_name']);
            if (empty($baseSlug)) {
                $baseSlug = 'rental-'.Str::lower(Str::random(6));
            }

            $slug = $baseSlug;
            $counter = 1;
            while (Vendor::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $vendor = Vendor::create([
                'name' => $data['store_name'],
                'slug' => $slug,
                'wa_number' => $data['wa_number'],
                'email' => $data['email'],
                'is_active' => true,
            ]);

            $vendorUser = VendorUser::create([
                'vendor_id' => $vendor->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'owner',
            ]);

            session(['vendor_id' => $vendor->id]);

            return $vendorUser;
        });
    }
}
