<?php

namespace App\Services;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public function generateBatch(string $profile, ?int $durationSeconds, ?int $quotaMb, int $count, bool $passwordSameAsUsername = true, ?int $userId = null): VoucherBatch
    {
        $batch = VoucherBatch::create([
            'batch_code' => strtoupper(Str::random(10)),
            'profile' => $profile,
            'duration_seconds' => $durationSeconds,
            'quota_mb' => $quotaMb,
            'total_vouchers' => $count,
            'created_by' => $userId,
        ]);

        DB::transaction(function () use ($batch, $profile, $durationSeconds, $quotaMb, $count, $passwordSameAsUsername) {
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
            }
        });

        return $batch->fresh();
    }

    public function disconnectUser(string $username): bool
    {
        $routers = Router::query()->get();
        $success = false;

        foreach ($routers as $router) {
            $svc = new MikrotikService($router);
            if (! $svc->isConnected()) {
                continue;
            }
            $ok = $svc->killActive($username);
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
