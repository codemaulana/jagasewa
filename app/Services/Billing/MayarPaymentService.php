<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\VendorUser;
use App\Services\ErrorMonitoringService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MayarPaymentService
{
    public function __construct(
        protected ErrorMonitoringService $errorMonitoring
    ) {}

    /**
     * Create a Mayar payment link / invoice for a subscription order.
     *
     * @return array{payment_url: string, invoice_id: string, is_mock: bool}
     */
    public function createPaymentLink(Subscription $subscription, ?VendorUser $user = null): array
    {
        $apiUrl = rtrim(config('services.mayar.api_url', 'https://api.mayar.id/hl/v1'), '/');
        $apiKey = config('services.mayar.api_key');
        $amount = Subscription::getPlanPrice($subscription->billing_cycle);
        $vendor = $subscription->vendor;

        $description = match ($subscription->billing_cycle) {
            Subscription::CYCLE_QUARTERLY => 'Langganan JagaSewa Pro (3 Bulan)',
            Subscription::CYCLE_ANNUALLY => 'Langganan JagaSewa Pro (1 Tahun)',
            default => 'Langganan JagaSewa Pro (Bulanan)',
        };

        $redirectUrl = route('vendor.billing.finish');

        $payload = [
            'name' => $user?->name ?? $vendor->name,
            'email' => $user?->email ?? ($vendor->slug.'@jagasewa.web.id'),
            'amount' => $amount,
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'expiredAt' => now('Asia/Jakarta')->addHours(24)->toIso8601String(),
            'extraData' => [
                'subscription_ulid' => $subscription->ulid,
                'vendor_id' => $subscription->vendor_id,
                'billing_cycle' => $subscription->billing_cycle,
            ],
        ];

        // If Mayar API Key is configured, perform live HTTP API request
        if (! empty($apiKey)) {
            try {
                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->timeout(15)
                    ->post("{$apiUrl}/payment/create", $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    $data = $json['data'] ?? [];
                    $invoiceId = (string) ($data['id'] ?? $data['payment_id'] ?? Str::ulid());
                    $paymentUrl = (string) ($data['link'] ?? $data['url'] ?? $redirectUrl);

                    $subscription->update([
                        'gateway_subscription_id' => $invoiceId,
                    ]);

                    return [
                        'payment_url' => $paymentUrl,
                        'invoice_id' => $invoiceId,
                        'is_mock' => false,
                    ];
                }

                Log::error('Mayar API invoice creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->errorMonitoring->captureException(new Exception('Mayar API error: '.$response->body()));
            } catch (Exception $e) {
                Log::error('Mayar API connection error: '.$e->getMessage());
                $this->errorMonitoring->captureException($e);
            }
        }

        // Fallback / Mock Sandbox Mode for local development & testing
        $mockInvoiceId = 'pay_mock_'.Str::random(16);
        $mockUrl = "https://sandbox.mayar.id/pay/{$mockInvoiceId}";

        $subscription->update([
            'gateway_subscription_id' => $mockInvoiceId,
        ]);

        return [
            'payment_url' => $mockUrl,
            'invoice_id' => $mockInvoiceId,
            'is_mock' => true,
        ];
    }
}
