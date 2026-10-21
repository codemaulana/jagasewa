<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    public const PLAN_FREE = 'free';

    public const PLAN_PRO = 'pro';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_EXPIRED = 'expired';

    public const CYCLE_MONTHLY = 'monthly';

    public const CYCLE_QUARTERLY = 'quarterly';

    public const CYCLE_ANNUALLY = 'annually';

    public const PRICE_MONTHLY = 15000;

    public const PRICE_QUARTERLY = 39000;

    public const PRICE_ANNUALLY = 129000;

    /**
     * Get standard Pro plan price in integer Rupiah for a given billing cycle.
     */
    public static function getPlanPrice(string $cycle): int
    {
        return match ($cycle) {
            self::CYCLE_QUARTERLY => self::PRICE_QUARTERLY,
            self::CYCLE_ANNUALLY => self::PRICE_ANNUALLY,
            default => self::PRICE_MONTHLY,
        };
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'plan',
        'status',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'gateway_subscription_id',
        'gateway_customer_id',
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
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
        ];
    }

    /**
     * Get the vendor that owns the subscription.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the events associated with this subscription.
     */
    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    /**
     * Get manual activation records associated with this subscription.
     */
    public function manualActivations(): HasMany
    {
        return $this->hasMany(ManualActivation::class);
    }

    /**
     * Determine if the subscription is currently active and within valid period.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->plan === self::PLAN_FREE) {
            return true;
        }

        if ($this->current_period_end === null) {
            return true;
        }

        return $this->current_period_end->isFuture();
    }

    /**
     * Determine if the subscription has Pro/Premium entitlement.
     */
    public function isPro(): bool
    {
        return $this->plan === self::PLAN_PRO && $this->isActive();
    }

    /**
     * Determine if the subscription is on the Free plan.
     */
    public function isFree(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }
}
