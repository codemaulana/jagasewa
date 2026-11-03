<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Services\Billing\QrisEngineClient;
use App\Services\QrisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisEngineClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_always_returns_true(): void
    {
        $client = new QrisEngineClient;
        $this->assertTrue($client->isHealthy());
    }

    public function test_create_dynamic_qris_returns_valid_payload(): void
    {
        $client = new QrisEngineClient;
        $result = $client->createDynamicQris('JS-20261103-TEST', 150000, 'Budi', 1, 30);

        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('JS-20261103-TEST', $result['reference_code']);
        $this->assertEquals(150000, $result['amount']);
        $this->assertStringStartsWith('000201010212', $result['qris_content']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $result['qr_image_data_uri']);
    }

    public function test_get_status_reflects_booking_settlement(): void
    {
        $booking = Booking::factory()->create([
            'reference_code' => 'JS-20261103-STAT',
            'is_paid_in_full' => false,
        ]);

        $client = new QrisEngineClient;
        $statusPending = $client->getStatus('JS-20261103-STAT');
        $this->assertEquals('pending', $statusPending['status']);

        // Settle payment
        app(QrisService::class)->settlePayment('JS-20261103-STAT', 'test');

        $statusSettled = $client->getStatus('JS-20261103-STAT');
        $this->assertEquals('settled', $statusSettled['status']);
    }
}
