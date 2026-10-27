<?php

namespace Tests\Feature;

use App\Jobs\SubscriptionReconciliationJob;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Services\Billing\SubscriptionReconciliationService;
use App\Services\ErrorMonitoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.mayar.api_key', 'test_mayar_key_reconcile');
        Config::set('services.mayar.api_url', 'https://api.mayar.id/hl/v1');
        Config::set('services.xendit.secret_key', 'test_xendit_key_reconcile');
    }

    /**
     * Pending subscription synced to active when gateway confirms paid
     */
    public function test_reconciliation_syncs_pending_to_active_when_gateway_paid(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'gateway_subscription_id' => 'mayar_pay_rec_001',
        ]);

        Http::fake([
            'https://api.mayar.id/hl/v1/invoice/mayar_pay_rec_001' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'id' => 'mayar_pay_rec_001',
                    'status' => 'paid',
                    'amount' => 15000,
                ],
            ], 200),
        ]);

        $service = app(SubscriptionReconciliationService::class);
        $result = $service->reconcile($subscription);

        $this->assertEquals('synced', $result['status']);
        $this->assertEquals('activated', $result['action']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertEquals(Subscription::PLAN_PRO, $subscription->plan);
        $this->assertNotNull($subscription->current_period_end);

        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => 'reconciliation.paid',
            'status' => 'processed',
        ]);
    }

    /**
     * Xendit pending subscription synced to active when gateway confirms PAID
     */
    public function test_reconciliation_syncs_xendit_pending_to_active(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_QUARTERLY,
            'gateway_subscription_id' => 'inv_xendit_rec_999',
        ]);

        Http::fake([
            'https://api.xendit.co/v2/invoices/inv_xendit_rec_999' => Http::response([
                'id' => 'inv_xendit_rec_999',
                'status' => 'PAID',
                'amount' => 39000,
            ], 200),
        ]);

        $service = app(SubscriptionReconciliationService::class);
        $result = $service->reconcile($subscription);

        $this->assertEquals('synced', $result['status']);
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
    }

    /**
     * Pending subscription synced to expired when gateway confirms expired
     */
    public function test_reconciliation_syncs_pending_to_expired_when_gateway_expired(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'gateway_subscription_id' => 'mayar_pay_expired_001',
        ]);

        Http::fake([
            'https://api.mayar.id/hl/v1/invoice/mayar_pay_expired_001' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'id' => 'mayar_pay_expired_001',
                    'status' => 'expired',
                ],
            ], 200),
        ]);

        $service = app(SubscriptionReconciliationService::class);
        $result = $service->reconcile($subscription);

        $this->assertEquals('synced', $result['status']);
        $this->assertEquals('expired', $result['action']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_EXPIRED, $subscription->status);
    }

    /**
     * Alert dispatched when order pending > 30 minutes without webhook
     */
    public function test_reconciliation_alerts_when_pending_order_stuck_longer_than_30_minutes(): void
    {
        $monitoringMock = $this->createMock(ErrorMonitoringService::class);
        $monitoringMock->expects($this->once())
            ->method('captureMessage')
            ->with($this->stringContains('menggantung > 30 menit'));

        $this->app->instance(ErrorMonitoringService::class, $monitoringMock);

        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'gateway_subscription_id' => 'mayar_pending_stuck',
            'created_at' => Carbon::now('Asia/Jakarta')->subMinutes(45),
        ]);

        Http::fake([
            'https://api.mayar.id/hl/v1/invoice/mayar_pending_stuck' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'id' => 'mayar_pending_stuck',
                    'status' => 'unpaid',
                ],
            ], 200),
        ]);

        $service = app(SubscriptionReconciliationService::class);
        $result = $service->reconcile($subscription);

        $this->assertEquals('unchanged', $result['status']);
        $this->assertEquals('pending', $result['action']);
    }

    /**
     * Active subscription past current_period_end transitions to past_due
     */
    public function test_reconciliation_moves_active_past_period_end_to_past_due(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => Carbon::now('Asia/Jakarta')->subHours(2),
            'gateway_subscription_id' => 'mayar_unrenewed',
        ]);

        Http::fake([
            'https://api.mayar.id/hl/v1/invoice/mayar_unrenewed' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'status' => 'unpaid',
                ],
            ], 200),
        ]);

        $service = app(SubscriptionReconciliationService::class);
        $result = $service->reconcile($subscription);

        $this->assertEquals('synced', $result['status']);
        $this->assertEquals('past_due', $result['action']);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_PAST_DUE, $subscription->status);
    }

    /**
     * Past due subscription past 7 days grace period is downgraded to free
     */
    public function test_reconciliation_downgrades_past_due_after_grace_period_to_free(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PAST_DUE,
            'current_period_end' => Carbon::now('Asia/Jakarta')->subDays(8),
        ]);

        $service = app(SubscriptionReconciliationService::class);
        $result = $service->reconcile($subscription);

        $this->assertEquals('synced', $result['status']);
        $this->assertEquals('downgraded', $result['action']);

        $subscription->refresh();
        $this->assertEquals(Subscription::PLAN_FREE, $subscription->plan);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNull($subscription->current_period_end);
    }

    /**
     * Artisan command billing:reconcile runs successfully
     */
    public function test_artisan_command_reconcile_runs_successfully(): void
    {
        $vendor = Vendor::factory()->create();
        $sub = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'gateway_subscription_id' => 'mayar_cmd_test',
        ]);

        Http::fake([
            'https://api.mayar.id/hl/v1/invoice/mayar_cmd_test' => Http::response([
                'statusCode' => 200,
                'data' => ['status' => 'paid'],
            ], 200),
        ]);

        $this->artisan('billing:reconcile')
            ->assertExitCode(0);

        $sub->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->status);
    }

    /**
     * SubscriptionReconciliationJob executes cleanly via queue worker
     */
    public function test_subscription_reconciliation_job_executes_cleanly(): void
    {
        $vendor = Vendor::factory()->create();
        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'gateway_subscription_id' => 'mayar_job_test',
        ]);

        Http::fake([
            'https://api.mayar.id/hl/v1/invoice/mayar_job_test' => Http::response([
                'statusCode' => 200,
                'data' => ['status' => 'unpaid'],
            ], 200),
        ]);

        $stats = app(SubscriptionReconciliationJob::class)->handle(app(SubscriptionReconciliationService::class));

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('synced', $stats);
        $this->assertEquals(1, $stats['total']);
    }
}
