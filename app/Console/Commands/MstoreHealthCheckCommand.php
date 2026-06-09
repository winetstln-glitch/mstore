<?php

namespace App\Console\Commands;

use App\Models\GenieAcsServer;
use App\Models\Router;
use App\Models\Setting;
use App\Services\GenieACSService;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MstoreHealthCheckCommand extends Command
{
    protected $signature = 'mstore:health-check';
    protected $description = 'Health check untuk komponen inti & integrasi MStore';

    public function handle(): int
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkRedis(),
            $this->checkQueue(),
            $this->checkSchedulerHeartbeat(),
            $this->checkStorage(),
            $this->checkMail(),
            $this->checkWhatsAppGateway(),
            $this->checkGenieAcs(),
            $this->checkMikrotik(),
            $this->checkDuitku(),
            $this->checkTelegram(),
        ];

        $hasCritical = collect($checks)->contains(fn (array $c) => $c['status'] === 'critical');
        $hasWarning = collect($checks)->contains(fn (array $c) => $c['status'] === 'warning');

        foreach ($checks as $check) {
            $icon = match ($check['status']) {
                'ok' => '✓',
                'warning' => '!',
                default => '✗',
            };

            $message = trim((string) ($check['message'] ?? ''));
            $line = $icon.' '.$check['name'];
            if ($message !== '') {
                $line .= ' — '.$message;
            }
            $this->line($line);
        }

        $this->newLine();

        $final = $hasCritical ? 'CRITICAL' : ($hasWarning ? 'WARNING' : 'HEALTHY');
        $this->info('Status akhir: '.$final);

        return $hasCritical ? 2 : ($hasWarning ? 1 : 0);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return ['name' => 'Database', 'status' => 'ok', 'message' => config('database.default')];
        } catch (\Throwable $e) {
            return ['name' => 'Database', 'status' => 'critical', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        $defaultCache = (string) config('cache.default');
        $queueDefault = (string) config('queue.default');

        if (! in_array($defaultCache, ['redis'], true) && ! in_array($queueDefault, ['redis'], true)) {
            return ['name' => 'Redis', 'status' => 'warning', 'message' => 'Tidak digunakan sebagai cache/queue default'];
        }

        try {
            $pong = Redis::connection()->command('ping');
            if ((string) $pong !== 'PONG') {
                return ['name' => 'Redis', 'status' => 'warning', 'message' => 'Ping tidak mengembalikan PONG'];
            }

            return ['name' => 'Redis', 'status' => 'ok', 'message' => 'PONG'];
        } catch (\Throwable $e) {
            return ['name' => 'Redis', 'status' => 'critical', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        $driver = (string) config('queue.default');
        if ($driver === 'sync') {
            return ['name' => 'Queue', 'status' => 'critical', 'message' => 'queue.default=sync'];
        }

        if ($driver === 'redis') {
            try {
                $pong = Redis::connection()->command('ping');
                if ((string) $pong !== 'PONG') {
                    return ['name' => 'Queue', 'status' => 'warning', 'message' => 'Redis tidak merespon PONG'];
                }

                return ['name' => 'Queue', 'status' => 'ok', 'message' => 'redis'];
            } catch (\Throwable $e) {
                return ['name' => 'Queue', 'status' => 'critical', 'message' => $e->getMessage()];
            }
        }

        if ($driver === 'database') {
            $hasTable = Schema::hasTable('jobs');
            return ['name' => 'Queue', 'status' => $hasTable ? 'ok' : 'critical', 'message' => $hasTable ? 'database' : 'Tabel jobs tidak ditemukan'];
        }

        return ['name' => 'Queue', 'status' => 'warning', 'message' => $driver];
    }

    private function checkSchedulerHeartbeat(): array
    {
        try {
            $value = Cache::get('mstore.scheduler.heartbeat_at');
            if (! is_string($value) || trim($value) === '') {
                return ['name' => 'Scheduler', 'status' => 'warning', 'message' => 'Heartbeat belum terdeteksi'];
            }

            $ts = Carbon::parse($value);
            $ageSeconds = $ts->diffInSeconds(now());

            if ($ageSeconds <= 180) {
                return ['name' => 'Scheduler', 'status' => 'ok', 'message' => 'last='.$ts->toDateTimeString()];
            }

            if ($ageSeconds <= 600) {
                return ['name' => 'Scheduler', 'status' => 'warning', 'message' => 'last='.$ts->toDateTimeString()];
            }

            return ['name' => 'Scheduler', 'status' => 'critical', 'message' => 'last='.$ts->toDateTimeString()];
        } catch (\Throwable $e) {
            return ['name' => 'Scheduler', 'status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $path = 'healthchecks/.probe';
            $disk->put($path, now()->toDateTimeString());
            $disk->delete($path);

            return ['name' => 'Storage', 'status' => 'ok', 'message' => 'local'];
        } catch (\Throwable $e) {
            return ['name' => 'Storage', 'status' => 'critical', 'message' => $e->getMessage()];
        }
    }

    private function checkMail(): array
    {
        $driver = (string) config('mail.default');
        if ($driver === '' || $driver === 'log' || $driver === 'array') {
            return ['name' => 'Mail', 'status' => 'warning', 'message' => $driver !== '' ? $driver : 'Tidak dikonfigurasi'];
        }

        return ['name' => 'Mail', 'status' => 'ok', 'message' => $driver];
    }

    private function checkWhatsAppGateway(): array
    {
        $url = trim((string) Setting::getValue('whatsapp_api_url', config('services.whatsapp.url')));
        $key = trim((string) Setting::getValue('whatsapp_api_key', config('services.whatsapp.key')));

        if ($url === '' || $key === '') {
            return ['name' => 'WhatsApp Gateway', 'status' => 'warning', 'message' => 'Konfigurasi belum lengkap'];
        }

        try {
            $res = Http::timeout(3)->withoutVerifying()->get(rtrim($url, '/').'/');
            if ($res->successful() || $res->status() === 401 || $res->status() === 403) {
                return ['name' => 'WhatsApp Gateway', 'status' => 'ok', 'message' => 'reachable'];
            }

            return ['name' => 'WhatsApp Gateway', 'status' => 'warning', 'message' => 'HTTP '.$res->status()];
        } catch (\Throwable $e) {
            return ['name' => 'WhatsApp Gateway', 'status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    private function checkGenieAcs(): array
    {
        $server = GenieAcsServer::query()->where('is_active', true)->first();
        $baseUrl = $server ? rtrim((string) $server->url, '/') : '';
        if ($baseUrl === '') {
            $baseUrl = rtrim((string) config('services.genieacs.url', ''), '/');
        }

        if ($baseUrl === '') {
            return ['name' => 'GenieACS', 'status' => 'warning', 'message' => 'URL belum dikonfigurasi'];
        }

        try {
            $res = Http::timeout(3)->acceptJson()->get($baseUrl.'/devices', ['limit' => 1]);
            if ($res->successful() || $res->status() === 401 || $res->status() === 403) {
                return ['name' => 'GenieACS', 'status' => 'ok', 'message' => 'reachable'];
            }

            return ['name' => 'GenieACS', 'status' => 'warning', 'message' => 'HTTP '.$res->status()];
        } catch (\Throwable $e) {
            return ['name' => 'GenieACS', 'status' => 'critical', 'message' => $e->getMessage()];
        }
    }

    private function checkMikrotik(): array
    {
        $router = Router::query()->orderBy('id')->first();
        if (! $router) {
            return ['name' => 'MikroTik', 'status' => 'warning', 'message' => 'Belum ada router'];
        }

        try {
            $svc = new MikrotikService($router);
            if (! $svc->isConnected()) {
                return ['name' => 'MikroTik', 'status' => 'warning', 'message' => 'Tidak bisa konek ke '.$router->name];
            }

            return ['name' => 'MikroTik', 'status' => 'ok', 'message' => $router->name];
        } catch (\Throwable $e) {
            return ['name' => 'MikroTik', 'status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    private function checkDuitku(): array
    {
        $merchantCode = trim((string) Setting::getValue('duitku_merchant_code', config('services.duitku.merchant_code')));
        $apiKey = trim((string) Setting::getValue('duitku_api_key', config('services.duitku.api_key')));

        if ($merchantCode === '' || $apiKey === '') {
            return ['name' => 'Duitku', 'status' => 'warning', 'message' => 'Konfigurasi belum lengkap'];
        }

        return ['name' => 'Duitku', 'status' => 'ok', 'message' => 'configured'];
    }

    private function checkTelegram(): array
    {
        $botToken = trim((string) Setting::getValue('telegram_bot_token', config('services.telegram.bot_token', '')));
        $chatId = trim((string) Setting::getValue('telegram_escalation_chat_id', ''));

        if ($botToken === '') {
            return ['name' => 'Telegram', 'status' => 'warning', 'message' => 'Bot token belum dikonfigurasi'];
        }

        if ($chatId === '') {
            return ['name' => 'Telegram', 'status' => 'warning', 'message' => 'Chat ID belum dikonfigurasi'];
        }

        return ['name' => 'Telegram', 'status' => 'ok', 'message' => 'configured'];
    }
}

