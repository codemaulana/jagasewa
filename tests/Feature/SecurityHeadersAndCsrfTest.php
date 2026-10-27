<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersAndCsrfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enforce CSRF middleware evaluation in test environment (prevent runningUnitTests bypass)
        $this->app->bind(PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        });

        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        });
    }

    public function test_it_returns_all_mandatory_security_headers_on_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Anti-Clickjacking
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME Sniffing
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy with Backblaze B2 image whitelist
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('https://*.backblazeb2.com', $csp);
        $this->assertStringContainsString('https://cdn.tailwindcss.com', $csp);

        // Hide PHP version
        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_it_returns_hsts_header_on_https_request(): void
    {
        $response = $this->get('https://localhost/');

        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_tc39_csrf_protection_blocks_post_requests_without_token(): void
    {
        $this->startSession();

        // State-changing POST request without CSRF token must return HTTP 419
        $response = $this->post(route('vendor.login.submit'), [
            'email' => 'vendor@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(419);
    }

    public function test_tc39_csrf_protection_accepts_valid_token(): void
    {
        $this->startSession();

        // Request with session started and valid CSRF token must not return 419
        $response = $this->post(route('vendor.login.submit'), [
            '_token' => csrf_token(),
            'email' => 'invalid@example.com',
            'password' => 'wrong',
        ]);

        $this->assertNotEquals(419, $response->getStatusCode());
    }
}
