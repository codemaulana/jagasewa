<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MayarWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $webhookSecret = 'test-mayar-secret-key-abcdef123456';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.mayar.webhook_secret', $this->webhookSecret);
    }

    /**
     * Generate Mayar HMAC-SHA256 signature for raw body.
     */
    protected function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
    }

    /**
     * BLT-08: Signature webhook salah / token tidak cocok -> HTTP 401
     */
    public function test_blt_08_invalid_or_missing_mayar_signature_returns_401(): void
    {
        // 1. Missing header
        $response1 = $this->postJson(route('webhooks.mayar'), [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_123',
                'order_id' => 'sub_123',
                'amount' => 15000,
            ],
        ]);
        $response1->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized: Invalid Mayar webhook signature']);

        // 2. Wrong signature
        $response2 = $this->withHeaders([
            'x-mayar-signature' => 'invalid-hmac-signature-here',
        ])->postJson(route('webhooks.mayar'), [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_123',
                'order_id' => 'sub_123',
                'amount' => 15000,
            ],
        ]);
        $response2->assertStatus(401);
    }

    /**
     * BLT-02: Webhook payment.received valid dengan HMAC signature mengaktifkan Pro & mencatat event
     */
    public function test_blt_02_valid_mayar_payment_received_activates_pro_subscription(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $eventId = 'pay_mayar_00112233';
        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => $eventId,
                'order_id' => $subscription->ulid,
                'customer_id' => 'cus_mayar_999',
                'status' => 'SUCCESS',
                'amount' => 15000,
                'currency' => 'IDR',
                'payment_method' => 'qris',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->withHeaders([
            'x-mayar-signature' => $signature,
        ])->postJson(route('webhooks.mayar'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Mayar webhook processed successfully',
            ]);

        // Subscription activated to Pro
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertEquals(Subscription::PLAN_PRO, $subscription->plan);
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isPro());
        $this->assertNotNull($subscription->current_period_end);
        $this->assertTrue($subscription->current_period_end->isFuture());
        $this->assertEquals($eventId, $subscription->gateway_subscription_id);

        // Subscription event created
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'vendor_id' => $vendor->id,
            'gateway_event_id' => $eventId,
            'event_type' => 'invoice.paid',
            'amount_idr' => 15000,
            'status' => SubscriptionEvent::STATUS_PROCESSED,
        ]);
    }

    /**
     * Support token langsung via header x-mayar-token
     */
    public function test_mayar_webhook_accepts_direct_token_header(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_direct_token_123',
                'order_id' => $subscription->ulid,
                'status' => 'SUCCESS',
                'amount' => 15000,
                'currency' => 'IDR',
            ],
        ];

        $response = $this->withHeaders([
            'x-mayar-token' => $this->webhookSecret,
        ])->postJson(route('webhooks.mayar'), $payload);

        $response->assertStatus(200)->assertJson(['status' => 'success']);
    }

    /**
     * BLT-01 / TC-26: Webhook ganda (replay) -> HTTP 200 idempoten
     */
    public function test_blt_01_duplicate_mayar_webhook_replay_is_idempotent(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_replay_mayar_001',
                'order_id' => $subscription->ulid,
                'status' => 'SUCCESS',
                'amount' => 15000,
                'currency' => 'IDR',
            ],
        ];

        $signature = $this->generateSignature($payload);

        // 1st request
        $res1 = $this->withHeaders(['x-mayar-signature' => $signature])
            ->postJson(route('webhooks.mayar'), $payload);
        $res1->assertStatus(200)->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('subscription_events', 1);

        // 2nd request (duplicate event)
        $res2 = $this->withHeaders(['x-mayar-signature' => $signature])
            ->postJson(route('webhooks.mayar'), $payload);
        $res2->assertStatus(200)->assertJson(['status' => 'ignored']);

        $this->assertDatabaseCount('subscription_events', 1);
    }

    /**
     * BLT-04 / TC-27: Nominal kurang bayar ditolak
     */
    public function test_blt_04_mayar_underpaid_amount_is_rejected(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY, // Rp 15.000
        ]);

        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_underpaid_001',
                'order_id' => $subscription->ulid,
                'status' => 'SUCCESS',
                'amount' => 10000, // Underpaid
                'currency' => 'IDR',
            ],
        ];

        $response = $this->withHeaders(['x-mayar-signature' => $this->generateSignature($payload)])
            ->postJson(route('webhooks.mayar'), $payload);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_PENDING_PAYMENT, $subscription->status);
    }

    /**
     * BLT-07: Order expired menolak invoice.paid terlambat
     */
    public function test_blt_07_mayar_expired_order_rejects_late_payment(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->expired()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_EXPIRED,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_late_after_expired',
                'order_id' => $subscription->ulid,
                'status' => 'SUCCESS',
                'amount' => 15000,
                'currency' => 'IDR',
            ],
        ];

        $response = $this->withHeaders(['x-mayar-signature' => $this->generateSignature($payload)])
            ->postJson(route('webhooks.mayar'), $payload);

        $response->assertStatus(200)
            ->assertJson(['status' => 'rejected']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_EXPIRED, $subscription->status);
    }

    /**
     * Siklus 3 bulan (Quarterly) dan 1 tahun (Annually) pada Mayar webhook
     */
    public function test_mayar_quarterly_and_annually_cycles(): void
    {
        $vendor = Vendor::factory()->create();

        // 3 Bulan (Rp 39.000)
        $subQuarterly = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_QUARTERLY,
        ]);

        $payloadQ = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_mayar_q1',
                'order_id' => $subQuarterly->ulid,
                'status' => 'SUCCESS',
                'amount' => 39000,
                'currency' => 'IDR',
            ],
        ];

        $resQ = $this->withHeaders(['x-mayar-signature' => $this->generateSignature($payloadQ)])
            ->postJson(route('webhooks.mayar'), $payloadQ);
        $resQ->assertStatus(200);

        $subQuarterly->refresh();
        $this->assertTrue($subQuarterly->current_period_end->greaterThan(now()->addMonths(2)));

        // 1 Tahun (Rp 129.000)
        $subAnnually = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_ANNUALLY,
        ]);

        $payloadA = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'pay_mayar_a1',
                'order_id' => $subAnnually->ulid,
                'status' => 'SUCCESS',
                'amount' => 129000,
                'currency' => 'IDR',
            ],
        ];

        $resA = $this->withHeaders(['x-mayar-signature' => $this->generateSignature($payloadA)])
            ->postJson(route('webhooks.mayar'), $payloadA);
        $resA->assertStatus(200);

        $subAnnually->refresh();
        $this->assertTrue($subAnnually->current_period_end->greaterThan(now()->addMonths(11)));
    }
}
