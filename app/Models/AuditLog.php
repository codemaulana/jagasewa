<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUlids;

    /**
     * Disable updated_at timestamp since audit logs are immutable.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'user_id',
        'event',
        'entity_type',
        'entity_id',
        'payload',
        'ip_address',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the vendor associated with the audit log.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user who performed the audited action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(VendorUser::class, 'user_id');
    }
}
