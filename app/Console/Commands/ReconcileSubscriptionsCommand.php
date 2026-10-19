<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Billing\SubscriptionReconciliationService;
use Illuminate\Console\Command;

class ReconcileSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:reconcile {--subscription= : Optional specific subscription ULID or ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile local subscription statuses against payment gateways (Mayar/Xendit)';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionReconciliationService $service): int
    {
        $specific = $this->option('subscription');

        if ($specific) {
            $subscription = Subscription::where('ulid', $specific)
                ->orWhere('id', is_numeric($specific) ? (int) $specific : 0)
                ->first();

            if (! $subscription) {
                $this->error("Subscription [{$specific}] tidak ditemukan.");

                return self::FAILURE;
            }

            $this->info("Merekonsiliasi subscription ID [{$subscription->id}] ULID [{$subscription->ulid}]...");
            $result = $service->reconcile($subscription);

            $this->table(
                ['Key', 'Value'],
                [
                    ['Status', $result['status']],
                    ['Action', $result['action']],
                    ['Message', $result['message']],
                ]
            );

            return self::SUCCESS;
        }

        $this->info('Memulai rekonsiliasi seluruh langganan aktif, pending, dan past_due...');
        $stats = $service->reconcileAll();

        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Diperiksa', $stats['total']],
                ['Disinkronkan (Updated)', $stats['synced']],
                ['Tidak Berubah', $stats['unchanged']],
                ['Error / Gagal', $stats['errors']],
            ]
        );

        $this->info('Rekonsiliasi langganan selesai.');

        return self::SUCCESS;
    }
}
