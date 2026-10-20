<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\Billing\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDoubleSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T-45 / BUG-01: Pro vendor cannot initiate another subscription checkout (prevents double billing).
     */
    public function test_pro_vendor_cannot_checkout_again(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Active Pro subscription until next month
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addDays(30),
        ]);

        $this->assertTrue(app(EntitlementService::class)->isPro($vendor));

        // Attempting another checkout must be rejected
        $response = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertRedirect(route('vendor.billing.index'));
        $response->assertSessionHas('error');

        // Verify exactly one subscription remains in database
        $this->assertEquals(1, Subscription::where('vendor_id', $vendor->id)->count());
    }

    /**
     * T-45 / BUG-01: Rapid duplicate checkout submissions (double click / race condition) do not create duplicate pending subscriptions.
     */
    public function test_rapid_double_checkout_does_not_create_duplicate_pending_subscriptions(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // First click
        $response1 = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response1->assertRedirect();

        // Second click within 15 seconds (same cycle)
        $response2 = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response2->assertRedirect();

        // Exactly one pending subscription created
        $this->assertEquals(1, Subscription::where('vendor_id', $vendor->id)->count());
        $this->assertEquals(
            1,
            Subscription::where('vendor_id', $vendor->id)->where('status', Subscription::STATUS_PENDING_PAYMENT)->count()
        );
    }

    /**
     * T-45 / BUG-01: Vendor can cancel an existing pending subscription and pick another plan cleanly.
     */
    public function test_vendor_can_cancel_pending_subscription_and_choose_another_plan(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Create initial pending monthly order
        $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), ['cycle' => 'monthly']);

        $pending = Subscription::where('vendor_id', $vendor->id)->where('status', Subscription::STATUS_PENDING_PAYMENT)->first();
        $this->assertNotNull($pending);

        // Cancel pending order
        $cancelResponse = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.cancel-pending'));

        $cancelResponse->assertRedirect(route('vendor.billing.index'));
        $cancelResponse->assertSessionHas('status');

        $pending->refresh();
        $this->assertEquals(Subscription::STATUS_CANCELLED, $pending->status);

        // Vendor can now checkout annual plan without conflicts
        $newOrderResponse = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), ['cycle' => 'annually']);

        $newOrderResponse->assertRedirect();

        $this->assertEquals(
            1,
            Subscription::where('vendor_id', $vendor->id)->where('status', Subscription::STATUS_PENDING_PAYMENT)->count()
        );
        $latest = Subscription::where('vendor_id', $vendor->id)->where('status', Subscription::STATUS_PENDING_PAYMENT)->first();
        $this->assertEquals(Subscription::CYCLE_ANNUALLY, $latest->billing_cycle);
    }

    /**
     * T-45 / BUG-01: Active Pro entitlement is never lost even if historical cancelled or pending subscriptions exist.
     */
    public function test_active_pro_entitlement_persists_regardless_of_subscription_history(): void
    {
        $vendor = Vendor::factory()->create();

        // Historical cancelled subscription
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_CANCELLED,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now('Asia/Jakarta')->subMonths(2),
            'current_period_end' => now('Asia/Jakarta')->subMonth(),
        ]);

        // Currently active Pro subscription
        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_ANNUALLY,
            'current_period_start' => now('Asia/Jakarta'),
            'current_period_end' => now('Asia/Jakarta')->addYear(),
        ]);

        $this->assertTrue(app(EntitlementService::class)->isPro($vendor));
    }

    /**
     * T-45 / UI: Billing page displays active Pro status banner and disables checkout buttons when active.
     */
    public function test_billing_index_renders_active_pro_banner_and_disables_duplicate_checkout(): void
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

        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Status: Paket Pro Aktif');
        $response->assertSee('✓ Paket Aktif Saat Ini');
        $response->assertDontSee('Pilih Bulanan');
    }

    /**
     * T-45 / UI: Billing page renders pending payment notice and cancellation button when pending order exists.
     */
    public function test_billing_index_renders_pending_payment_banner(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_PENDING_PAYMENT,
            'billing_cycle' => Subscription::CYCLE_QUARTERLY,
            'gateway_subscription_id' => 'pay_mock_test123',
            'current_period_start' => now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->get(route('vendor.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Tagihan Menunggu Pembayaran');
        $response->assertSee('Batalkan Tagihan');
        $response->assertSee('Lanjutkan Pembayaran');
    }
}
