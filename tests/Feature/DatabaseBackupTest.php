<?php

namespace Tests\Feature;

use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_it_creates_encrypted_backup_and_uploads_to_storage(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Rental Kostum Cantik']);
        VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Owner Kostum',
            'email' => 'owner@example.com',
        ]);

        $password = 'SecretBackupKey2026!';
        $exitCode = Artisan::call('backup:database', [
            '--disk' => 's3',
            '--password' => $password,
        ]);

        $this->assertEquals(0, $exitCode);

        $files = Storage::disk('s3')->files('backups');
        $this->assertNotEmpty($files, 'Backup file was not created in s3 backups directory.');

        $backupFile = $files[0];
        $this->assertStringEndsWith('.sql.zip', $backupFile);

        // Verify that the file is an AES-256 encrypted zip archive
        $stream = Storage::disk('s3')->readStream($backupFile);
        $tempPath = tempnam(sys_get_temp_dir(), 'test_zip_');
        file_put_contents($tempPath, stream_get_contents($stream));
        fclose($stream);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempPath));

        // Attempting to extract without password should fail
        $sqlName = $zip->getNameIndex(0);
        $this->assertStringEndsWith('.sql', $sqlName);

        $unauthenticatedContent = $zip->getFromName($sqlName);
        $this->assertFalse($unauthenticatedContent, 'Reading file without password should return false for encrypted zip.');

        // Extracting with the correct password must succeed
        $zip->setPassword($password);
        $decryptedContent = $zip->getFromName($sqlName);
        $zip->close();
        @unlink($tempPath);

        $this->assertIsString($decryptedContent);
        $this->assertStringContainsString('Rental Kostum Cantik', $decryptedContent);
        $this->assertStringContainsString('owner@example.com', $decryptedContent);
    }

    public function test_it_prunes_backups_older_than_retention_days(): void
    {
        // Place an old backup file and set its modification time to 35 days ago
        Storage::disk('s3')->put('backups/backup-jagasewa-old.sql.zip', 'dummy old content');
        $oldPath = Storage::disk('s3')->path('backups/backup-jagasewa-old.sql.zip');
        touch($oldPath, time() - (35 * 86400));

        // Place a fresh backup file with current timestamp
        Storage::disk('s3')->put('backups/backup-jagasewa-fresh.sql.zip', 'dummy fresh content');
        $freshPath = Storage::disk('s3')->path('backups/backup-jagasewa-fresh.sql.zip');
        touch($freshPath, time());

        $service = app(DatabaseBackupService::class);
        $prunedCount = $service->pruneOldBackups('s3', 30);

        $this->assertEquals(1, $prunedCount);
        $this->assertFalse(Storage::disk('s3')->exists('backups/backup-jagasewa-old.sql.zip'));
        $this->assertTrue(Storage::disk('s3')->exists('backups/backup-jagasewa-fresh.sql.zip'));
    }

    public function test_it_restores_database_from_encrypted_backup(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Original Vendor',
            'slug' => 'original-vendor',
        ]);

        $password = 'SecretRestoreKey2026!';

        // Run backup
        Artisan::call('backup:database', [
            '--disk' => 's3',
            '--password' => $password,
        ]);

        // Modify database: update vendor name and add a new vendor
        $vendor->update(['name' => 'Modified Name After Backup']);
        $newVendor = Vendor::factory()->create([
            'name' => 'Vendor Created After Backup',
            'slug' => 'vendor-after-backup',
        ]);

        $this->assertDatabaseHas('vendors', ['name' => 'Modified Name After Backup']);
        $this->assertDatabaseHas('vendors', ['name' => 'Vendor Created After Backup']);

        // Run restore with force
        $exitCode = Artisan::call('backup:restore', [
            '--disk' => 's3',
            '--password' => $password,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Assert database state is restored back to the snapshot
        $this->assertDatabaseHas('vendors', ['name' => 'Original Vendor']);
        $this->assertDatabaseMissing('vendors', ['name' => 'Modified Name After Backup']);
        $this->assertDatabaseMissing('vendors', ['name' => 'Vendor Created After Backup']);
    }

    public function test_restore_fails_with_invalid_password(): void
    {
        Vendor::factory()->create(['name' => 'Vendor Secret']);

        Artisan::call('backup:database', [
            '--disk' => 's3',
            '--password' => 'CorrectPassword123',
        ]);

        $exitCode = Artisan::call('backup:restore', [
            '--disk' => 's3',
            '--password' => 'WrongPassword999',
            '--force' => true,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_restore_automatically_picks_latest_backup_when_no_file_given(): void
    {
        Vendor::factory()->create(['name' => 'Auto Pick Vendor']);

        $password = 'SecretKeyAutoPick123!';
        Artisan::call('backup:database', [
            '--disk' => 's3',
            '--password' => $password,
        ]);

        Vendor::query()->delete();
        $this->assertDatabaseEmpty('vendors');

        // Call restore without file argument
        $exitCode = Artisan::call('backup:restore', [
            '--disk' => 's3',
            '--password' => $password,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('vendors', ['name' => 'Auto Pick Vendor']);
    }
}
