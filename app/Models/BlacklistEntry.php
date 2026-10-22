<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use App\Services\IdentityHashService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BlacklistEntry extends Model
{
    use BelongsToVendor, HasFactory, HasUlids;

    public const TYPE_WA = 'wa';

    public const TYPE_NIK = 'nik';

    public const TYPE_INSTAGRAM = 'instagram';

    public const SCOPE_PRIVATE = 'private';

    public const SCOPE_SHARED = 'shared';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PENDING = 'pending_review';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'identity_type',
        'identity_hash',
        'masked_identity',
        'scope',
        'verification_status',
        'reason',
        'evidence',
        'evidence_file_path',
        'is_disputed',
        'dispute_reason',
        'disputed_at',
        'dispute_status',
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
            'is_disputed' => 'boolean',
            'disputed_at' => 'datetime',
        ];
    }

    /**
     * Get temporary signed S3 URL for attached evidence.
     */
    public function getEvidenceUrlAttribute(): ?string
    {
        if (empty($this->evidence_file_path)) {
            return null;
        }

        $hasS3 = ! empty(config('filesystems.disks.s3.key')) && ! empty(config('filesystems.disks.s3.bucket'));
        $diskName = $hasS3 || app()->environment('testing') ? 's3' : 'public';

        try {
            return Storage::disk($diskName)->temporaryUrl(
                $this->evidence_file_path,
                now()->addMinutes(60)
            );
        } catch (\Throwable) {
            return Storage::disk('public')->url($this->evidence_file_path);
        }
    }

    /**
     * Generate safe masked string for public/shared display without leaking PII.
     */
    public static function maskValue(string $type, string $raw): string
    {
        $cleaned = trim($raw);
        if ($type === self::TYPE_WA) {
            $normalized = IdentityHashService::normalizePhone($cleaned);
            if (strlen($normalized) >= 9) {
                return substr($normalized, 0, 4).'****'.substr($normalized, -4);
            }

            return substr($normalized, 0, 2).'****';
        }

        if ($type === self::TYPE_NIK) {
            if (strlen($cleaned) >= 16) {
                return substr($cleaned, 0, 4).'********'.substr($cleaned, -4);
            }

            return substr($cleaned, 0, 2).'****';
        }

        if ($type === self::TYPE_INSTAGRAM) {
            $handle = ltrim($cleaned, '@');
            if (strlen($handle) > 3) {
                return '@'.substr($handle, 0, 2).'***'.substr($handle, -1);
            }

            return '@'.substr($handle, 0, 1).'***';
        }

        return '******';
    }

    /**
     * Get the vendor that owns the blacklist entry.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
