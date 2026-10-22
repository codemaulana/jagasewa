<?php

namespace App\Filament\Vendor\Resources\BlacklistEntries\Schemas;

use App\Models\BlacklistEntry;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlacklistEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tambah Entri Blacklist')
                    ->description('Identitas penyewa akan di-hash secara aman menggunakan HMAC-SHA256 (Blind Index). Nilai mentah (plaintext) tidak disimpan di database demi menjaga privasi.')
                    ->schema([
                        Select::make('identity_type')
                            ->label('Jenis Identitas')
                            ->options([
                                BlacklistEntry::TYPE_WA => 'Nomor WhatsApp / HP',
                                BlacklistEntry::TYPE_NIK => 'NIK (Nomor Induk Kependudukan)',
                                BlacklistEntry::TYPE_INSTAGRAM => 'Akun Instagram',
                            ])
                            ->default(BlacklistEntry::TYPE_WA)
                            ->required()
                            ->live(),

                        TextInput::make('identity_value')
                            ->label('Nilai Identitas')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(fn ($get) => match ($get('identity_type')) {
                                BlacklistEntry::TYPE_WA => 'Contoh: 08123456789 atau +628123456789',
                                BlacklistEntry::TYPE_NIK => 'Contoh: 3271012345670001 (16 digit angka)',
                                BlacklistEntry::TYPE_INSTAGRAM => 'Contoh: @username atau username',
                                default => 'Masukkan identitas',
                            })
                            ->helperText('Otomatis dinormalisasi sebelum di-hash (misal: 08xx → 628xx).'),

                        Textarea::make('reason')
                            ->label('Alasan Blacklist')
                            ->required()
                            ->minLength(5)
                            ->maxLength(500)
                            ->placeholder('Jelaskan alasan pemblokiran (misal: merusak barang sewa dan menolak ganti rugi, memblokir kontak saat jatuh tempo, dll.)')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('scope')
                            ->label('Cakupan Blacklist')
                            ->options([
                                BlacklistEntry::SCOPE_PRIVATE => 'Internal Toko Anda Saja (Hanya vendor Anda yang dapat melihat)',
                                BlacklistEntry::SCOPE_SHARED => 'Lapor ke Blacklist Bersama Komunitas [Pro]',
                            ])
                            ->default(BlacklistEntry::SCOPE_PRIVATE)
                            ->helperText(fn () => auth('vendor')->user()?->vendor?->isPro()
                                ? 'Laporan bersama akan disamarkan secara otomatis di direktori komunitas JagaSewa untuk melindungi toko lain.'
                                : 'Paket Free hanya dapat menyimpan blacklist internal toko. Upgrade ke Pro untuk membagikan laporan ke komunitas.')
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        FileUpload::make('evidence_file_path')
                            ->label('Unggah Bukti Pelanggaran (Tangkapan Layar / Nota / Foto) [Pro]')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk(config('filesystems.disks.s3.key') || app()->environment('testing') ? 's3' : 'public')
                            ->directory('blacklist_evidence')
                            ->visibility('private')
                            ->maxSize(5120)
                            ->helperText('Bukti tersimpan aman di S3 privat dan hanya digunakan untuk verifikasi.')
                            ->visible(fn ($get) => $get('scope') === BlacklistEntry::SCOPE_SHARED)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
