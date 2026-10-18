<?php

namespace App\Console\Commands;

use App\Jobs\AutoCancelPendingBookingsJob;
use Illuminate\Console\Command;

class AutoCancelPendingBookingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:auto-cancel-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-cancel pending bookings that have exceeded their 24h expiration deadline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired pending bookings...');

        $cancelledCount = (new AutoCancelPendingBookingsJob)->handle();

        $this->info("Successfully cancelled {$cancelledCount} expired pending booking(s).");

        return self::SUCCESS;
    }
}
