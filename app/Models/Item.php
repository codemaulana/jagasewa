<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'size',
        'included_items',
        'care_instructions',
        'photo_paths',
        'price_per_day',
        'deposit_amount',
        'fine_per_day',
        'buffer_before',
        'buffer_after',
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
     * Get the route key for the model (ULID for public safety, avoiding sequential IDs).
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
            'price_per_day' => 'integer',
            'deposit_amount' => 'integer',
            'fine_per_day' => 'integer',
            'buffer_before' => 'integer',
            'buffer_after' => 'integer',
            'is_active' => 'boolean',
            'photo_paths' => 'array',
            'included_items' => 'array',
        ];
    }

    /**
     * Get the vendor that owns the item.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Alias deposit accessor to deposit_amount.
     */
    public function getDepositAttribute(): int
    {
        return (int) $this->deposit_amount;
    }

    /**
     * Alias deposit mutator to deposit_amount.
     */
    public function setDepositAttribute($value): void
    {
        $this->attributes['deposit_amount'] = (int) $value;
    }
}
