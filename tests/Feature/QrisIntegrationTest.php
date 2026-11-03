<?php

namespace Tests\Feature;

use App\Livewire\Public\QrisPaymentCard;
use App\Models\Booking;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrisIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_qris_webhook_settles_booking(): void
    {
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'reference_code' => 'JS-20261103-WEBHOOK',
            'is_paid_in_full' => false,
        ]);

        $response = $this->postJson(route('api.webhooks.qris'), [
            'reference_code' => 'JS-20261103-WEBHOOK',
            'provider' => 'qris_bca',
            'transaction_id' => 'TRX-987654',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $booking->refresh();
        $this->assertTrue($booking->is_paid_in_full);
    }

    public function test_qris_simulate_endpoint_settles_payment(): void
    {
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'reference_code' => 'JS-20261103-SIM',
            'is_paid_in_full' => false,
        ]);

        $response = $this->postJson(route('api.qris.simulate', ['referenceCode' => 'JS-20261103-SIM']));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $booking->refresh();
        $this->assertTrue($booking->is_paid_in_full);
    }

    public function test_qris_webhook_missing_reference_code_returns_422(): void
    {
        $response = $this->postJson(route('api.webhooks.qris'), []);

        $response->assertStatus(422);
    }

    public function test_qris_payment_card_livewire_component_renders_and_polls(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Kamera Jogja']);
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'reference_code' => 'JS-20261103-CARD',
            'total_price' => 200000,
            'deposit_paid' => 100000,
            'is_paid_in_full' => false,
        ]);

        // 1. Initial pending state
        $component = Livewire::test(QrisPaymentCard::class, ['booking' => $booking])
            ->assertSee('QRIS')
            ->assertSee('300.000') // 200k + 100k
            ->assertSee('Menunggu pembayaran masuk');

        // 2. Simulate payment via component action
        $component->call('simulatePayment')
            ->assertSee('LUNAS VIA QRIS')
            ->assertSee('Pembayaran Telah Diterima');

        $booking->refresh();
        $this->assertTrue($booking->is_paid_in_full);
    }
}
