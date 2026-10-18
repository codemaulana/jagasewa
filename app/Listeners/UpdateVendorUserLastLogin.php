<?php

namespace App\Listeners;

use App\Models\VendorUser;
use Illuminate\Auth\Events\Login;

class UpdateVendorUserLastLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof VendorUser) {
            $event->user->updateQuietly([
                'last_login_at' => now(),
            ]);

            session(['vendor_id' => $event->user->vendor_id]);
        }
    }
}
