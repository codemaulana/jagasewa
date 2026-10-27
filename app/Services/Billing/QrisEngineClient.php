<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class QrisEngineClient
{
    protected string $baseUrl;

    protected string $secret;

    protected bool $enabled;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.qris_engine.url', 'http://127.0.0.1:8081'), '/');
        $this->secret = (string) config('services.qris_engine.secret', '');
        $this->enabled = (bool) config('services.qris_engine.enabled', false);
    }

    /**
     * Check whether the Go QRIS microservice is reachable and healthy.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->baseUrl}/health");

            return $response->successful() && ($response->json('status') === 'healthy');
        } catch (Throwable $e) {
            Log::debug('QRIS Engine health check unreachable: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Request dynamic QRIS invoice creation from the Go microservice.
     *
     * @return array<string, mixed>|null
     */
    public function createDynamicQris(
        string $referenceCode,
        int $amount,
        string $renterName,
        int $vendorId,
        int $expiredMinutes = 30
    ): ?array {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->secret,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/v1/qris/create", [
                    'reference_code' => $referenceCode,
                    'amount' => $amount,
                    'renter_name' => $renterName,
                    'vendor_id' => $vendorId,
                    'expired_minutes' => $expiredMinutes,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('QRIS Engine returned error response: '.$response->body());

            return null;
        } catch (Throwable $e) {
            Log::error('Failed to connect to QRIS Engine: '.$e->getMessage(), [
                'reference_code' => $referenceCode,
            ]);

            return null;
        }
    }

    /**
     * Fetch payment status for a specific booking reference code.
     *
     * @return array<string, mixed>|null
     */
    public function getStatus(string $referenceCode): ?array
    {
        try {
            $response = Http::timeout(3)
                ->get("{$this->baseUrl}/api/v1/qris/status/{$referenceCode}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Throwable $e) {
            Log::debug('Failed to get QRIS status: '.$e->getMessage());

            return null;
        }
    }
}
