<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Renter extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'phone_encrypted',
        'phone_hash',
        'nik',
        'nik_encrypted',
        'nik_hash',
        'instagram_handle_hash',
        'address',
        'address_encrypted',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_encrypted' => 'encrypted',
            'nik_encrypted' => 'encrypted',
            'address_encrypted' => 'encrypted',
        ];
    }

    /**
     * Accessor for phone plaintext.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->phone_encrypted;
    }

    /**
     * Mutator for phone plaintext (automatically sets phone_encrypted and computes phone_hash).
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->phone_encrypted = $value;

        if (! empty($value)) {
            $normalized = self::normalizePhone($value);
            $this->phone_hash = self::hashValue($normalized);
        }
    }

    /**
     * Accessor for NIK plaintext.
     */
    public function getNikAttribute(): ?string
    {
        return $this->nik_encrypted;
    }

    /**
     * Mutator for NIK plaintext (automatically sets nik_encrypted and computes nik_hash).
     */
    public function setNikAttribute(?string $value): void
    {
        $this->nik_encrypted = $value;

        if (! empty($value)) {
            $normalized = preg_replace('/\D/', '', $value);
            $this->nik_hash = self::hashValue($normalized);
        }
    }

    /**
     * Accessor for address plaintext.
     */
    public function getAddressAttribute(): ?string
    {
        return $this->address_encrypted;
    }

    /**
     * Mutator for address plaintext.
     */
    public function setAddressAttribute(?string $value): void
    {
        $this->address_encrypted = $value;
    }

    /**
     * Normalize a phone number to standard Indonesian format (628...).
     */
    public static function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($cleaned, '+62')) {
            $cleaned = substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '08')) {
            $cleaned = '62'.substr($cleaned, 1);
        }

        return $cleaned;
    }

    /**
     * Compute HMAC-SHA256 blind index hash using BLACKLIST_SECRET or APP_KEY.
     */
    public static function hashValue(string $value): string
    {
        $secret = config('app.blacklist_secret') ?: config('app.key');

        return hash_hmac('sha256', $value, (string) $secret);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Renter $renter) {
            // Ensure phone_hash exists if phone_encrypted is present
            if (! empty($renter->phone_encrypted) && empty($renter->phone_hash)) {
                $renter->phone_hash = self::hashValue(self::normalizePhone($renter->phone_encrypted));
            }

            // Ensure nik_hash exists if nik_encrypted is present
            if (! empty($renter->nik_encrypted) && empty($renter->nik_hash)) {
                $renter->nik_hash = self::hashValue(preg_replace('/\D/', '', $renter->nik_encrypted));
            }
        });
    }

    /**
     * Get bookings made by the renter.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get identity documents uploaded by the renter.
     */
    public function identityDocuments(): HasMany
    {
        return $this->hasMany(IdentityDocument::class);
    }
}
