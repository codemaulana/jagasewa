<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MayarCheckoutTest extends TestCase
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
     * Checkout bulanan (monthly) creates pending subscription and redirects to sandbox link
     */
    public function test_vendor_checkout_monthly_creates_pending_subscription_and_redirects(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location') ?? '';
        $this->assertTrue(str_contains($targetUrl, 'mayar') || str_contains($targetUrl, 'myr.lat') || str_contains($targetUrl, 'invoices'));

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
            ->assertRedirect();

        $subQuarterly = Subscription::where('vendor_id', $vendor->id)->where('billing_cycle', Subscription::CYCLE_QUARTERLY)->first();
        $this->assertNotNull($subQuarterly);
        $this->assertEquals(Subscription::CYCLE_QUARTERLY, $subQuarterly->billing_cycle);

        // Annually
        $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), ['cycle' => 'annually'])
            ->assertRedirect();

        $subAnnually = Subscription::where('vendor_id', $vendor->id)->where('billing_cycle', Subscription::CYCLE_ANNUALLY)->first();
        $this->assertNotNull($subAnnually);
        $this->assertEquals(Subscription::CYCLE_ANNUALLY, $subAnnually->billing_cycle);
    }

    /**
     * Live Mayar API call integration when MAYAR_API_KEY is configured
     */
    public function test_checkout_calls_mayar_api_when_key_is_configured(): void
    {
        Config::set('services.mayar.api_key', 'mayar_live_api_key_test_123');
        Config::set('services.mayar.api_url', 'https://api.mayar.id/hl/v1');

        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);

        Http::fake([
            'https://api.mayar.id/hl/v1/payment/create' => Http::response([
                'statusCode' => 200,
                'messages' => 'Payment successfully created',
                'data' => [
                    'id' => 'pay_mayar_api_real_999',
                    'link' => 'https://pay.mayar.id/l/jagasewa-pro-monthly',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->post(route('vendor.billing.checkout'), [
                'cycle' => 'monthly',
            ]);

        $response->assertRedirect('https://pay.mayar.id/l/jagasewa-pro-monthly');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mayar.id/hl/v1/payment/create'
                && $request->hasHeader('Authorization', 'Bearer mayar_live_api_key_test_123')
                && $request['amount'] === 15000
                && isset($request['extraData']['subscription_ulid']);
        });

        $subscription = Subscription::where('vendor_id', $vendor->id)->latest()->first();
        $this->assertEquals('pay_mayar_api_real_999', $subscription->gateway_subscription_id);
    }
}
