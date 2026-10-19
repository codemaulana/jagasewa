<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'vendor_id',
        'actual_return_date',
        'late_days',
        'late_fine',
        'damage_deduction',
        'total_deduction',
        'deposit_remaining',
        'damage_notes',
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
            'actual_return_date' => 'date',
            'late_days' => 'integer',
            'late_fine' => 'integer',
            'damage_deduction' => 'integer',
            'total_deduction' => 'integer',
            'deposit_remaining' => 'integer',
        ];
    }

    /**
     * Get the booking associated with the settlement.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the vendor that owns the settlement.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the damage items recorded for the settlement.
     */
    public function damageItems(): HasMany
    {
        return $this->hasMany(DamageItem::class);
    }

    /**
     * Get refund amount due to renter (if deposit_remaining > 0).
     */
    public function getRefundAmountAttribute(): int
    {
        return max(0, (int) $this->deposit_remaining);
    }

    /**
     * Get shortfall amount due from renter (if deposit_remaining < 0).
     */
    public function getShortfallAmountAttribute(): int
    {
        return max(0, -((int) $this->deposit_remaining));
    }
}
