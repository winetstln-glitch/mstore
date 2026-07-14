<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class ProductionAuditCommand extends Command
{
    protected $signature = 'production:audit';
    protected $description = 'Audit kesiapan production dan hitung Production Readiness Score';

    public function handle(): int
    {
        $items = [
            $this->checkAppDebug(),
            $this->checkHttps(),
            $this->checkRedis(),
            $this->checkQueue(),
            $this->checkScheduler(),
            $this->checkBackup(),
            $this->checkLogRotation(),
            $this->checkWebhook(),
        ];

        $score = 0;
        foreach ($items as $item) {
            $score += (int) $item['score'];
            $icon = $item['ok'] ? '✓' : '✗';
            $message = trim((string) ($item['message'] ?? ''));
            $line = $icon.' '.$item['name'];
            if ($message !== '') {
                $line .= ' — '.$message;
            }
            $this->line($line);
        }

        $score = max(0, min(100, $score));
        $this->newLine();
        $this->info('Production Readiness Score: '.$score.'/100');

        if ($score >= 90) {
            $this->info('Status akhir: READY');
            return 0;
        }
        if ($score >= 70) {
            $this->warn('Status akhir: WARNING');
            return 1;
        }

        $this->error('Status akhir: CRITICAL');
        return 2;
    }

    private function checkAppDebug(): array
    {
        $debug = (bool) config('app.debug');
        return [
            'name' => 'APP_DEBUG=false',
            'ok' => ! $debug,
            'score' => $debug ? 0 : 20,
            'message' => $debug ? 'Aktif' : 'OK',
        ];
    }

    private function checkHttps(): array
    {
        $url = (string) config('app.url');
        $ok = str_starts_with(strtolower($url), 'https://');
        return [
            'name' => 'HTTPS aktif (APP_URL)',
            'ok' => $ok,
            'score' => $ok ? 10 : 0,
            'message' => $url !== '' ? $url : 'Tidak dikonfigurasi',
        ];
    }

    private function checkRedis(): array
    {
        try {
            $pong = Redis::connection()->command('ping');
            $ok = (string) $pong === 'PONG';
            return [
                'name' => 'Redis aktif',
                'ok' => $ok,
                'score' => $ok ? 10 : 0,
                'message' => $ok ? 'PONG' : 'Ping gagal',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Redis aktif',
                'ok' => false,
                'score' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        $driver = (string) config('queue.default');
        $ok = $driver !== '' && $driver !== 'sync';
        return [
            'name' => 'Queue driver non-sync',
            'ok' => $ok,
            'score' => $ok ? 15 : 0,
            'message' => $driver !== '' ? $driver : 'Tidak dikonfigurasi',
        ];
    }

    private function checkScheduler(): array
    {
        $value = Cache::get('mstore.scheduler.heartbeat_at');
        if (! is_string($value) || trim($value) === '') {
            return [
                'name' => 'Scheduler aktif',
                'ok' => false,
                'score' => 0,
                'message' => 'Heartbeat belum terdeteksi',
            ];
        }

        try {
            $ts = Carbon::parse($value);
            $ageSeconds = $ts->diffInSeconds(now());
            $ok = $ageSeconds <= 180;

            return [
                'name' => 'Scheduler aktif',
                'ok' => $ok,
                'score' => $ok ? 15 : 0,
                'message' => 'last='.$ts->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Scheduler aktif',
                'ok' => false,
                'score' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkBackup(): array
    {
        $hasBackup = Schema::hasTable('backup_logs') || Schema::hasTable('backups');
        $score = $hasBackup ? 10 : 0;
        return [
            'name' => 'Backup aktif',
            'ok' => $hasBackup,
            'score' => $score,
            'message' => $hasBackup ? 'Tabel backup tersedia' : 'Tidak terdeteksi (pastikan dijadwalkan)',
        ];
    }

    private function checkLogRotation(): array
    {
        $driver = (string) config('logging.default');
        $channels = (array) config('logging.channels');
        $isDaily = isset($channels[$driver]) && (($channels[$driver]['driver'] ?? null) === 'daily');

        return [
            'name' => 'Log rotation',
            'ok' => $isDaily,
            'score' => $isDaily ? 10 : 0,
            'message' => $driver !== '' ? $driver : 'Tidak dikonfigurasi',
        ];
    }

    private function checkWebhook(): array
    {
        $hasWhatsApp = trim((string) Setting::getValue('whatsapp_api_url', '')) !== '';
        $hasDuitku = trim((string) Setting::getValue('duitku_merchant_code', '')) !== '';
        $ok = $hasWhatsApp && $hasDuitku;

        return [
            'name' => 'Webhook/Integrasi aktif',
            'ok' => $ok,
            'score' => $ok ? 10 : 0,
            'message' => $ok ? 'configured' : 'Periksa WhatsApp/Duitku',
        ];
    }
}

