<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class InitialAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        // 1. If user is a VendorUser and their vendor has an uploaded logo, use the store logo
        if (method_exists($record, 'vendor') && $record->vendor?->logo_url) {
            return $record->vendor->logo_url;
        }

        // 2. Otherwise generate a clean inline SVG avatar with user's initial (CSP-friendly data URI)
        $name = Filament::getNameForDefaultAvatar($record);
        $initial = strtoupper(mb_substr(trim($name ?: 'U'), 0, 1));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40">'
            .'<rect width="40" height="40" rx="8" fill="rgb(17, 17, 17)"/>'
            .'<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="rgb(255, 255, 255)" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="700">'
            .htmlspecialchars($initial)
            .'</text></svg>';

        return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
    }
}
