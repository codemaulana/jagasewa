<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    /**
     * Create an encrypted database backup and upload it to the configured storage disk.
     *
     * @return array{file: string, disk: string, size: int, tables_count: int, pruned_count: int}
     */
    public function backup(?string $disk = null, ?string $password = null): array
    {
        $disk = $disk ?? config('database.backup.disk', 's3');
        $password = $password ?? config('database.backup.password') ?? config('app.key');
        $directory = config('database.backup.directory', 'backups');

        if (empty($password)) {
            throw new RuntimeException('Backup password or APP_KEY must not be empty for encrypted backup.');
        }

        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d_His');
        $sqlFileName = "backup-jagasewa-{$timestamp}.sql";
        $zipFileName = "{$sqlFileName}.zip";

        $tempSqlPath = tempnam(sys_get_temp_dir(), 'sql_');
        $tempZipPath = tempnam(sys_get_temp_dir(), 'zip_');

        try {
            $tableCount = $this->dumpDatabaseToFile($tempSqlPath);

            $this->createEncryptedZip($tempSqlPath, $tempZipPath, $sqlFileName, $password);

            $remotePath = trim($directory, '/').'/'.$zipFileName;
            $fileStream = fopen($tempZipPath, 'r');
            $uploaded = Storage::disk($disk)->put($remotePath, $fileStream);
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            if (! $uploaded) {
                throw new RuntimeException("Failed to upload backup archive to disk [{$disk}] at path [{$remotePath}].");
            }

            $size = (int) filesize($tempZipPath);

            // Apply 30-day retention pruning
            $prunedCount = $this->pruneOldBackups($disk);

            Log::info('Database backup completed successfully', [
                'file' => $remotePath,
                'disk' => $disk,
                'size' => $size,
                'tables' => $tableCount,
                'pruned' => $prunedCount,
            ]);

            return [
                'file' => $remotePath,
                'disk' => $disk,
                'size' => $size,
                'tables_count' => $tableCount,
                'pruned_count' => $prunedCount,
            ];
        } finally {
            if ($tempSqlPath && file_exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
            if ($tempZipPath && file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }
    }

    /**
     * Dump all tables and data to an SQL file.
     */
    public function dumpDatabaseToFile(string $filePath): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $handle = fopen($filePath, 'w');
        if (! $handle) {
            throw new RuntimeException("Unable to open temp file for writing SQL dump: {$filePath}");
        }

        fwrite($handle, "-- JagaSewa Database Backup\n");
        fwrite($handle, '-- Generated: '.now()->timezone('Asia/Jakarta')->toIso8601String()."\n");
        fwrite($handle, '-- Driver: '.$driver."\n\n");

        $tableCount = 0;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            $tables = $connection->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            foreach ($tables as $tableObj) {
                $tableProp = array_keys((array) $tableObj)[0];
                $table = $tableObj->$tableProp;

                $this->dumpMySqlTable($connection, $table, $handle);
                $tableCount++;
            }

            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        } elseif ($driver === 'sqlite') {
            fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

            $tables = $connection->select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            foreach ($tables as $tableObj) {
                $table = $tableObj->name;
                $createSql = $tableObj->sql;

                fwrite($handle, "-- Table: `{$table}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql.";\n\n");

                $this->dumpTableData($connection, $table, $handle);
                $tableCount++;
            }

            fwrite($handle, "\nPRAGMA foreign_keys = ON;\n");
        } else {
            // Generic schema fallback
            $tables = $connection->getSchemaBuilder()->getTableListing();
            foreach ($tables as $table) {
                $this->dumpTableData($connection, $table, $handle);
                $tableCount++;
            }
        }

        fclose($handle);

        return $tableCount;
    }

    /**
     * Dump a MySQL table schema and data.
     *
     * @param  Connection  $connection
     * @param  resource  $handle
     */
    protected function dumpMySqlTable($connection, string $table, $handle): void
    {
        fwrite($handle, "-- Table: `{$table}`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

        $createResult = $connection->select("SHOW CREATE TABLE `{$table}`");
        if (! empty($createResult)) {
            $createRow = (array) $createResult[0];
            $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? null;
            if ($createSql) {
                fwrite($handle, $createSql.";\n\n");
            }
        }

        $this->dumpTableData($connection, $table, $handle);
        fwrite($handle, "\n");
    }

    /**
     * Dump table records using raw PDO cursor stream.
     *
     * @param  Connection  $connection
     * @param  resource  $handle
     */
    protected function dumpTableData($connection, string $table, $handle): void
    {
        $pdo = $connection->getPdo();
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        if (! $stmt) {
            return;
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $columns = array_keys($row);
            $escapedColumns = array_map(fn ($col) => "`{$col}`", $columns);

            $values = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } elseif (is_int($val) || is_float($val)) {
                    $values[] = (string) $val;
                } elseif (is_bool($val)) {
                    $values[] = $val ? '1' : '0';
                } else {
                    $values[] = $pdo->quote((string) $val);
                }
            }

            $sql = sprintf(
                "INSERT INTO `%s` (%s) VALUES (%s);\n",
                $table,
                implode(', ', $escapedColumns),
                implode(', ', $values)
            );
            fwrite($handle, $sql);
        }
    }

    /**
     * Create an AES-256 encrypted zip archive containing the SQL dump.
     */
    public function createEncryptedZip(string $sourceFile, string $destinationZip, string $entryName, string $password): void
    {
        $zip = new ZipArchive;
        $status = $zip->open($destinationZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($status !== true) {
            throw new RuntimeException("Could not create zip file at [{$destinationZip}], ZipArchive code: {$status}");
        }

        if (! $zip->addFile($sourceFile, $entryName)) {
            $zip->close();
            throw new RuntimeException("Failed to add file [{$sourceFile}] to zip archive.");
        }

        if (! $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256, $password)) {
            $zip->close();
            throw new RuntimeException("Failed to set AES-256 encryption on [{$entryName}].");
        }

        $zip->close();
    }

    /**
     * Prune backups older than retention days from storage.
     */
    public function pruneOldBackups(?string $disk = null, ?int $retentionDays = null): int
    {
        $disk = $disk ?? config('database.backup.disk', 's3');
        $retentionDays = $retentionDays ?? config('database.backup.retention_days', 30);
        $directory = config('database.backup.directory', 'backups');

        $storage = Storage::disk($disk);
        $files = $storage->files($directory);
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $prunedCount = 0;

        foreach ($files as $file) {
            if (! str_ends_with($file, '.zip')) {
                continue;
            }

            $lastModified = $storage->lastModified($file);
            if ($lastModified && $lastModified <= $cutoff) {
                $storage->delete($file);
                $prunedCount++;
                Log::info("Pruned old backup file [{$file}] older than {$retentionDays} days.");
            }
        }

        return $prunedCount;
    }

    /**
     * Restore database from an encrypted backup archive.
     *
     * @param  string  $backupFilePath  Path to backup archive (either remote on $disk, or absolute local path)
     * @param  string|null  $password  Decryption password
     * @param  string|null  $disk  Storage disk where backup is stored (null if local file)
     * @return array{restored_file: string, statements_count: int}
     */
    public function restore(string $backupFilePath, ?string $password = null, ?string $disk = null): array
    {
        $password = $password ?? config('database.backup.password') ?? config('app.key');
        if (empty($password)) {
            throw new RuntimeException('Password is required to decrypt and restore database.');
        }

        $tempZipPath = null;
        if ($disk !== null && Storage::disk($disk)->exists($backupFilePath)) {
            $tempZipPath = tempnam(sys_get_temp_dir(), 'restore_zip_');
            $stream = Storage::disk($disk)->readStream($backupFilePath);
            file_put_contents($tempZipPath, stream_get_contents($stream));
            if (is_resource($stream)) {
                fclose($stream);
            }
            $zipFileToOpen = $tempZipPath;
        } elseif (file_exists($backupFilePath)) {
            $zipFileToOpen = $backupFilePath;
        } else {
            throw new RuntimeException("Backup file not found at [{$backupFilePath}] (disk: ".($disk ?? 'local path').').');
        }

        try {
            $zip = new ZipArchive;
            $openResult = $zip->open($zipFileToOpen);
            if ($openResult !== true) {
                throw new RuntimeException("Failed to open backup zip file, error code: {$openResult}");
            }

            if (! $zip->setPassword($password)) {
                $zip->close();
                throw new RuntimeException('Failed to set zip password for decryption.');
            }

            // Find sql entry
            $sqlEntryName = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat && str_ends_with($stat['name'], '.sql')) {
                    $sqlEntryName = $stat['name'];
                    break;
                }
            }

            if (! $sqlEntryName) {
                $zip->close();
                throw new RuntimeException('No .sql file found in backup zip archive.');
            }

            $sqlContent = $zip->getFromName($sqlEntryName);
            $zip->close();

            if ($sqlContent === false || $sqlContent === '') {
                throw new RuntimeException('Failed to extract or decrypt SQL content. Check that the password is correct.');
            }

            $statementsCount = $this->executeSqlDump($sqlContent);

            Log::info('Database restored successfully', [
                'backup_file' => $backupFilePath,
                'statements_count' => $statementsCount,
            ]);

            return [
                'restored_file' => $backupFilePath,
                'statements_count' => $statementsCount,
            ];
        } finally {
            if ($tempZipPath && file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }
    }

    /**
     * Execute SQL statements with foreign keys disabled during import.
     */
    public function executeSqlDump(string $sqlContent): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        try {
            DB::unprepared($sqlContent);
        } finally {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }

        $count = preg_match_all('/;\s*$/m', $sqlContent);

        return max(1, $count);
    }

    /**
     * Get the latest backup archive path from storage.
     */
    public function getLatestBackupFile(?string $disk = null): ?string
    {
        $disk = $disk ?? config('database.backup.disk', 's3');
        $directory = config('database.backup.directory', 'backups');
        $files = Storage::disk($disk)->files($directory);

        $zipFiles = array_filter($files, fn ($file) => str_ends_with($file, '.zip'));
        if (empty($zipFiles)) {
            return null;
        }

        rsort($zipFiles);

        return array_values($zipFiles)[0];
    }

    /**
     * List all available backups from storage disk.
     *
     * @return array<int, array{path: string, size: int, last_modified: int}>
     */
    public function listBackups(?string $disk = null): array
    {
        $disk = $disk ?? config('database.backup.disk', 's3');
        $directory = config('database.backup.directory', 'backups');
        $storage = Storage::disk($disk);
        $files = $storage->files($directory);

        $result = [];
        foreach ($files as $file) {
            if (! str_ends_with($file, '.zip')) {
                continue;
            }

            $result[] = [
                'path' => $file,
                'size' => (int) $storage->size($file),
                'last_modified' => (int) $storage->lastModified($file),
            ];
        }

        usort($result, fn ($a, $b) => $b['last_modified'] <=> $a['last_modified']);

        return $result;
    }
}
