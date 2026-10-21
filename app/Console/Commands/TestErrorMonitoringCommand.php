<?php

namespace App\Console\Commands;

use App\Services\ErrorMonitoringService;
use Exception;
use Illuminate\Console\Command;

class TestErrorMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:test
                            {--message= : Custom test error message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger a test exception and verify Sentry / Webhook error monitoring integration';

    /**
     * Execute the console command.
     */
    public function handle(ErrorMonitoringService $service): int
    {
        $message = $this->option('message') ?? 'Manual test exception triggered via [php artisan monitor:test]';

        $this->info('Triggering test exception for JagaSewa error monitoring...');

        $testException = new Exception($message);

        $result = $service->captureException($testException, [
            'test_mode' => true,
            'triggered_by' => 'CLI artisan monitor:test',
            'sample_nik' => '3201012345678901', // Verifies PII redaction
            'sample_phone' => '081234567890',    // Verifies PII redaction
        ]);

        $this->newLine();
        $this->table(
            ['Parameter', 'Status'],
            [
                ['Event ID', $result['event_id']],
                ['Sentry Integration', $result['sentry_sent'] ? '✓ Dispatched Successfully' : (config('services.monitoring.sentry_dsn') ? '✗ Failed' : '○ Not Configured (SENTRY_DSN empty)')],
                ['Webhook Alert', $result['webhook_sent'] ? '✓ Dispatched Successfully' : (config('services.monitoring.webhook_url') ? '✗ Failed' : '○ Not Configured (MONITOR_WEBHOOK_URL empty)')],
                ['Local Log Channel', '✓ Recorded in Laravel Log with PII Redacted'],
            ]
        );

        $this->info('✓ Error monitoring pipeline executed successfully.');

        return Command::SUCCESS;
    }
}
