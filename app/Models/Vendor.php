<?php

namespace App\Models;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'wa_number',
        'email',
        'is_active',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wa_number' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users associated with the vendor.
     */
    public function users(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    /**
     * Get the items belonging to the vendor.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the bookings belonging to the vendor.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the blacklist entries belonging to the vendor.
     */
    public function blacklistEntries(): HasMany
    {
        return $this->hasMany(BlacklistEntry::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updated(function (Vendor $vendor) {
            if ($vendor->wasChanged('is_active')) {
                AuditService::log(
                    event: $vendor->is_active ? 'vendor_activated' : 'vendor_deactivated',
                    entityType: 'vendor',
                    entityId: (string) $vendor->id,
                    payload: [
                        'vendor_name' => $vendor->name,
                        'is_active' => $vendor->is_active,
                    ],
                    vendorId: $vendor->id
                );
            }
        });
    }
}
