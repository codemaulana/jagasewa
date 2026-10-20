<?php

namespace App\Models;

use App\Services\AuditService;
use App\Services\Billing\EntitlementService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

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
        'logo_path',
        'banner_path',
        'description',
        'address',
        'bank_account_info',
        'custom_wa_templates',
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
            'wa_number' => 'encrypted',
            'is_active' => 'boolean',
            'custom_wa_templates' => 'array',
        ];
    }

    /**
     * Get temporary signed URL or public URL for store logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        $hasS3 = ! empty(config('filesystems.disks.s3.key')) && ! empty(config('filesystems.disks.s3.bucket'));
        $diskName = $hasS3 || app()->environment('testing') ? 's3' : 'public';
        $disk = Storage::disk($diskName);

        try {
            return $disk->temporaryUrl($this->logo_path, now()->addMinutes(60));
        } catch (\Throwable) {
            try {
                return $disk->url($this->logo_path);
            } catch (\Throwable) {
                return Storage::disk('public')->url($this->logo_path);
            }
        }
    }

    /**
     * Get temporary signed URL or public URL for store banner.
     */
    public function getBannerUrlAttribute(): ?string
    {
        if (empty($this->banner_path)) {
            return null;
        }

        $hasS3 = ! empty(config('filesystems.disks.s3.key')) && ! empty(config('filesystems.disks.s3.bucket'));
        $diskName = $hasS3 || app()->environment('testing') ? 's3' : 'public';
        $disk = Storage::disk($diskName);

        try {
            return $disk->temporaryUrl($this->banner_path, now()->addMinutes(60));
        } catch (\Throwable) {
            try {
                return $disk->url($this->banner_path);
            } catch (\Throwable) {
                return Storage::disk('public')->url($this->banner_path);
            }
        }
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
     * Get the subscriptions belonging to the vendor.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the current latest subscription of the vendor.
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Get the active Pro subscription if present.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('plan', Subscription::PLAN_PRO)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            })
            ->latestOfMany();
    }

    /**
     * Get the latest pending payment subscription if present.
     */
    public function pendingSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_PENDING_PAYMENT)
            ->latestOfMany();
    }

    /**
     * Get the subscription webhook and transaction events for the vendor.
     */
    public function subscriptionEvents(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    /**
     * Get the manual subscription activations performed for the vendor.
     */
    public function manualActivations(): HasMany
    {
        return $this->hasMany(ManualActivation::class);
    }

    /**
     * Determine if the vendor has active Pro entitlement.
     */
    public function isPro(): bool
    {
        return app(EntitlementService::class)->isPro($this);
    }

    /**
     * Determine if the vendor can add or activate another item.
     */
    public function canAddActiveItem(): bool
    {
        return app(EntitlementService::class)->canAddActiveItem($this);
    }

    /**
     * Determine if the vendor can add another blacklist entry.
     */
    public function canAddBlacklistEntry(): bool
    {
        return app(EntitlementService::class)->canAddBlacklistEntry($this);
    }

    /**
     * Determine if the vendor has white label branding privileges.
     */
    public function hasWhiteLabel(): bool
    {
        return app(EntitlementService::class)->hasWhiteLabel($this);
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
