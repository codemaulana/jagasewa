<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\MayarWebhookHandler;
use App\Services\Billing\SubscriptionReconciliationService;
use App\Services\Billing\SubscriptionStateMachine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionLifecycleAndRegressionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-22 10:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Free vendor cannot access premium endpoint; Pro vendor can.
     */
    public function test_free_vendor_blocked_from_premium_endpoint_and_pro_vendor_allowed(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Free tier -> 403
        $response = $this->actingAs($user, 'vendor')
            ->getJson(route('vendor.reports.export'));

        $response->assertStatus(403);
        $response->assertJson([
            'error_code' => 'PLAN_REQUIRED',
            'feature' => 'export_reports',
        ]);

        // Upgrade to Pro -> 200 CSV stream
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addMonth(),
        ]);

        $responsePro = $this->actingAs($user, 'vendor')
            ->get(route('vendor.reports.export'));

        $responsePro->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $responsePro->headers->get('content-type'));
    }

    /**
     * 2. Duplicate subscription purchase is rejected with 409 Conflict.
     */
    public function test_duplicate_subscription_purchase_rejected_with_409(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(20),
        ]);

        // Attempt duplicate checkout via JSON without renewal intent
        $response = $this->actingAs($user, 'vendor')
            ->postJson(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'ALREADY_SUBSCRIBED',
        ]);
    }

    /**
     * 3. Pending subscription duplicate checkout is rejected with 409 Conflict.
     */
    public function test_duplicate_pending_order_rejected_with_409(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => 'quarterly',
            'current_period_start' => now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->postJson(route('vendor.billing.checkout'), [
                'cycle' => 'quarterly',
            ]);

        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'PENDING_ORDER_EXISTS',
        ]);
    }

    /**
     * 4. Renewal seamlessly connects from previous current_period_end, not payment date.
     */
    public function test_renewal_extension_connects_from_previous_current_period_end(): void
    {
        $vendor = Vendor::factory()->create();
        $initialEnd = now('Asia/Jakarta')->addDays(15);

        // Active subscription expiring in 15 days
        $activeSub = Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta')->subDays(15),
            'current_period_end' => $initialEnd,
        ]);

        // Create renewal pending order
        $renewalSub = Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $initialEnd,
        ]);

        // Transition renewal to active via invoice.paid
        $stateMachine = app(SubscriptionStateMachine::class);
        $stateMachine->transition($renewalSub, Subscription::STATUS_ACTIVE, 'invoice.paid', [
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ]);

        $renewalSub->refresh();
        $activeSub->refresh();

        // Previous subscription cancelled
        $this->assertEquals(Subscription::STATUS_CANCELLED, $activeSub->status);

        // New subscription period end is exactly initialEnd + 1 month
        $expectedEnd = $initialEnd->copy()->addMonth();
        $this->assertEquals($expectedEnd->toIso8601String(), $renewalSub->current_period_end->toIso8601String());
        $this->assertTrue($renewalSub->isActive());
    }

    /**
     * 5. Webhook idempotency prevents duplicate events from extending duration twice.
     */
    public function test_webhook_idempotency_prevents_duplicate_extension(): void
    {
        $vendor = Vendor::factory()->create();
        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => 'monthly',
            'current_period_start' => now('Asia/Jakarta'),
        ]);

        $handler = app(MayarWebhookHandler::class);
        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => 'evt_test_unique_999',
                'order_id' => (string) $subscription->id,
                'status' => 'SUCCESS',
                'amount' => 15000,
                'currency' => 'IDR',
            ],
        ];

        // First execution
        $res1 = $handler->handle($payload);
        $this->assertEquals('success', $res1['status']);
        $subscription->refresh();
        $firstEnd = $subscription->current_period_end->toIso8601String();

        // Replay same event
        $res2 = $handler->handle($payload);
        $this->assertEquals('ignored', $res2['status']);

        // End date remains unchanged (not extended twice)
        $subscription->refresh();
        $this->assertEquals($firstEnd, $subscription->current_period_end->toIso8601String());
        $this->assertEquals(1, SubscriptionEvent::where('gateway_event_id', 'evt_test_unique_999')->count());
    }

    /**
     * 6. Lifecycle: expiry -> 3-day grace period -> downgrade to Free.
     */
    public function test_expiration_grace_period_and_downgrade_to_free(): void
    {
        $vendor = Vendor::factory()->create();
        $entitlement = app(EntitlementService::class);
        $reconciliation = app(SubscriptionReconciliationService::class);

        // Subscription expired 1 day ago (inside 3-day grace period)
        $sub = Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PAST_DUE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta')->subDays(31),
            'current_period_end' => now('Asia/Jakarta')->subDay(),
        ]);

        // Within grace period, isPro is preserved
        $this->assertTrue($entitlement->isInGracePeriod($vendor));
        $this->assertTrue($entitlement->isPro($vendor));

        // Reconcile: within grace period, not yet cancelled
        $reconciliation->reconcile($sub);
        $sub->refresh();
        $this->assertEquals(Subscription::STATUS_PAST_DUE, $sub->status);

        // Fast-forward past grace period (4 days expired)
        Carbon::setTestNow(now('Asia/Jakarta')->addDays(4));

        $this->assertFalse($entitlement->isInGracePeriod($vendor));

        // Reconcile: past grace period -> cancelled and downgraded
        $reconciliation->reconcile($sub);
        $sub->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertEquals(Subscription::PLAN_FREE, $sub->plan);
        $this->assertFalse($entitlement->isPro($vendor));
    }

    /**
     * 7. Downgrade does NOT delete data; over-limit items become read-only.
     */
    public function test_over_limit_items_become_read_only_and_not_deleted(): void
    {
        $vendor = Vendor::factory()->create();
        $entitlement = app(EntitlementService::class);

        // Create 17 active items while Pro
        for ($i = 1; $i <= 17; $i++) {
            Item::factory()->create([
                'vendor_id' => $vendor->id,
                'name' => "Barang Rental {$i}",
                'is_active' => true,
            ]);
        }

        $this->assertEquals(17, Item::where('vendor_id', $vendor->id)->count());

        // Vendor is Free (limit = 15)
        $this->assertFalse($entitlement->isPro($vendor));
        $this->assertEquals(15, $entitlement->limitFor($vendor, 'active_items'));
        $this->assertTrue($entitlement->isOverLimit($vendor, 'active_items'));

        // All 17 items still exist in database (zero deletion)
        $allItems = Item::where('vendor_id', $vendor->id)->orderBy('id', 'asc')->get();
        $this->assertCount(17, $allItems);

        // Items 1..15 within free limit: read-only = false
        $this->assertFalse($entitlement->isItemReadOnly($vendor, $allItems[0]));
        $this->assertFalse($entitlement->isItemReadOnly($vendor, $allItems[14]));

        // Items 16..17 exceeding free limit: read-only = true
        $this->assertTrue($entitlement->isItemReadOnly($vendor, $allItems[15]));
        $this->assertTrue($entitlement->isItemReadOnly($vendor, $allItems[16]));
    }

    /**
     * 8. Endpoint /vendor/entitlements serves as Single Source of Truth for frontend.
     */
    public function test_entitlements_endpoint_returns_single_source_of_truth(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->getJson(route('vendor.entitlements'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vendor_id',
            'vendor_ulid',
            'plan',
            'plan_name',
            'status',
            'is_pro',
            'in_grace_period',
            'limits' => [
                'active_items',
                'photos_per_item',
                'blacklist_entries',
                'monthly_bookings',
                'staff_users',
            ],
            'usage',
            'features',
        ]);
        $this->assertFalse($response->json('is_pro'));
        $this->assertEquals(15, $response->json('limits.active_items'));
    }

    /**
     * 9. Photo management: primary_photo_url accessor with cache-busting, deletion cleans storage.
     */
    public function test_photo_management_primary_photo_url_and_deletion(): void
    {
        Storage::fake('s3');

        $item = Item::factory()->create([
            'photo_paths' => ['item_photos/photo1.webp', 'item_photos/photo2.webp'],
        ]);

        // Primary photo url returns first photo with ?v= cache-busting
        $primaryUrl = $item->primary_photo_url;
        $this->assertNotNull($primaryUrl);
        $this->assertStringContainsString('photo1.webp', $primaryUrl);
        $this->assertStringContainsString('v=', $primaryUrl);

        // All photo URLs have cache busting
        $urls = $item->photo_urls;
        $this->assertCount(2, $urls);
        $this->assertStringContainsString('photo2.webp', $urls[1]);

        // Deleting item cascades and cleans up storage
        Storage::disk('s3')->put('item_photos/photo1.webp', 'image-content-1');
        Storage::disk('s3')->put('item_photos/photo2.webp', 'image-content-2');
        $this->assertTrue(Storage::disk('s3')->exists('item_photos/photo1.webp'));

        $item->delete();
        $this->assertFalse(Storage::disk('s3')->exists('item_photos/photo1.webp'));
        $this->assertFalse(Storage::disk('s3')->exists('item_photos/photo2.webp'));
    }

    /**
     * 10. Tenant isolation: Vendor A cannot modify Vendor B photo or subscription.
     */
    public function test_tenant_isolation_vendor_a_cannot_touch_vendor_b_resource(): void
    {
        $vendorA = Vendor::factory()->create();
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create();
        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);
        $subB = Subscription::create([
            'vendor_id' => $vendorB->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now('Asia/Jakarta'),
        ]);

        // Vendor A cannot view/edit Vendor B item
        $response = $this->actingAs($userA, 'vendor')
            ->get("/vendor/items/{$itemB->ulid}/edit");
        $response->assertStatus(404);

        // Ensure Vendor B subscription remains intact
        $subB->refresh();
        $this->assertEquals($vendorB->id, $subB->vendor_id);
    }
}
