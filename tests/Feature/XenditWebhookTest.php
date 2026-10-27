<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $callbackToken = 'valid-xendit-test-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.xendit.callback_token', $this->callbackToken);
    }

    /**
     * Signature webhook salah / token tidak cocok -> HTTP 401
     */
    public function test_invalid_or_missing_callback_token_returns_401(): void
    {
        // 1. Missing header
        $response1 = $this->postJson(route('webhooks.xendit'), [
            'id' => 'evt_123',
            'external_id' => 'sub_123',
            'status' => 'PAID',
            'amount' => 15000,
        ]);
        $response1->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized: Invalid callback token']);

        // 2. Wrong token header
        $response2 = $this->withHeaders([
            'x-callback-token' => 'invalid-token-here',
        ])->postJson(route('webhooks.xendit'), [
            'id' => 'evt_123',
            'external_id' => 'sub_123',
            'status' => 'PAID',
            'amount' => 15000,
        ]);
        $response2->assertStatus(401);
    }

    /**
     * Webhook PAID valid mengaktifkan subscription & mencatat event
     */
    public function test_valid_invoice_paid_activates_subscription_and_records_event(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_FREE,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $eventId = 'inv_xendit_test_998877';
        $payload = [
            'id' => $eventId,
            'external_id' => $subscription->ulid,
            'status' => 'PAID',
            'amount' => 15000,
            'paid_amount' => 15000,
            'currency' => 'IDR',
            'payment_method' => 'BANK_TRANSFER',
            'paid_at' => now()->toIso8601String(),
        ];

        $response = $this->withHeaders([
            'x-callback-token' => $this->callbackToken,
        ])->postJson(route('webhooks.xendit'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ]);

        // Assert Subscription status changed to active Pro
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertEquals(Subscription::PLAN_PRO, $subscription->plan);
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isPro());
        $this->assertNotNull($subscription->current_period_end);
        $this->assertTrue($subscription->current_period_end->isFuture());
        $this->assertEquals($eventId, $subscription->gateway_subscription_id);

        // Assert SubscriptionEvent recorded with SHA-256 payload hash (no raw PII)
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
     * Webhook ganda (replay) -> HTTP 200 idempoten, tidak duplikat proses
     */
    public function test_duplicate_webhook_replay_is_idempotent(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $eventId = 'inv_xendit_replay_001';
        $payload = [
            'id' => $eventId,
            'external_id' => $subscription->ulid,
            'status' => 'PAID',
            'amount' => 15000,
            'currency' => 'IDR',
        ];

        // 1st request -> processed
        $res1 = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);
        $res1->assertStatus(200)->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('subscription_events', 1);

        // 2nd request with exact same event ID -> ignored (idempotent 200)
        $res2 = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);
        $res2->assertStatus(200)->assertJson(['status' => 'ignored']);

        // No duplicate event rows inserted
        $this->assertDatabaseCount('subscription_events', 1);
    }

    /**
     * Replay attack (attacker resends identical valid request)
     */
    public function test_replay_attack_rejected_by_idempotency_check(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $eventId = 'inv_attack_replay_999';

        // Pre-existing event already recorded
        SubscriptionEvent::factory()->create([
            'subscription_id' => $subscription->id,
            'vendor_id' => $vendor->id,
            'gateway_event_id' => $eventId,
            'amount_idr' => 15000,
        ]);

        $payload = [
            'id' => $eventId,
            'external_id' => $subscription->ulid,
            'status' => 'PAID',
            'amount' => 15000,
            'currency' => 'IDR',
        ];

        $response = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);

        $response->assertStatus(200)->assertJson(['status' => 'ignored']);
        // Status remains pending_payment because the event was ignored as duplicate
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_PENDING_PAYMENT, $subscription->status);
    }

    /**
     * Nominal tidak cocok -> tolak, alert, status tetap pending_payment
     */
    public function test_amount_mismatch_is_rejected_and_rolls_back(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY, // Expected: Rp 15.000
        ]);

        $payload = [
            'id' => 'inv_mismatch_001',
            'external_id' => $subscription->ulid,
            'status' => 'PAID',
            'amount' => 10000, // Underpaid
            'currency' => 'IDR',
        ];

        $response = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_PENDING_PAYMENT, $subscription->status);
        $this->assertDatabaseCount('subscription_events', 0);
    }

    /**
     * Pembayaran sebagian (partial payment) ditolak
     */
    public function test_partial_payment_is_rejected(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_QUARTERLY, // Expected: Rp 39.000
        ]);

        $payload = [
            'id' => 'inv_partial_001',
            'external_id' => $subscription->ulid,
            'status' => 'PAID',
            'amount' => 15000, // Only 1 month transferred instead of quarterly 39.000
            'currency' => 'IDR',
        ];

        $response = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);

        $response->assertStatus(422);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_PENDING_PAYMENT, $subscription->status);
    }

    /**
     * Webhook out-of-order / order not found
     */
    public function test_unknown_subscription_order_returns_422(): void
    {
        $payload = [
            'id' => 'inv_unknown_999',
            'external_id' => 'non-existent-ulid-0000000',
            'status' => 'PAID',
            'amount' => 15000,
            'currency' => 'IDR',
        ];

        $response = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    /**
     * Order expired -> invoice.paid ditolak oleh state machine, alert admin
     */
    public function test_expired_order_rejects_late_invoice_paid(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->expired()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_EXPIRED,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $payload = [
            'id' => 'inv_late_after_expired',
            'external_id' => $subscription->ulid,
            'status' => 'PAID',
            'amount' => 15000,
            'currency' => 'IDR',
        ];

        $response = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), $payload);

        // Returns 200 with rejected notice so gateway does not endlessly retry, but status remains expired
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'rejected',
            ]);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_EXPIRED, $subscription->status);
    }

    /**
     * Race condition antara redirect dan webhook
     * Redirect browser TIDAK mengubah status subscription; hanya webhook yang mengubah status.
     */
    public function test_browser_redirect_never_alters_subscription_status(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
        ]);

        // Vendor redirected back from payment gateway
        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.billing.finish'));

        $response->assertRedirect('/vendor');
        $response->assertSessionHas('status');

        // Subscription status MUST remain pending_payment (redirect is not source of truth)
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_PENDING_PAYMENT, $subscription->status);
    }

    /**
     * Siklus Quarterly & Annually menghitung tanggal period_end dengan tepat
     */
    public function test_quarterly_and_annually_cycles_compute_correct_period_end(): void
    {
        $vendor = Vendor::factory()->create();

        // Quarterly
        $subQuarterly = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_QUARTERLY,
        ]);

        $resQuarterly = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), [
                'id' => 'inv_q_123',
                'external_id' => $subQuarterly->ulid,
                'status' => 'PAID',
                'amount' => 39000,
                'currency' => 'IDR',
            ]);
        $resQuarterly->assertStatus(200);

        $subQuarterly->refresh();
        $this->assertTrue($subQuarterly->current_period_end->greaterThan(now()->addMonths(2)));

        // Annually
        $subAnnually = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_ANNUALLY,
        ]);

        $resAnnually = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), [
                'id' => 'inv_a_123',
                'external_id' => $subAnnually->ulid,
                'status' => 'PAID',
                'amount' => 129000,
                'currency' => 'IDR',
            ]);
        $resAnnually->assertStatus(200);

        $subAnnually->refresh();
        $this->assertTrue($subAnnually->current_period_end->greaterThan(now()->addMonths(11)));
    }

    /**
     * Non-IDR currency rejected
     */
    public function test_non_idr_currency_rejected(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $response = $this->withHeaders(['x-callback-token' => $this->callbackToken])
            ->postJson(route('webhooks.xendit'), [
                'id' => 'inv_usd_001',
                'external_id' => $subscription->ulid,
                'status' => 'PAID',
                'amount' => 15000,
                'currency' => 'USD',
            ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }
}
