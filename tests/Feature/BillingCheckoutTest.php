<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated vendor cannot access checkout
     */
    public function test_unauthenticated_user_cannot_initiate_checkout(): void
    {
        $response = $this->post(route('vendor.billing.checkout'), [
            'cycle' => 'monthly',
        ]);

        $response->assertRedirect('/vendor/login');
    }

    /**
     * Invalid billing cycle fails validation
     */
    public function test_invalid_billing_cycle_fails_validation(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'lifetime_hack',
            ]);

        $response->assertSessionHasErrors(['cycle']);
    }

    /**
     * Checkout bulanan (monthly) creates pending subscription and redirects to billing index
     */
    public function test_vendor_checkout_monthly_creates_pending_subscription(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertRedirect(route('vendor.billing.index'));
        $response->assertSessionHas('status');

        // Subscription created with pending_payment status
        $subscription = Subscription::where('vendor_id', $vendor->id)->latest()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(Subscription::PLAN_PRO, $subscription->plan);
        $this->assertEquals(Subscription::STATUS_PENDING_PAYMENT, $subscription->status);
        $this->assertEquals(Subscription::CYCLE_MONTHLY, $subscription->billing_cycle);
        $this->assertNotNull($subscription->gateway_subscription_id);
    }

    /**
     * Checkout 3 bulan (quarterly) dan 1 tahun (annually)
     */
    public function test_vendor_checkout_quarterly_and_annually(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        // Quarterly
        $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), ['cycle' => 'quarterly'])
            ->assertRedirect(route('vendor.billing.index'));

        $subQuarterly = Subscription::where('vendor_id', $vendor->id)->where('billing_cycle', Subscription::CYCLE_QUARTERLY)->first();
        $this->assertNotNull($subQuarterly);
        $this->assertEquals(Subscription::CYCLE_QUARTERLY, $subQuarterly->billing_cycle);

        // Cancel previous pending order when choosing different cycle
        $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), ['cycle' => 'annually'])
            ->assertRedirect(route('vendor.billing.index'));

        $subQuarterly->refresh();
        $this->assertEquals(Subscription::STATUS_CANCELLED, $subQuarterly->status);

        $subAnnually = Subscription::where('vendor_id', $vendor->id)->where('billing_cycle', Subscription::CYCLE_ANNUALLY)->first();
        $this->assertNotNull($subAnnually);
        $this->assertEquals(Subscription::CYCLE_ANNUALLY, $subAnnually->billing_cycle);
    }

    /**
     * JSON checkout request returns subscription ulid and gateway subscription id
     */
    public function test_json_checkout_returns_success_response(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->postJson(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $subscription = Subscription::where('vendor_id', $vendor->id)->latest()->first();
        $response->assertJson([
            'subscription_ulid' => $subscription->ulid,
            'gateway_subscription_id' => $subscription->gateway_subscription_id,
        ]);
    }

    /**
     * Vendor already Pro cannot double subscribe without renewal flag
     */
    public function test_already_pro_vendor_cannot_checkout_without_renewal(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Subscription::factory()->create([
            'vendor_id' => $vendor->id,
            'plan' => Subscription::PLAN_PRO,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now('Asia/Jakarta')->addDays(20),
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertRedirect(route('vendor.billing.index'));
        $response->assertSessionHas('error');
    }
}
