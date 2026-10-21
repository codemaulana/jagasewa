<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--disk= : Storage disk to upload the backup archive (default: s3)}
                            {--password= : Custom AES-256 encryption password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an encrypted database backup (AES-256) and upload to storage with 30-day retention';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        $disk = $this->option('disk');
        $password = $this->option('password');

        $this->info('Starting automated database backup...');

        try {
            $result = $service->backup($disk, $password);

            $this->newLine();
            $this->table(
                ['Attribute', 'Value'],
                [
                    ['File', $result['file']],
                    ['Storage Disk', $result['disk']],
                    ['Archive Size', number_format($result['size'] / 1024, 2).' KB'],
                    ['Tables Dumped', (string) $result['tables_count']],
                    ['Retention Pruned', $result['pruned_count'].' file(s) > 30 days old'],
                    ['Encryption', 'AES-256 (Password-protected ZIP)'],
                ]
            );

            $this->info('✓ Database backup completed successfully.');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Database backup failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
