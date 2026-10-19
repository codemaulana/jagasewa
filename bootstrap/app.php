<?php

use App\Http\Middleware\EnsureVendorOwnership;
use App\Http\Middleware\SecurityHeaders;
use App\Services\ErrorMonitoringService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES', '');
        $proxies = $trustedProxies === '*'
            ? '*'
            : array_values(array_filter(array_merge(
                array_filter(array_map('trim', explode(',', $trustedProxies))),
                [
                    '173.245.48.0/20',
                    '103.21.244.0/22',
                    '103.22.200.0/22',
                    '103.31.4.0/22',
                    '141.101.64.0/18',
                    '108.162.192.0/18',
                    '190.93.240.0/20',
                    '188.114.96.0/20',
                    '197.234.240.0/22',
                    '198.41.128.0/17',
                    '162.158.0.0/15',
                    '104.16.0.0/13',
                    '104.24.0.0/14',
                    '172.64.0.0/13',
                    '131.0.72.0/22',
                    '2400:cb00::/32',
                    '2606:4700::/32',
                    '2803:f800::/32',
                    '2405:b500::/32',
                    '2405:8100::/32',
                    '2a06:98c0::/29',
                    '2c0f:f248::/32',
                ]
            )));

        $middleware->trustProxies(at: $proxies);

        // SEC-37: HTTP Security Headers & Anti-Clickjacking Middleware (T-38)
        $middleware->append(SecurityHeaders::class);

        // SEC-22: Webhook endpoints excluded from CSRF verification (T-42)
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->alias([
            'vendor.ownership' => EnsureVendorOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            if (app()->bound(ErrorMonitoringService::class)) {
                app(ErrorMonitoringService::class)->captureException($e);
            }
        });
    })->create();
