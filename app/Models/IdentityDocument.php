<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdentityDocument extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const DOC_TYPE_KTP = 'ktp';

    public const DOC_TYPE_SELFIE = 'selfie';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'renter_id',
        'doc_type',
        'storage_path',
        'storage_disk',
        'file_size',
        'mime_type',
        'consent_given',
        'consent_version',
        'consent_at',
        'delete_scheduled_at',
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
            'consent_given' => 'boolean',
            'consent_at' => 'datetime',
            'delete_scheduled_at' => 'datetime',
            'deleted_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (IdentityDocument $doc) {
            if (empty($doc->consent_at) && $doc->consent_given) {
                $doc->consent_at = Carbon::now('Asia/Jakarta');
            }

            if (empty($doc->delete_scheduled_at)) {
                $doc->delete_scheduled_at = Carbon::now('Asia/Jakarta')->addDays(config('jagasewa.retention.closed_days', 7));
            }
        });
    }

    /**
     * Check if the document is a KTP.
     */
    public function isKtp(): bool
    {
        return $this->doc_type === self::DOC_TYPE_KTP;
    }

    /**
     * Check if the document is a selfie.
     */
    public function isSelfie(): bool
    {
        return $this->doc_type === self::DOC_TYPE_SELFIE;
    }

    /**
     * Get the booking associated with this document.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the renter associated with this document.
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    /**
     * Scope query to documents eligible for auto-deletion according to privacy retention policy.
     */
    public function scopeEligibleForDeletion(Builder $query, ?Carbon $now = null): Builder
    {
        $now = $now ?? Carbon::now('Asia/Jakarta');
        $fallbackDays = (int) config('jagasewa.retention.fallback_days', 90);
        $closedDays = (int) config('jagasewa.retention.closed_days', 7);
        $cancelledDays = (int) config('jagasewa.retention.cancelled_days', 3);

        return $query->where(function (Builder $q) use ($now, $fallbackDays, $closedDays, $cancelledDays) {
            // 1. Explicitly scheduled deletion reached
            $q->where(function (Builder $sub) use ($now) {
                $sub->whereNotNull('delete_scheduled_at')
                    ->where('delete_scheduled_at', '<=', $now);
            })
            // 2. Fallback: document uploaded > fallbackDays ago
                ->orWhere('created_at', '<=', $now->copy()->subDays($fallbackDays))
            // 3. Booking is closed > closedDays ago
                ->orWhereHas('booking', function (Builder $sub) use ($now, $closedDays) {
                    $sub->where('status', Booking::STATUS_CLOSED)
                        ->where('updated_at', '<=', $now->copy()->subDays($closedDays));
                })
            // 4. Booking is cancelled/rejected > cancelledDays ago
                ->orWhereHas('booking', function (Builder $sub) use ($now, $cancelledDays) {
                    $sub->whereIn('status', [
                        Booking::STATUS_REJECTED,
                        Booking::STATUS_CANCELLED_TIMEOUT,
                        Booking::STATUS_CANCELLED_RENTER,
                        Booking::STATUS_CANCELLED_VENDOR,
                    ])->where('updated_at', '<=', $now->copy()->subDays($cancelledDays));
                });
        });
    }
}
