<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
     * Get the bookings for this item.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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

    /**
     * Get array of signed/public URLs for this item's photos.
     *
     * @return list<string>
     */
    public function getPhotoUrlsAttribute(): array
    {
        $urls = [];
        if (! empty($this->photo_paths) && is_array($this->photo_paths)) {
            $hasS3 = ! empty(config('filesystems.disks.s3.key')) && ! empty(config('filesystems.disks.s3.bucket'));
            $diskName = $hasS3 || app()->environment('testing') ? 's3' : 'public';
            $disk = Storage::disk($diskName);

            foreach ($this->photo_paths as $path) {
                try {
                    $urls[] = $disk->temporaryUrl($path, now()->addMinutes(60));
                } catch (\Throwable) {
                    try {
                        $urls[] = $disk->url($path);
                    } catch (\Throwable) {
                        $urls[] = Storage::disk('public')->url($path);
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Item $item) {
            if (! empty($item->photo_paths) && is_array($item->photo_paths)) {
                $hasS3 = ! empty(config('filesystems.disks.s3.key')) && ! empty(config('filesystems.disks.s3.bucket'));
                $diskName = $hasS3 || app()->environment('testing') ? 's3' : 'public';
                $disk = Storage::disk($diskName);

                foreach ($item->photo_paths as $path) {
                    try {
                        $disk->delete($path);
                    } catch (\Throwable) {
                        // ignore error on cascade
                    }
                }
            }
        });
    }
}
