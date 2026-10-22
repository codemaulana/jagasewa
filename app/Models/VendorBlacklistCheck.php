<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBlacklistCheck extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'month_year',
        'checks_count',
    ];

    protected function casts(): array
    {
        return [
            'checks_count' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get or create check tracker for a vendor in the given month.
     */
    public static function forCurrentMonth(Vendor $vendor): self
    {
        $monthYear = now('Asia/Jakarta')->format('Y-m');

        return self::firstOrCreate(
            ['vendor_id' => $vendor->id, 'month_year' => $monthYear],
            ['checks_count' => 0]
        );
    }
}
