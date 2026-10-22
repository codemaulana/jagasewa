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

    public const CATEGORIES = [
        'Cosplay' => 'Kostum Cosplay',
        'Kamera & Lensa' => 'Kamera & Lensa',
        'Kebaya & Gaun' => 'Kebaya & Gaun',
        'Busana Adat' => 'Busana Adat',
        'Elektronik & Audio' => 'Elektronik & Audio',
        'Outdoor & Camping' => 'Outdoor & Camping',
        'Lainnya' => 'Lainnya',
    ];

    public const UNIT_STATUS_AVAILABLE = 'available';

    public const UNIT_STATUS_RENTED = 'rented';

    public const UNIT_STATUS_LAUNDRY = 'laundry';

    public const UNIT_STATUS_MAINTENANCE = 'maintenance';

    public const CONDITIONS = [
        'Sangat Baik' => 'Sangat Baik (Mulus / Prima)',
        'Baik' => 'Baik (Normal Digunakan)',
        'Ada Bekas Pakai' => 'Ada Bekas Pakai (Minor)',
        'Perlu Perhatian' => 'Perlu Perhatian / Pemeliharaan',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'category',
        'item_code',
        'slug',
        'description',
        'size',
        'condition',
        'included_items',
        'care_instructions',
        'photo_paths',
        'price_per_day',
        'package_price_3d',
        'deposit_amount',
        'fine_per_day',
        'buffer_before',
        'buffer_after',
        'unit_status',
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
            'package_price_3d' => 'integer',
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
     * Get array of signed/public URLs for this item's photos with cache-busting.
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
            $v = $this->updated_at ? $this->updated_at->timestamp : time();

            foreach ($this->photo_paths as $path) {
                try {
                    $url = $disk->temporaryUrl($path, now()->addMinutes(60));
                } catch (\Throwable) {
                    try {
                        $url = $disk->url($path);
                    } catch (\Throwable) {
                        $url = Storage::disk('public')->url($path);
                    }
                }

                $urls[] = str_contains($url, '?') ? "{$url}&v={$v}" : "{$url}?v={$v}";
            }
        }

        return $urls;
    }

    /**
     * Get the primary (first) photo URL with cache-busting.
     */
    public function getPrimaryPhotoUrlAttribute(): ?string
    {
        $urls = $this->photo_urls;

        return ! empty($urls) ? $urls[0] : null;
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
