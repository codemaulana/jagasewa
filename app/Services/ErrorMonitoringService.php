<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ErrorMonitoringService
{
    /**
     * Capture and report an unhandled exception to configured monitoring services.
     *
     * @param  array<string, mixed>  $context
     * @return array{event_id: string, sentry_sent: bool, webhook_sent: bool, error: string|null}
     */
    public function captureException(Throwable $e, array $context = []): array
    {
        $enabled = config('services.monitoring.enabled', true);
        $eventId = str_replace('-', '', Str::uuid()->toString());

        if (! $enabled) {
            return [
                'event_id' => $eventId,
                'sentry_sent' => false,
                'webhook_sent' => false,
                'error' => 'Monitoring disabled by config',
            ];
        }

        $sentryDsn = config('services.monitoring.sentry_dsn');
        $webhookUrl = config('services.monitoring.webhook_url');

        $sanitizedMessage = $this->sanitizeString($e->getMessage());
        $sanitizedContext = $this->sanitizeContext($context);

        $eventData = [
            'event_id' => $eventId,
            'timestamp' => now()->toIso8601ZuluString(),
            'platform' => 'php',
            'level' => 'error',
            'logger' => 'jagasewa.error_monitor',
            'environment' => config('app.env', 'production'),
            'release' => '1.0.0',
            'exception' => [
                'values' => [
                    [
                        'type' => get_class($e),
                        'value' => $sanitizedMessage,
                        'stacktrace' => [
                            'frames' => $this->formatStackTrace($e),
                        ],
                    ],
                ],
            ],
            'tags' => [
                'app' => 'JagaSewa',
                'php_version' => PHP_VERSION,
                'environment' => config('app.env', 'production'),
            ],
            'extra' => $sanitizedContext,
        ];

        if (request()) {
            $eventData['request'] = [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'headers' => [
                    'User-Agent' => request()->header('User-Agent', 'Unknown'),
                ],
            ];
        }

        $sentrySent = false;
        if (! empty($sentryDsn)) {
            $sentrySent = $this->sendToSentry($sentryDsn, $eventData);
        }

        $webhookSent = false;
        if (! empty($webhookUrl)) {
            $webhookSent = $this->sendToWebhook($webhookUrl, $eventData, $e);
        }

        // Always record in local log without leaking PII
        Log::error("[Event ID: {$eventId}] Exception captured by ErrorMonitoring: ".$sanitizedMessage, [
            'class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'sentry_sent' => $sentrySent,
            'webhook_sent' => $webhookSent,
        ]);

        return [
            'event_id' => $eventId,
            'sentry_sent' => $sentrySent,
            'webhook_sent' => $webhookSent,
            'error' => null,
        ];
    }

    /**
     * Capture and report an informational message or alert to monitoring services.
     *
     * @param  array<string, mixed>  $context
     * @return array{event_id: string, sentry_sent: bool, webhook_sent: bool, error: string|null}
     */
    public function captureMessage(string $message, array $context = [], string $level = 'info'): array
    {
        $enabled = config('services.monitoring.enabled', true);
        $eventId = str_replace('-', '', Str::uuid()->toString());

        if (! $enabled) {
            return [
                'event_id' => $eventId,
                'sentry_sent' => false,
                'webhook_sent' => false,
                'error' => 'Monitoring disabled by config',
            ];
        }

        $sentryDsn = config('services.monitoring.sentry_dsn');
        $webhookUrl = config('services.monitoring.webhook_url');

        $sanitizedMessage = $this->sanitizeString($message);
        $sanitizedContext = $this->sanitizeContext($context);

        $eventData = [
            'event_id' => $eventId,
            'timestamp' => now()->toIso8601ZuluString(),
            'platform' => 'php',
            'level' => $level,
            'logger' => 'jagasewa.alert_monitor',
            'environment' => config('app.env', 'production'),
            'release' => '1.0.0',
            'message' => [
                'formatted' => $sanitizedMessage,
            ],
            'tags' => [
                'app' => 'JagaSewa',
                'environment' => config('app.env', 'production'),
            ],
            'extra' => $sanitizedContext,
        ];

        $sentrySent = false;
        if (! empty($sentryDsn)) {
            $sentrySent = $this->sendToSentry($sentryDsn, $eventData);
        }

        $webhookSent = false;
        if (! empty($webhookUrl)) {
            $webhookSent = $this->sendToWebhook($webhookUrl, $eventData, new \Exception($sanitizedMessage));
        }

        $logMethod = in_array($level, ['error', 'warning', 'info'], true) ? $level : 'info';
        Log::$logMethod("[Event ID: {$eventId}] Message captured by ErrorMonitoring: ".$sanitizedMessage, [
            'level' => $level,
            'sentry_sent' => $sentrySent,
            'webhook_sent' => $webhookSent,
        ]);

        return [
            'event_id' => $eventId,
            'sentry_sent' => $sentrySent,
            'webhook_sent' => $webhookSent,
            'error' => null,
        ];
    }

    /**
     * Dispatch event to Sentry Store REST API without heavy SDK dependency.
     */
    protected function sendToSentry(string $dsn, array $eventData): bool
    {
        $parsed = parse_url($dsn);
        if (! $parsed || ! isset($parsed['host'], $parsed['user'], $parsed['path'])) {
            Log::warning('Invalid SENTRY_DSN provided to ErrorMonitoringService.');

            return false;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $publicKey = $parsed['user'];
        $projectId = trim($parsed['path'], '/');

        $endpoint = "{$scheme}://{$host}{$port}/api/{$projectId}/store/";

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Sentry-Auth' => "Sentry sentry_version=7, sentry_client=jagasewa-native/1.0, sentry_key={$publicKey}",
                ])
                ->post($endpoint, $eventData);

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('Failed to dispatch error to Sentry: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Dispatch structured notification to Webhook (Discord / Slack / Telegram).
     */
    protected function sendToWebhook(string $webhookUrl, array $eventData, Throwable $e): bool
    {
        $isDiscord = str_contains($webhookUrl, 'discord.com/api/webhooks');
        $sanitizedMessage = $this->sanitizeString($e->getMessage());
        $env = config('app.env', 'production');
        $time = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s T');
        $url = request() ? request()->fullUrl() : 'CLI / Artisan';

        if ($isDiscord) {
            $payload = [
                'content' => '🚨 **[JagaSewa Alert] Error 500 Detected!**',
                'embeds' => [
                    [
                        'title' => 'Exception: '.class_basename($e),
                        'description' => substr($sanitizedMessage, 0, 500),
                        'color' => 15158332, // Red
                        'fields' => [
                            ['name' => 'Environment', 'value' => $env, 'inline' => true],
                            ['name' => 'Event ID', 'value' => $eventData['event_id'], 'inline' => true],
                            ['name' => 'Time', 'value' => $time, 'inline' => true],
                            ['name' => 'URL / Context', 'value' => $url, 'inline' => false],
                            ['name' => 'File', 'value' => basename($e->getFile()).':'.$e->getLine(), 'inline' => false],
                        ],
                        'footer' => [
                            'text' => 'JagaSewa Production Monitor',
                        ],
                    ],
                ],
            ];
        } else {
            // Slack / Generic Webhook payload
            $payload = [
                'text' => sprintf(
                    "🚨 *[JagaSewa Error Alert]*\n*Exception:* %s\n*Message:* %s\n*Environment:* %s\n*Time:* %s\n*Context:* %s\n*File:* %s:%d\n*Event ID:* %s",
                    class_basename($e),
                    $sanitizedMessage,
                    $env,
                    $time,
                    $url,
                    basename($e->getFile()),
                    $e->getLine(),
                    $eventData['event_id']
                ),
            ];
        }

        try {
            $response = Http::timeout(5)->post($webhookUrl, $payload);

            return $response->successful();
        } catch (Throwable $err) {
            Log::warning('Failed to dispatch error to Webhook: '.$err->getMessage());

            return false;
        }
    }

    /**
     * Format exception stack trace into Sentry frames.
     */
    protected function formatStackTrace(Throwable $e): array
    {
        $frames = [];
        $traces = array_slice($e->getTrace(), 0, 10);

        foreach (array_reverse($traces) as $trace) {
            $frames[] = [
                'filename' => $trace['file'] ?? '[internal]',
                'lineno' => $trace['line'] ?? 0,
                'function' => ($trace['class'] ?? '').($trace['type'] ?? '').($trace['function'] ?? ''),
            ];
        }

        $frames[] = [
            'filename' => $e->getFile(),
            'lineno' => $e->getLine(),
            'function' => 'throw',
        ];

        return $frames;
    }

    /**
     * Sanitize string to remove sensitive PII (NIK, Phone, Passwords).
     */
    public function sanitizeString(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        // Mask 16-digit NIK
        $text = preg_replace('/\b\d{16}\b/', '[REDACTED_NIK]', $text);

        // Mask Indonesian WhatsApp / Phone numbers (+628, 628, 08)
        $text = preg_replace('/\b(\+?62|0)8[1-9][0-9]{6,11}\b/', '[REDACTED_PHONE]', $text);

        // Mask passwords, tokens, API keys in string
        $text = preg_replace('/(password|secret|token|api_key|app_key)=([^\s&]+)/i', '$1=[REDACTED]', $text);

        return $text;
    }

    /**
     * Recursively sanitize context arrays to protect PII.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitizeContext(array $context): array
    {
        $sanitized = [];
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'nik', 'phone', 'wa', 'whatsapp', 'nik_hash', 'phone_hash', 'app_key', 'authorization'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
