<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup {--filename=}';
    protected $description = 'Backup semua data database dan file storage';

    public function handle()
    {
        $this->info('=== Memulai proses backup ===');
        $this->newLine();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = $this->option('filename') ?? "backup_{$timestamp}.sql";
        $backupPath = storage_path("app/backups/{$filename}");

        $this->createBackupDirectory();
        $this->backupDatabase($backupPath);
        $this->compressBackup($backupPath);

        $this->newLine();
        $this->info('=== Backup selesai! ===');
        $this->info("File backup: {$backupPath}.zip");
    }

    private function createBackupDirectory()
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
            $this->line('  - Direktori backups dibuat');
        }
    }

    private function backupDatabase($backupPath)
    {
        $this->line('  - Membackup database...');
        
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        switch ($connection) {
            case 'mysql':
                $this->backupMySQL($config, $backupPath);
                break;
            case 'sqlite':
                $this->backupSqlite($config, $backupPath);
                break;
            default:
                $this->error("  - Tipe database {$connection} tidak didukung");
                break;
        }
    }

    private function backupMySQL($config, $backupPath)
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        $command = "mysqldump --host={$host} --port={$port} --user={$username} ";
        if ($password) {
            $command .= "--password='{$password}' ";
        }
        $command .= "{$database} > {$backupPath}";

        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->line('    ✓ Database MySQL berhasil dibackup');
        } else {
            $this->error('    ✗ Gagal backup database MySQL');
        }
    }

    private function backupSqlite($config, $backupPath)
    {
        $dbPath = database_path($config['database']);
        if (file_exists($dbPath)) {
            copy($dbPath, $backupPath);
            $this->line('    ✓ Database SQLite berhasil dibackup');
        } else {
            $this->error('    ✗ File database SQLite tidak ditemukan');
        }
    }

    private function compressBackup($backupPath)
    {
        if (!file_exists($backupPath)) {
            return;
        }

        $this->line('  - Mengompres file backup...');
        
        $zip = new \ZipArchive();
        $zipPath = $backupPath . '.zip';
        
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $zip->addFile($backupPath, basename($backupPath));
            
            $storagePath = storage_path('app/public');
            if (is_dir($storagePath)) {
                $this->addDirectoryToZip($zip, $storagePath, 'storage');
            }
            
            $zip->close();
            unlink($backupPath);
            $this->line('    ✓ File backup berhasil dikompres');
        } else {
            $this->error('    ✗ Gagal mengompres file backup');
        }
    }

    private function addDirectoryToZip($zip, $dir, $zipPath)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . '/' . substr($filePath, strlen($dir) + 1);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
