<?php

namespace App\Models\Concerns;

use App\Models\Vendor;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait BelongsToVendor
{
    /**
     * Boot the trait to automatically assign vendor_id on create.
     */
    public static function bootBelongsToVendor(): void
    {
        static::creating(function ($model) {
            if (empty($model->vendor_id)) {
                $vendorUser = auth('vendor')->user();
                $vendorId = $vendorUser?->vendor_id ?? session('vendor_id');

                if ($vendorId) {
                    $model->vendor_id = $vendorId;
                }
            }
        });
    }

    /**
     * Scope a query to only include resources belonging to the current vendor.
     */
    public function scopeForCurrentVendor(Builder $query): Builder
    {
        $vendorUser = auth('vendor')->user();
        $vendorId = $vendorUser?->vendor_id ?? session('vendor_id');

        if ($vendorId) {
            return $query->where($query->qualifyColumn('vendor_id'), $vendorId);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Get the vendor that owns the model.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Check if this model belongs to the current vendor.
     */
    public function belongsToCurrentVendor(): bool
    {
        $vendorUser = auth('vendor')->user();
        $vendorId = $vendorUser?->vendor_id ?? session('vendor_id');

        return ! empty($vendorId) && (int) $this->vendor_id === (int) $vendorId;
    }

    /**
     * Ensure the model belongs to the current authenticated vendor or throw 404 (IDOR prevention).
     */
    public function ensureBelongsToCurrentVendor(): void
    {
        if (! $this->belongsToCurrentVendor()) {
            AuditService::log(
                'idor_attempt_blocked',
                static::class,
                (string) $this->getKey(),
                ['target_vendor_id' => $this->vendor_id]
            );

            throw new NotFoundHttpException('Resource not found.');
        }
    }
}
