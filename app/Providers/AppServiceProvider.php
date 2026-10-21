<?php

namespace App\Providers;

use App\Listeners\UpdateVendorUserLastLogin;
use App\Services\IdentityHashService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, UpdateVendorUserLastLogin::class);

        // SEC-11: Rate Limiter Configuration (T-37)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('booking.upload', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        RateLimiter::for('booking.public', function (Request $request) {
            $limits = [
                Limit::perHour(10)->by($request->ip()),
            ];

            if ($request->filled('phone')) {
                $phoneHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, (string) $request->input('phone'));
                $limits[] = Limit::perHour(3)->by('wa:'.$phoneHash);
            }

            return $limits;
        });

        RateLimiter::for('signed-url', function (Request $request) {
            $key = optional($request->user('vendor'))->vendor_id ?? $request->ip();

            return Limit::perMinute(20)->by('signed-url:'.$key);
        });

        RateLimiter::for('blacklist.check', function (Request $request) {
            $key = optional($request->user('vendor'))->vendor_id ?? $request->ip();

            return Limit::perMinute(30)->by('blacklist-check:'.$key);
        });

        RateLimiter::for('password.reset', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
    }
}
