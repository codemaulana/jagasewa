<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Billing\MayarWebhookHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SimulateMayarPaymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:simulate-mayar {plt_id? : Optional paymentLinkTransactionId from browser URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate payment for pending Mayar Pro subscription in sandbox/local environment';

    /**
     * Execute the console command.
     */
    public function handle(MayarWebhookHandler $webhookHandler): int
    {
        $subscription = Subscription::where('status', Subscription::STATUS_PENDING_PAYMENT)
            ->latest('id')
            ->first();

        if (! $subscription) {
            $this->warn('Tidak ditemukan subscription dengan status pending_payment.');

            return self::FAILURE;
        }

        $pltId = $this->argument('plt_id');

        $this->info("Menemukan order langganan ID [{$subscription->id}] ULID [{$subscription->ulid}] Vendor [{$subscription->vendor_id}]");

        // 1. If pltId is provided, attempt live Mayar sandbox simulation API call
        $apiKey = config('services.mayar.api_key');
        if (! empty($pltId) && ! empty($apiKey)) {
            $this->info("Memanggil API Simulasi Mayar Sandbox untuk ID: {$pltId}...");
            try {
                $response = Http::withToken($apiKey)->post('https://api.mayar.io/hl/v2/payments/simulate', [
                    'paymentLinkTransactionId' => $pltId,
                ]);

                if ($response->successful()) {
                    $this->info('API Simulasi Mayar berhasil merespons 200 OK.');
                } else {
                    $this->warn('Mayar API response: '.$response->body());
                }
            } catch (\Exception $e) {
                $this->warn('Gagal menghubungi API Mayar: '.$e->getMessage());
            }
        }

        // 2. Process webhook handler directly to ensure instant upgrade on localhost
        $amount = Subscription::getPlanPrice($subscription->billing_cycle);
        $eventId = 'pay_sim_'.Str::random(16);

        $payload = [
            'event' => 'payment.received',
            'data' => [
                'id' => $eventId,
                'status' => 'SUCCESS',
                'amount' => $amount,
                'currency' => 'IDR',
                'extra_data' => [
                    'subscription_ulid' => $subscription->ulid,
                    'vendor_id' => (string) $subscription->vendor_id,
                ],
            ],
        ];

        $result = $webhookHandler->handle($payload);

        if ($result['code'] === 200) {
            $subscription->refresh();
            $this->info("SUKSES: Langganan Pro vendor [{$subscription->vendor_id}] berhasil DIAKTIFKAN!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Plan', $subscription->plan],
                    ['Status', $subscription->status],
                    ['Cycle', $subscription->billing_cycle],
                    ['Periode Berakhir', $subscription->current_period_end?->toDateTimeString()],
                ]
            );

            return self::SUCCESS;
        }

        $this->error('Gagal memproses aktivasi: '.$result['message']);

        return self::FAILURE;
    }
}
