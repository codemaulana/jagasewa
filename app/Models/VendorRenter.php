<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRenter extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    public const RELIABILITY_TRUSTED = 'trusted';

    public const RELIABILITY_NORMAL = 'normal';

    public const RELIABILITY_CAUTION = 'caution';

    public const RELIABILITY_PROBLEMATIC = 'problematic';

    public const RELIABILITY_LABELS = [
        self::RELIABILITY_TRUSTED => 'Penyewa Terpercaya',
        self::RELIABILITY_NORMAL => 'Standar',
        self::RELIABILITY_CAUTION => 'Perlu Perhatian',
        self::RELIABILITY_PROBLEMATIC => 'Bermasalah',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'renter_id',
        'internal_notes',
        'reliability_tag',
        'total_completed_rentals',
        'total_spent',
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
            'total_completed_rentals' => 'integer',
            'total_spent' => 'integer',
        ];
    }

    /**
     * Get the vendor relationship.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the renter relationship.
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    /**
     * Human-readable reliability badge label.
     */
    public function getReliabilityLabelAttribute(): string
    {
        return self::RELIABILITY_LABELS[$this->reliability_tag] ?? 'Standar';
    }

    /**
     * Recalculate metrics and automatic reliability status from vendor booking history.
     */
    public function recalculateMetrics(): void
    {
        $bookings = Booking::where('vendor_id', $this->vendor_id)
            ->where('renter_id', $this->renter_id)
            ->with('settlement')
            ->get();

        $completedCount = $bookings->where('status', Booking::STATUS_CLOSED)->count();
        $totalSpent = (int) $bookings->whereIn('status', [
            Booking::STATUS_CLOSED,
            Booking::STATUS_ACTIVE,
            Booking::STATUS_RETURNED,
        ])->sum('total_price');

        // Check if there are damage deductions or late fines in settlements
        $hasDamagesOrFines = $bookings->contains(function (Booking $b) {
            $settlement = $b->settlement;

            return $settlement && (($settlement->damage_deduction > 0) || ($settlement->late_fine > 0));
        });

        $cancelledCount = $bookings->where('status', Booking::STATUS_CANCELLED_RENTER)->count();

        // Determine reliability tag if vendor hasn't manually pinned 'problematic'
        $tag = $this->reliability_tag;
        if ($tag !== self::RELIABILITY_PROBLEMATIC) {
            if ($hasDamagesOrFines || $cancelledCount >= 2) {
                $tag = self::RELIABILITY_CAUTION;
            } elseif ($completedCount >= 2) {
                $tag = self::RELIABILITY_TRUSTED;
            } else {
                $tag = self::RELIABILITY_NORMAL;
            }
        }

        $this->update([
            'total_completed_rentals' => $completedCount,
            'total_spent' => $totalSpent,
            'reliability_tag' => $tag,
        ]);
    }
}
