<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlacklistEntry extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    public const TYPE_WA = 'wa';

    public const TYPE_NIK = 'nik';

    public const TYPE_INSTAGRAM = 'instagram';

    public const SCOPE_PRIVATE = 'private';

    public const SCOPE_SHARED = 'shared';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'identity_type',
        'identity_hash',
        'scope',
        'reason',
        'evidence',
        'expires_at',
        'consent_basis',
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
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the vendor that owns the blacklist entry.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
