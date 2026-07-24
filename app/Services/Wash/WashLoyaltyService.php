<?php

namespace App\Services\Wash;

use App\Models\Setting;
use App\Models\WashCustomer;
use App\Models\WashLoyaltyCounter;
use App\Models\WashRewardRedemption;
use App\Models\WashRewardVoucher;
use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Services\AuditLogService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WashLoyaltyService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly WhatsAppService $whatsApp
    ) {
    }

    public function target(): int
    {
        $target = 11;
        try {
            $target = (int) Setting::getValue('wash_loyalty_target', 11);
        } catch (\Throwable) {
            $target = 11;
        }
        if ($target < 1) {
            $target = 11;
        }

        return $target;
    }

    public function voucherExpiryDays(): int
    {
        $days = 60;
        try {
            $days = (int) Setting::getValue('wash_reward_voucher_expiry_days', 60);
        } catch (\Throwable) {
            $days = 60;
        }
        if ($days < 1) {
            $days = 60;
        }

        return $days;
    }

    public function normalizePlate(?string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $plate));
    }

    public function progress(WashLoyaltyCounter $counter): array
    {
        $target = $this->target();
        $cycle = (int) ($counter->cycle_paid_count ?? 0);
        $progress = $cycle % $target;
        $remaining = $target - $progress;
        if ($progress === 0) {
            $remaining = $target;
        }

        return [
            'progress' => $progress,
            'target' => $target,
            'remaining' => $remaining,
            'cycle_paid_count' => $cycle,
            'lifetime_paid_count' => (int) ($counter->lifetime_paid_count ?? 0),
        ];
    }

    public function getOrCreateCounter(?WashCustomer $customer, string $vehiclePlate): WashLoyaltyCounter
    {
        $plate = $this->normalizePlate($vehiclePlate);
        return WashLoyaltyCounter::query()->firstOrCreate(
            ['vehicle_plate' => $plate],
            [
                'wash_customer_id' => $customer?->id,
                'wash_member_id' => null,
                'cycle_paid_count' => 0,
                'lifetime_paid_count' => 0,
            ]
        );
    }

    public function availableVouchersForPlate(string $vehiclePlate)
    {
        $plate = $this->normalizePlate($vehiclePlate);
        $now = now();

        return WashRewardVoucher::query()
            ->where('vehicle_plate', $plate)
            ->where('status', 'available')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc('id')
            ->get();
    }

    public function incrementOnPaidTransaction(WashTransaction $transaction): array
    {
        if (! Schema::hasTable('wash_loyalty_counters')) {
            return ['created_voucher' => null, 'progress' => null];
        }

        $plate = $this->normalizePlate($transaction->vehicle_plate);
        if ($plate === '') {
            return ['created_voucher' => null, 'progress' => null];
        }

        $status = strtolower((string) $transaction->status);
        if (! in_array($status, ['lunas', 'posted'])) {
            return ['created_voucher' => null, 'progress' => null];
        }

        if (((float) $transaction->total_amount) <= 0) {
            return ['created_voucher' => null, 'progress' => null];
        }

        $hasNonCoffee = $transaction->items()
            ->whereHas('service', function ($q) {
                $q->where('vehicle_type', '!=', 'coffee');
            })
            ->exists();
        if (! $hasNonCoffee) {
            return ['created_voucher' => null, 'progress' => null];
        }

        // Check if transaction has already been counted
        if (Schema::hasColumn('wash_transactions', 'loyalty_counted_at') && $transaction->loyalty_counted_at) {
            // Already counted, just return current progress
            $counter = $this->getOrCreateCounter($transaction->washCustomer, $plate);
            return [
                'created_voucher' => null,
                'progress' => $this->progress($counter),
            ];
        }

        $target = $this->target();

        return DB::transaction(function () use ($transaction, $plate, $target) {
            $counter = WashLoyaltyCounter::query()
                ->where('vehicle_plate', $plate)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = WashLoyaltyCounter::create([
                    'wash_customer_id' => $transaction->wash_customer_id,
                    'wash_member_id' => $transaction->wash_member_id,
                    'vehicle_plate' => $plate,
                    'cycle_paid_count' => 0,
                    'lifetime_paid_count' => 0,
                ]);
            } elseif (! $counter->wash_customer_id && $transaction->wash_customer_id) {
                $counter->wash_customer_id = $transaction->wash_customer_id;
            }
            if (! $counter->wash_member_id && $transaction->wash_member_id) {
                $counter->wash_member_id = $transaction->wash_member_id;
            }

            $counter->cycle_paid_count = ((int) $counter->cycle_paid_count) + 1;
            $counter->lifetime_paid_count = ((int) $counter->lifetime_paid_count) + 1;
            $counter->last_paid_transaction_id = $transaction->id;
            $counter->last_paid_at = now();
            $counter->save();

            // Mark transaction as counted
            if (Schema::hasColumn('wash_transactions', 'loyalty_counted_at')) {
                $transaction->update(['loyalty_counted_at' => now()]);
            }

            $this->auditLog->logAction('wash_loyalty.counter_incremented', $counter, [
                'transaction_id' => $transaction->id,
                'vehicle_plate' => $plate,
                'cycle_paid_count' => (int) $counter->cycle_paid_count,
                'lifetime_paid_count' => (int) $counter->lifetime_paid_count,
            ]);

            $createdVoucher = null;
            if (((int) $counter->cycle_paid_count) >= $target) {
                $createdVoucher = $this->issueVoucherForCounter($counter, $transaction);
                $counter->cycle_paid_count = 0;
                $counter->save();

                $this->auditLog->logAction('wash_loyalty.counter_reset', $counter, [
                    'transaction_id' => $transaction->id,
                    'vehicle_plate' => $plate,
                    'reason' => 'reward_issued',
                ]);
            }

            $progress = $this->progress($counter);

            return [
                'created_voucher' => $createdVoucher,
                'progress' => $progress,
            ];
        });
    }

    public function redeemVoucher(string $code, WashTransaction $transaction, float $totalAmount): WashRewardVoucher
    {
        if (! Schema::hasTable('wash_reward_vouchers')) {
            abort(422, 'Voucher reward belum tersedia. Jalankan migrasi database.');
        }

        $cleanCode = strtoupper(trim($code));
        if ($cleanCode === '') {
            abort(422, 'Kode voucher wajib diisi.');
        }

        $plate = $this->normalizePlate($transaction->vehicle_plate);
        if ($plate === '') {
            abort(422, 'Plat nomor wajib diisi untuk redeem voucher.');
        }

        return DB::transaction(function () use ($cleanCode, $transaction, $plate, $totalAmount) {
            $voucher = WashRewardVoucher::query()
                ->where('code', $cleanCode)
                ->lockForUpdate()
                ->first();

            if (! $voucher) {
                abort(422, 'Voucher tidak ditemukan.');
            }

            if ($voucher->status !== 'available') {
                abort(422, 'Voucher sudah tidak tersedia.');
            }

            if (! is_null($voucher->expires_at) && $voucher->expires_at->isPast()) {
                $voucher->update([
                    'status' => 'expired',
                ]);
                $this->auditLog->logAction('wash_loyalty.voucher_expired', $voucher, [
                    'vehicle_plate' => $voucher->vehicle_plate,
                ]);
                abort(422, 'Voucher sudah kadaluarsa.');
            }

            if ($voucher->vehicle_plate !== $plate) {
                abort(422, 'Voucher tidak sesuai dengan plat kendaraan.');
            }

            $voucher->update([
                'status' => 'used',
                'used_at' => now(),
                'used_wash_transaction_id' => $transaction->id,
            ]);

            WashRewardRedemption::create([
                'wash_reward_voucher_id' => $voucher->id,
                'wash_transaction_id' => $transaction->id,
                'redeemed_by_user_id' => Auth::id(),
                'amount' => (int) round(max(0, $totalAmount)),
                'redeemed_at' => now(),
            ]);

            $this->auditLog->logAction('wash_loyalty.voucher_used', $voucher, [
                'transaction_id' => $transaction->id,
                'vehicle_plate' => $plate,
                'amount' => (int) round(max(0, $totalAmount)),
            ]);

            return $voucher;
        });
    }

    public function sendProgressWhatsApp(WashTransaction $transaction, array $progress, ?WashRewardVoucher $createdVoucher = null): void
    {
        $phone = $transaction->washCustomer?->phone;
        if (! is_string($phone) || trim($phone) === '') {
            return;
        }

        $customerName = $transaction->customer_name ?: ($transaction->washCustomer?->name ?: 'Pelanggan');
        $target = (int) ($progress['target'] ?? $this->target());
        $current = (int) ($progress['progress'] ?? 0);
        $remaining = (int) ($progress['remaining'] ?? 0);

        if ($createdVoucher) {
            $text = "🎉 Selamat {$customerName}!\n\n"
                ."Anda telah menyelesaikan {$target} kali cuci.\n\n"
                ."Anda mendapatkan:\n🎁 Gratis 1x Cuci\n\n"
                ."Kode Voucher:\n{$createdVoucher->code}";
            $this->whatsApp->sendMessage($phone, $text, 'wash_loyalty', null);
            return;
        }

        $text = "Halo {$customerName} 👋\n\n"
            ."Progress Loyalty Anda:\n\n"
            ."{$current} / {$target}\n\n"
            ."Tinggal {$remaining} kali lagi untuk mendapatkan:\n🎁 Gratis 1x Cuci";

        $this->whatsApp->sendMessage($phone, $text, 'wash_loyalty', null);
    }

    private function issueVoucherForCounter(WashLoyaltyCounter $counter, WashTransaction $transaction): WashRewardVoucher
    {
        $code = $this->nextVoucherCode();
        $expiryDays = $this->voucherExpiryDays();
        $expiresAt = now()->addDays($expiryDays);

        $rewardType = 'free_wash';
        $firstWashItem = $transaction->items()
            ->whereHas('service', function ($q) {
                $q->where('vehicle_type', '!=', 'coffee');
            })
            ->orderBy('id')
            ->first();

        if ($firstWashItem instanceof WashTransactionItem) {
            $vt = strtolower((string) ($firstWashItem->service?->vehicle_type ?? ''));
            if ($vt === 'car') {
                $rewardType = 'free_wash_car';
            } elseif ($vt === 'motor') {
                $rewardType = 'free_wash_motor';
            }
        }

        $voucher = WashRewardVoucher::create([
            'code' => $code,
            'wash_loyalty_counter_id' => $counter->id,
            'wash_customer_id' => $counter->wash_customer_id,
            'wash_member_id' => $counter->wash_member_id,
            'vehicle_plate' => $counter->vehicle_plate,
            'reward_type' => $rewardType,
            'status' => 'available',
            'issued_at' => now(),
            'expires_at' => $expiresAt,
            'meta' => [
                'issued_from_transaction_id' => $transaction->id,
                'target' => $this->target(),
            ],
        ]);

        $this->auditLog->logAction('wash_loyalty.voucher_created', $voucher, [
            'counter_id' => $counter->id,
            'vehicle_plate' => $counter->vehicle_plate,
            'reward_type' => $rewardType,
        ]);

        return $voucher;
    }

    private function nextVoucherCode(): string
    {
        return DB::transaction(function () {
            $maxId = WashRewardVoucher::query()->lockForUpdate()->max('id') ?? 0;
            $next = ((int) $maxId) + 1;
            return 'GW-FREE-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        });
    }
}
