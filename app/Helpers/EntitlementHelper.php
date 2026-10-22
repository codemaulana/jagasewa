<?php

namespace App\Helpers;

use App\Models\Vendor;
use App\Services\Billing\EntitlementService;

class EntitlementHelper
{
    /**
     * Check if a vendor can use a specific feature.
     */
    public static function canUse(?Vendor $vendor, string $feature): bool
    {
        return app(EntitlementService::class)->canUse($vendor, $feature);
    }

    /**
     * Get numeric limit for a key under vendor's plan.
     */
    public static function limitFor(?Vendor $vendor, string $key): int
    {
        return app(EntitlementService::class)->limitFor($vendor, $key);
    }

    /**
     * Check if a vendor is Pro.
     */
    public static function isPro(?Vendor $vendor): bool
    {
        return app(EntitlementService::class)->isPro($vendor);
    }
}

if (! function_exists('canUse')) {
    function canUse(?Vendor $vendor, string $feature): bool
    {
        return EntitlementHelper::canUse($vendor, $feature);
    }
}

if (! function_exists('limitFor')) {
    function limitFor(?Vendor $vendor, string $key): int
    {
        return EntitlementHelper::limitFor($vendor, $key);
    }
}
