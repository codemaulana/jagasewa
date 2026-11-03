<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Services\QrisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QrisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrisService;
    }

    public function test_generate_dynamic_qris_structure_and_emvco(): void
    {
        $result = $this->service->generateDynamicQris(
            amount: 250000,
            referenceCode: 'JS-20261103-TEST',
            merchantName: 'Tokyo Cosplay Rental',
            city: 'Jakarta Selatan'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(250000, $result['amount']);
        $this->assertEquals('JS-20261103-TEST', $result['reference_code']);

        $content = $result['qris_content'];
        // Format indicator 000201 and dynamic point of initiation 010212
        $this->assertStringStartsWith('000201010212', $content);
        // Currency 360 (IDR)
        $this->assertStringContainsString('5303360', $content);
        // Amount 250000 (Tag 54 with length 06)
        $this->assertStringContainsString('5406250000', $content);
        // Country ID
        $this->assertStringContainsString('5802ID', $content);
        // Tag 63 (CRC16)
        $this->assertStringContainsString('6304', $content);

        // QR Image SVG data URI
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $result['qr_image_data_uri']);
    }

    public function test_calculate_crc16_is_deterministic(): void
    {
        $payload = '000201010212530336054061500005802ID5916JAGASEWA RENTAL6007JAKARTA6304';
        $crc = $this->service->calculateCrc16($payload);

        $this->assertEquals('9B28', $crc);
    }

    public function test_settle_payment_updates_booking_successfully(): void
    {
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'reference_code' => 'JS-20261103-BOOK',
            'is_paid_in_full' => false,
            'total_price' => 100000,
            'deposit_paid' => 50000,
        ]);

        $result = $this->service->settlePayment('JS-20261103-BOOK', 'simulator', 'TRX-12345');

        $this->assertTrue($result['success']);
        $this->assertEquals('booking', $result['type']);
        $this->assertEquals('settled', $result['status']);

        $booking->refresh();
        $this->assertTrue($booking->is_paid_in_full);
        $this->assertNotNull($booking->paid_in_full_at);
        $this->assertStringContainsString('TRX-12345', $booking->payment_notes);
    }

    public function test_settle_payment_is_idempotent(): void
    {
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'reference_code' => 'JS-20261103-IDEM',
            'is_paid_in_full' => false,
        ]);

        // First settlement
        $this->service->settlePayment('JS-20261103-IDEM', 'bank_bca');
        $booking->refresh();
        $firstPaidAt = $booking->paid_in_full_at;

        // Replay second settlement
        $result = $this->service->settlePayment('JS-20261103-IDEM', 'bank_bca');
        $this->assertTrue($result['success']);

        $booking->refresh();
        $this->assertEquals($firstPaidAt->toIso8601String(), $booking->paid_in_full_at->toIso8601String());
    }

    public function test_settle_payment_updates_subscription(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'gateway_subscription_id' => 'SUB-20261103-999',
            'plan' => 'pro',
            'billing_cycle' => 'monthly',
            'status' => Subscription::STATUS_PENDING_PAYMENT,
        ]);

        $result = $this->service->settlePayment('SUB-20261103-999', 'gopay');

        $this->assertTrue($result['success']);
        $this->assertEquals('subscription', $result['type']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
    }

    public function test_settle_payment_unknown_code_returns_failure(): void
    {
        $result = $this->service->settlePayment('UNKNOWN-CODE-999');

        $this->assertFalse($result['success']);
    }
}
