<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED_TIMEOUT = 'cancelled_timeout';

    public const STATUS_CANCELLED_RENTER = 'cancelled_renter';

    public const STATUS_CANCELLED_VENDOR = 'cancelled_vendor';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reference_code',
        'idempotency_key',
        'lock_version',
        'vendor_id',
        'item_id',
        'renter_id',
        'start_date',
        'end_date',
        'total_days',
        'total_price',
        'deposit_paid',
        'fine_amount',
        'status',
        'pending_expires_at',
        'vendor_notes',
        'renter_notes',
        'approved_at',
        'activated_at',
        'returned_at',
        'closed_at',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'pending_expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'returned_at' => 'datetime',
            'closed_at' => 'datetime',
            'lock_version' => 'integer',
            'total_days' => 'integer',
            'total_price' => 'integer',
            'deposit_paid' => 'integer',
            'fine_amount' => 'integer',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->reference_code)) {
                $booking->reference_code = self::generateReferenceCode();
            }

            if (empty($booking->pending_expires_at) && $booking->status === self::STATUS_PENDING) {
                $booking->pending_expires_at = Carbon::now('Asia/Jakarta')->addHours(24);
            }
        });
    }

    /**
     * Generate a unique reference code: JS-YYYYMMDD-XXXX.
     */
    public static function generateReferenceCode(): string
    {
        $date = Carbon::now('Asia/Jakarta')->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "JS-{$date}-{$random}";
    }

    /**
     * Check if booking status blocks calendar dates (per BR-02).
     */
    public function isBlockingCalendar(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_ACTIVE,
            self::STATUS_RETURNED,
        ], true);
    }

    /**
     * Get the vendor that owns the booking.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the rented item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the renter.
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    /**
     * Get the identity documents for the booking.
     */
    public function identityDocuments(): HasMany
    {
        return $this->hasMany(IdentityDocument::class);
    }
}
