<?php

namespace App\Services;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Network\Services\MonitoringService;

class VoucherService
{
    public function __construct(protected MonitoringService $monitoringService)
    {
    }

    public function generateBatch(string $profile, ?int $durationSeconds, ?int $quotaMb, int $count, bool $passwordSameAsUsername = true, ?int $userId = null): VoucherBatch
    {
        $useRadius = Setting::getValue('use_radius_for_vouchers', '1') === '1';
        
        $limitUptime = $this->secondsToMikrotikUptime($durationSeconds);
        $limitBytesTotal = $quotaMb ? $quotaMb * 1024 * 1024 : null; // MB to bytes

        $batch = VoucherBatch::create([
            'batch_code' => strtoupper(Str::random(10)),
            'profile' => $profile,
            'duration_seconds' => $durationSeconds,
            'quota_mb' => $quotaMb,
            'total_vouchers' => $count,
            'created_by' => $userId,
        ]);

        DB::transaction(function () use ($batch, $profile, $durationSeconds, $quotaMb, $count, $passwordSameAsUsername, $useRadius, $limitUptime, $limitBytesTotal) {
            $routers = Router::where('is_active', true)->get();
            
            for ($i = 0; $i < $count; $i++) {
                $username = strtoupper(Str::random(9));
                $password = $passwordSameAsUsername ? $username : strtoupper(Str::random(9));

                $voucher = Voucher::create([
                    'batch_id' => $batch->id,
                    'username' => $username,
                    'password' => $password,
                    'profile' => $profile,
                    'duration_seconds' => $durationSeconds,
                    'quota_mb' => $quotaMb,
                    'status' => 'unused',
                ]);

                if ($useRadius) {
                    RadCheck::create([
                        'username' => $voucher->username,
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $voucher->password ?? $voucher->username,
                    ]);

                    if ($profile) {
                        RadReply::create([
                            'username' => $voucher->username,
                            'attribute' => 'Mikrotik-Rate-Limit',
                            'op' => ':=',
                            'value' => $profile,
                        ]);
                    }

                    if ($durationSeconds) {
                        RadReply::create([
                            'username' => $voucher->username,
                            'attribute' => 'Session-Timeout',
                            'op' => ':=',
                            'value' => (string) $durationSeconds,
                        ]);
                    }
                } else {
                    // Add to all Mikrotik routers
                    foreach ($routers as $router) {
                        if ($this->monitoringService->isRouterConnected($router)) {
                            $this->monitoringService->createHotspotUser(
                                $router,
                                $voucher->username,
                                $voucher->password,
                                $profile ?: 'default',
                                $limitUptime,
                                $limitBytesTotal
                            );
                        }
                    }
                }
            }
        });

        return $batch->fresh();
    }
    
    protected function secondsToMikrotikUptime(?int $seconds): ?string
    {
        if (!$seconds) {
            return null;
        }
        
        if ($seconds >= 2592000) { // 30 days
            $months = floor($seconds / 2592000);
            $remaining = $seconds % 2592000;
            if ($remaining == 0) {
                return $months . 'mo';
            }
        }
        
        if ($seconds >= 86400) { // 1 day
            $days = floor($seconds / 86400);
            $remaining = $seconds % 86400;
            if ($remaining == 0) {
                return $days . 'd';
            }
        }
        
        if ($seconds >= 3600) { // 1 hour
            $hours = floor($seconds / 3600);
            $remaining = $seconds % 3600;
            if ($remaining == 0) {
                return $hours . 'h';
            }
        }
        
        if ($seconds >= 60) { // 1 minute
            $minutes = floor($seconds / 60);
            $remaining = $seconds % 60;
            if ($remaining == 0) {
                return $minutes . 'm';
            }
        }
        
        return $seconds . 's';
    }

    public function disconnectUser(string $username): bool
    {
        $routers = Router::query()->get();
        $success = false;

        foreach ($routers as $router) {
            if (! $this->monitoringService->isRouterConnected($router)) {
                continue;
            }
            $ok = $this->monitoringService->killActive($router, $username);
            $success = $success || $ok;
        }

        if (! $success) {
            DB::table('queue_jobs')->insert([
                'type' => 'mikrotik_disconnect',
                'payload' => json_encode(['username' => $username]),
                'status' => 'queued',
                'attempts' => 0,
                'last_error' => 'All router disconnect attempts failed.',
                'scheduled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $success;
    }

    public function reapplyProfile(string $username): bool
    {
        $voucher = Voucher::query()->where('username', $username)->first();
        if (! $voucher) {
            return false;
        }

        if ($voucher->profile) {
            RadReply::query()->updateOrCreate(
                ['username' => $voucher->username, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => $voucher->profile]
            );
        }

        if ($voucher->duration_seconds) {
            RadReply::query()->updateOrCreate(
                ['username' => $voucher->username, 'attribute' => 'Session-Timeout'],
                ['op' => ':=', 'value' => (string) $voucher->duration_seconds]
            );
        }

        return $this->disconnectUser($username);
    }
}
