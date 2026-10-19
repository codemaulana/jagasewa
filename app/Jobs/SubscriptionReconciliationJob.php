<?php

namespace App\Jobs;

use App\Services\Billing\SubscriptionReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubscriptionReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to reconcile subscriptions against gateway status.
     *
     * @return array{total: int, synced: int, unchanged: int, errors: int}
     */
    public function handle(SubscriptionReconciliationService $reconciliationService): array
    {
        Log::info('SubscriptionReconciliationJob started.');

        $results = $reconciliationService->reconcileAll();

        Log::info('SubscriptionReconciliationJob completed.', $results);

        return $results;
    }
}
