<?php

namespace App\Console\Commands;

use App\Jobs\AutoDeleteDocumentsJob;
use Illuminate\Console\Command;

class AutoDeleteDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:auto-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically purge expired identity documents (KTP & selfie) according to retention policy (BR-05)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired identity documents...');

        $purgedCount = (new AutoDeleteDocumentsJob)->handle();

        $this->info("Successfully purged {$purgedCount} expired identity document(s).");

        return self::SUCCESS;
    }
}
