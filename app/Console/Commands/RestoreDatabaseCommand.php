<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RestoreDatabaseCommand extends Command
{
    protected $signature = 'db:restore {file}';
    protected $description = 'Restore database dan file storage dari backup';

    public function handle()
    {
        $backupFile = $this->argument('file');
        
        if (!file_exists($backupFile)) {
            $this->error("File backup {$backupFile} tidak ditemukan!");
            return;
        }

        $this->info('=== Memulai proses restore ===');
        $this->newLine();

        if ($this->confirm('Apakah Anda yakin ingin merestore? Semua data saat ini akan diganti!', false)) {
            $this->extractBackup($backupFile);
            $this->restoreDatabase();
            $this->restoreStorage();
            $this->cleanupTemp();

            $this->newLine();
            $this->info('=== Restore selesai! ===');
        } else {
            $this->info('Proses restore dibatalkan.');
        }
    }

    private function extractBackup($backupFile)
    {
        $this->line('  - Mengekstrak file backup...');
        
        $tempDir = storage_path('app/temp_restore');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupFile)) {
            $zip->extractTo($tempDir);
            $zip->close();
            $this->line('    ✓ File backup berhasil diekstrak');
        } else {
            $this->error('    ✗ Gagal mengekstrak file backup');
        }
    }

    private function restoreDatabase()
    {
        $tempDir = storage_path('app/temp_restore');
        $sqlFiles = glob($tempDir . '/*.sql');
        
        if (empty($sqlFiles)) {
            $this->error('  - Tidak ada file SQL di backup!');
            return;
        }

        $sqlFile = $sqlFiles[0];
        $this->line('  - Merestore database...');

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        switch ($connection) {
            case 'mysql':
                $this->restoreMySQL($config, $sqlFile);
                break;
            case 'sqlite':
                $this->restoreSqlite($config, $sqlFile);
                break;
            default:
                $this->error("  - Tipe database {$connection} tidak didukung");
                break;
        }
    }

    private function restoreMySQL($config, $sqlFile)
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        $command = "mysql --host={$host} --port={$port} --user={$username} ";
        if ($password) {
            $command .= "--password='{$password}' ";
        }
        $command .= "{$database} < {$sqlFile}";

        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->line('    ✓ Database MySQL berhasil direstore');
        } else {
            $this->error('    ✗ Gagal restore database MySQL');
        }
    }

    private function restoreSqlite($config, $sqlFile)
    {
        $dbPath = database_path($config['database']);
        if (file_exists($sqlFile)) {
            copy($sqlFile, $dbPath);
            $this->line('    ✓ Database SQLite berhasil direstore');
        } else {
            $this->error('    ✗ File database SQLite tidak ditemukan di backup');
        }
    }

    private function restoreStorage()
    {
        $tempDir = storage_path('app/temp_restore/storage');
        if (!is_dir($tempDir)) {
            $this->line('  - Tidak ada file storage di backup');
            return;
        }

        $this->line('  - Merestore file storage...');
        
        $publicStorage = storage_path('app/public');
        
        $this->copyDirectory($tempDir, $publicStorage);
        
        $this->line('    ✓ File storage berhasil direstore');
    }

    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target);
                }
            } else {
                copy($item, $target);
            }
        }
    }

    private function cleanupTemp()
    {
        $tempDir = storage_path('app/temp_restore');
        if (is_dir($tempDir)) {
            $this->deleteDirectory($tempDir);
            $this->line('  - File temporary dibersihkan');
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
