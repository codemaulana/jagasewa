<?php

namespace Tests\Unit;

use App\Services\Billing\QrisEngineClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QrisEngineClientTest extends TestCase
{
    public function test_health_check_returns_true_when_service_is_healthy(): void
    {
        Http::fake([
            'http://127.0.0.1:8081/health' => Http::response([
                'status' => 'healthy',
                'service' => 'jagasewa-qris-engine',
            ], 200),
        ]);

        $client = new QrisEngineClient;
        $this->assertTrue($client->isHealthy());
    }

    public function test_health_check_returns_false_on_connection_error(): void
    {
        Http::fake([
            'http://127.0.0.1:8081/health' => Http::response(null, 500),
        ]);

        $client = new QrisEngineClient;
        $this->assertFalse($client->isHealthy());
    }

    public function test_create_dynamic_qris_returns_payload_on_success(): void
    {
        Http::fake([
            'http://127.0.0.1:8081/api/v1/qris/create' => Http::response([
                'success' => true,
                'data' => [
                    'reference_code' => 'JS-20261027-TEST',
                    'qris_content' => '00020101021226...',
                    'amount' => 150000,
                    'status' => 'pending',
                ],
            ], 201),
        ]);

        $client = new QrisEngineClient;
        $result = $client->createDynamicQris('JS-20261027-TEST', 150000, 'Budi', 1, 30);

        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('JS-20261027-TEST', $result['data']['reference_code']);
    }

    public function test_get_status_returns_data(): void
    {
        Http::fake([
            'http://127.0.0.1:8081/api/v1/qris/status/JS-20261027-TEST' => Http::response([
                'success' => true,
                'status' => 'settled',
                'amount' => 150000,
            ], 200),
        ]);

        $client = new QrisEngineClient;
        $status = $client->getStatus('JS-20261027-TEST');

        $this->assertNotNull($status);
        $this->assertEquals('settled', $status['status']);
    }
}
