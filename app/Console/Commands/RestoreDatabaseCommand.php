<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class RestoreDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore
                            {file? : Path or filename of the backup zip (defaults to latest backup on disk)}
                            {--disk= : Storage disk to retrieve backup from (default: s3)}
                            {--password= : Custom decryption password}
                            {--force : Force execution without interactive confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database from an encrypted backup archive (AES-256)';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        $file = $this->argument('file');
        $disk = $this->option('disk') ?? config('database.backup.disk', 's3');
        $password = $this->option('password');
        $force = $this->option('force');

        if (! $file) {
            $this->info("Scanning storage disk [{$disk}] for latest backup...");
            $file = $service->getLatestBackupFile($disk);

            if (! $file) {
                $this->error("No backup archive found on disk [{$disk}].");

                return Command::FAILURE;
            }

            $this->info("Found latest backup archive: {$file}");
        }

        if (! $force && ! $this->confirm('WARNING: This will drop and overwrite existing database tables. Are you sure you want to proceed?', false)) {
            $this->warn('Database restore cancelled by user.');

            return Command::SUCCESS;
        }

        $this->info("Decrypting and restoring database from [{$file}]...");

        try {
            $result = $service->restore($file, $password, $disk);

            $this->newLine();
            $this->table(
                ['Attribute', 'Value'],
                [
                    ['Restored File', $result['restored_file']],
                    ['Statements Processed', (string) $result['statements_count']],
                    ['Status', 'Completed Successfully'],
                ]
            );

            $this->info('✓ Database restored successfully.');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Database restore failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
