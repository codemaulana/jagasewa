<?php

namespace Tests\Feature;

use App\Services\ErrorMonitoringService;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ErrorMonitoringTest extends TestCase
{
    public function test_it_sanitizes_pii_from_exception_message_and_context(): void
    {
        $service = app(ErrorMonitoringService::class);

        $sensitiveMessage = 'Failed booking for NIK 3201012345678901 and WhatsApp 081234567890 with password=mysecret123';
        $sanitized = $service->sanitizeString($sensitiveMessage);

        $this->assertStringNotContainsString('3201012345678901', $sanitized);
        $this->assertStringNotContainsString('081234567890', $sanitized);
        $this->assertStringNotContainsString('mysecret123', $sanitized);
        $this->assertStringContainsString('[REDACTED_NIK]', $sanitized);
        $this->assertStringContainsString('[REDACTED_PHONE]', $sanitized);
        $this->assertStringContainsString('password=[REDACTED]', $sanitized);

        $context = [
            'nik' => '3201012345678901',
            'phone' => '081234567890',
            'password' => 'supersecret',
            'user' => [
                'nik_hash' => 'blindindexhash',
                'description' => 'User NIK 3201012345678901 registered',
            ],
        ];

        $sanitizedContext = $service->sanitizeContext($context);
        $this->assertEquals('[REDACTED]', $sanitizedContext['nik']);
        $this->assertEquals('[REDACTED]', $sanitizedContext['phone']);
        $this->assertEquals('[REDACTED]', $sanitizedContext['password']);
        $this->assertEquals('[REDACTED]', $sanitizedContext['user']['nik_hash']);
        $this->assertStringNotContainsString('3201012345678901', $sanitizedContext['user']['description']);
    }

    public function test_it_dispatches_error_to_sentry_store_api(): void
    {
        Http::fake([
            'https://o123456.ingest.sentry.io/api/450123456/store/' => Http::response(['id' => 'sentry_event_123'], 200),
        ]);

        config([
            'services.monitoring.sentry_dsn' => 'https://mockpubkey@o123456.ingest.sentry.io/450123456',
            'services.monitoring.webhook_url' => null,
            'services.monitoring.enabled' => true,
        ]);

        $service = app(ErrorMonitoringService::class);
        $result = $service->captureException(new Exception('Testing Sentry Dispatch'), [
            'renter_id' => '01JABCDEF123456789',
        ]);

        $this->assertTrue($result['sentry_sent']);
        $this->assertFalse($result['webhook_sent']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://o123456.ingest.sentry.io/api/450123456/store/'
                && $request->hasHeader('X-Sentry-Auth')
                && str_contains($request['exception']['values'][0]['value'], 'Testing Sentry Dispatch');
        });
    }

    public function test_it_dispatches_error_to_discord_webhook(): void
    {
        Http::fake([
            'https://discord.com/api/webhooks/123/xyz' => Http::response(['id' => 'msg_123'], 204),
        ]);

        config([
            'services.monitoring.sentry_dsn' => null,
            'services.monitoring.webhook_url' => 'https://discord.com/api/webhooks/123/xyz',
            'services.monitoring.enabled' => true,
        ]);

        $service = app(ErrorMonitoringService::class);
        $result = $service->captureException(new Exception('Critical Booking Error'));

        $this->assertFalse($result['sentry_sent']);
        $this->assertTrue($result['webhook_sent']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/api/webhooks/123/xyz'
                && isset($request['embeds'][0]['title'])
                && str_contains($request['embeds'][0]['title'], 'Exception');
        });
    }

    public function test_it_runs_artisan_monitor_test_command(): void
    {
        Http::fake();

        $exitCode = Artisan::call('monitor:test', [
            '--message' => 'Test command execution verification',
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_it_respects_disabled_monitoring(): void
    {
        Http::fake();

        config([
            'services.monitoring.enabled' => false,
        ]);

        $service = app(ErrorMonitoringService::class);
        $result = $service->captureException(new Exception('Should not send'));

        $this->assertFalse($result['sentry_sent']);
        $this->assertFalse($result['webhook_sent']);
        $this->assertNotNull($result['error']);

        Http::assertNothingSent();
    }
}
