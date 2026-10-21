<?php

namespace Tests\Feature;

use App\Models\ManualActivation;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingMigrationAndModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_subscription_with_factory(): void
    {
        $vendor = Vendor::factory()->create();

        $subscription = Subscription::factory()->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'vendor_id' => $vendor->id,
            'plan' => 'free',
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $this->assertNotNull($subscription->ulid);
        $this->assertEquals(26, strlen($subscription->ulid));
        $this->assertTrue($subscription->vendor->is($vendor));
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isFree());
        $this->assertFalse($subscription->isPro());
    }

    public function test_can_create_pro_subscription_with_factory(): void
    {
        $subscription = Subscription::factory()->pro(Subscription::CYCLE_QUARTERLY)->create();

        $this->assertEquals('pro', $subscription->plan);
        $this->assertEquals('quarterly', $subscription->billing_cycle);
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->current_period_end);
        $this->assertTrue($subscription->current_period_end->isFuture());
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isPro());
        $this->assertFalse($subscription->isFree());
    }

    public function test_subscription_past_due_and_expired_states(): void
    {
        $pastDue = Subscription::factory()->pastDue()->create();
        $this->assertEquals('past_due', $pastDue->status);
        $this->assertFalse($pastDue->isActive());
        $this->assertFalse($pastDue->isPro());

        $expired = Subscription::factory()->expired()->create();
        $this->assertEquals('expired', $expired->status);
        $this->assertFalse($expired->isActive());
        $this->assertFalse($expired->isPro());
    }

    public function test_can_create_subscription_event_with_factory(): void
    {
        $subscription = Subscription::factory()->pro()->create();
        $event = SubscriptionEvent::factory()->create([
            'subscription_id' => $subscription->id,
            'vendor_id' => $subscription->vendor_id,
            'amount_idr' => 15000,
            'event_type' => 'invoice.paid',
        ]);

        $this->assertDatabaseHas('subscription_events', [
            'id' => $event->id,
            'subscription_id' => $subscription->id,
            'vendor_id' => $subscription->vendor_id,
            'event_type' => 'invoice.paid',
            'amount_idr' => 15000,
        ]);

        $this->assertNotNull($event->ulid);
        $this->assertEquals(26, strlen($event->ulid));
        $this->assertIsInt($event->amount_idr);
        $this->assertEquals(15000, $event->amount_idr);
        $this->assertInstanceOf(Carbon::class, $event->processed_at);
        $this->assertTrue($event->subscription->is($subscription));
        $this->assertTrue($event->vendor->is($subscription->vendor));
    }

    public function test_subscription_events_gateway_event_id_unique_constraint(): void
    {
        $subscription = Subscription::factory()->pro()->create();
        $gatewayEventId = 'evt_xendit_1234567890';

        // First event insertion succeeds
        SubscriptionEvent::factory()->create([
            'subscription_id' => $subscription->id,
            'vendor_id' => $subscription->vendor_id,
            'gateway_event_id' => $gatewayEventId,
        ]);

        // Second event with duplicate gateway_event_id must be rejected by UNIQUE DB constraint
        $this->expectException(QueryException::class);
        SubscriptionEvent::factory()->create([
            'subscription_id' => $subscription->id,
            'vendor_id' => $subscription->vendor_id,
            'gateway_event_id' => $gatewayEventId,
        ]);
    }

    public function test_can_create_manual_activation_with_factory(): void
    {
        $admin = User::factory()->create();
        $subscription = Subscription::factory()->pro()->create();

        $manual = ManualActivation::factory()->create([
            'subscription_id' => $subscription->id,
            'vendor_id' => $subscription->vendor_id,
            'activated_by' => $admin->id,
            'amount_paid_idr' => 39000,
            'notes' => 'Pembayaran paket 3 bulan via transfer Mandiri',
        ]);

        $this->assertDatabaseHas('manual_activations', [
            'id' => $manual->id,
            'subscription_id' => $subscription->id,
            'vendor_id' => $subscription->vendor_id,
            'activated_by' => $admin->id,
            'amount_paid_idr' => 39000,
        ]);

        $this->assertNotNull($manual->ulid);
        $this->assertEquals(26, strlen($manual->ulid));
        $this->assertIsInt($manual->amount_paid_idr);
        $this->assertEquals(39000, $manual->amount_paid_idr);
        $this->assertInstanceOf(Carbon::class, $manual->activated_at);
        $this->assertTrue($manual->subscription->is($subscription));
        $this->assertTrue($manual->vendor->is($subscription->vendor));
        $this->assertTrue($manual->admin->is($admin));
    }

    public function test_vendor_and_subscription_relationships(): void
    {
        $vendor = Vendor::factory()->create();
        $sub1 = Subscription::factory()->create(['vendor_id' => $vendor->id, 'created_at' => now()->subMonth()]);
        $sub2 = Subscription::factory()->pro()->create(['vendor_id' => $vendor->id, 'created_at' => now()]);

        $event = SubscriptionEvent::factory()->create([
            'subscription_id' => $sub2->id,
            'vendor_id' => $vendor->id,
        ]);

        $admin = User::factory()->create();
        $manual = ManualActivation::factory()->create([
            'subscription_id' => $sub2->id,
            'vendor_id' => $vendor->id,
            'activated_by' => $admin->id,
        ]);

        // Vendor relationships
        $this->assertCount(2, $vendor->subscriptions);
        $this->assertTrue($vendor->currentSubscription->is($sub2));
        $this->assertTrue($vendor->subscriptionEvents->contains($event));
        $this->assertTrue($vendor->manualActivations->contains($manual));

        // Subscription relationships
        $this->assertTrue($sub2->events->contains($event));
        $this->assertTrue($sub2->manualActivations->contains($manual));
    }

    public function test_billing_migration_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('subscriptions'));
        $this->assertTrue(Schema::hasTable('subscription_events'));
        $this->assertTrue(Schema::hasTable('manual_activations'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_19_000010_create_billing_tables.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('manual_activations'));
        $this->assertFalse(Schema::hasTable('subscription_events'));
        $this->assertFalse(Schema::hasTable('subscriptions'));

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_19_000010_create_billing_tables.php',
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('subscriptions'));
        $this->assertTrue(Schema::hasTable('subscription_events'));
        $this->assertTrue(Schema::hasTable('manual_activations'));
    }
}
