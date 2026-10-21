<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply strict HTTP security headers.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // SEC-37: Anti-Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // SEC-37: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // SEC-37: Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // SEC-37: Strict Transport Security (HSTS - 1 Year)
        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Rule 12 & SEC-37: Content Security Policy with Backblaze B2, AWS S3, and blob: for Livewire image uploads
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https://*.backblazeb2.com https://*.amazonaws.com",
            "connect-src 'self' blob: https://*.backblazeb2.com https://*.amazonaws.com",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy (Restrict unneeded device hardware)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Sembunyikan versi PHP
        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
