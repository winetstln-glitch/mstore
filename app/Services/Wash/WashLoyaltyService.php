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

    /**
     * Get the bonus mode:
     * - voucher (default): create a voucher on Nth visit, redeem on next visit
     * - instant_discount: directly apply 100% discount ON the Nth visit transaction itself (legacy)
     */
    public function bonusMode(): string
    {
        $mode = 'voucher';
        try {
            $val = strtolower(trim((string) Setting::getValue('wash_loyalty_bonus_mode', 'voucher')));
            if (in_array($val, ['voucher', 'instant_discount', 'instant', 'legacy'], true)) {
                $mode = ($val === 'instant_discount' || $val === 'instant' || $val === 'legacy') ? 'instant_discount' : 'voucher';
            }
        } catch (\Throwable) {
            $mode = 'voucher';
        }

        return $mode;
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

    public function vehicleTypeMatchesReward(?string $vehicleType, ?string $rewardType): bool
    {
        $vt = strtolower(trim((string) $vehicleType));
        $rt = strtolower(trim((string) $rewardType));

        if ($rt === '' || $rt === 'free_wash') {
            return true;
        }
        if ($rt === 'free_wash_car' && $vt === 'car') {
            return true;
        }
        if ($rt === 'free_wash_motor' && $vt === 'motor') {
            return true;
        }

        return false;
    }

    public function rewardTypeLabel(?string $rewardType): string
    {
        return match (strtolower(trim((string) $rewardType))) {
            'free_wash_car' => 'Gratis Cuci Mobil',
            'free_wash_motor' => 'Gratis Cuci Motor',
            default => 'Gratis 1x Cuci',
        };
    }

    public function detectRewardTypeForPlate(string $vehiclePlate, WashTransaction $contextTx = null): string
    {
        $vehicleType = null;
        if ($contextTx && $contextTx->relationLoaded('items') && $contextTx->items->count() > 0) {
            $firstSvc = $contextTx->items->first()?->service;
            if ($firstSvc) {
                $vehicleType = strtolower(trim((string) ($firstSvc->vehicle_type ?? '')));
            }
        }
        if ($vehicleType === 'car') return 'free_wash_car';
        if ($vehicleType === 'motor') return 'free_wash_motor';

        if (Schema::hasTable('wash_transactions') && Schema::hasTable('wash_services')) {
            try {
                $histSvc = \App\Models\WashTransactionItem::query()
                    ->select('ws.vehicle_type')
                    ->join('wash_transactions as wt', 'wt.id', '=', 'wash_transaction_items.wash_transaction_id')
                    ->join('wash_services as ws', 'ws.id', '=', 'wash_transaction_items.wash_service_id')
                    ->whereRaw('TRIM(UPPER(wt.vehicle_plate)) = ?', [strtoupper(trim($vehiclePlate))])
                    ->orderByDesc('wt.created_at')
                    ->limit(1)
                    ->value('ws.vehicle_type');
                $vt = strtolower(trim((string) ($histSvc ?? '')));
                if ($vt === 'car') return 'free_wash_car';
                if ($vt === 'motor') return 'free_wash_motor';
            } catch (\Throwable $e) { /* ignore */ }
        }

        return 'free_wash';
    }

    public function issueManualVoucher(string $vehiclePlate, ?string $reason = null, WashTransaction $contextTx = null, string $rewardType = null, ?int $expiresDays = 90): WashRewardVoucher
    {
        if (! Schema::hasTable('wash_reward_vouchers')) {
            abort(422, 'Voucher reward belum tersedia. Jalankan migrasi database.');
        }
        $plate = $this->normalizePlate($vehiclePlate);
        if ($plate === '') {
            abort(422, 'Plat nomor wajib diisi untuk membuat voucher manual.');
        }
        $rt = $rewardType ?: $this->detectRewardTypeForPlate($plate, $contextTx);

        return DB::transaction(function () use ($plate, $rt, $reason, $expiresDays) {
            $now = now();
            $counter = WashLoyaltyCounter::query()
                ->where('vehicle_plate', $plate)
                ->lockForUpdate()
                ->first();
            if (! $counter) {
                $counter = WashLoyaltyCounter::create([
                    'vehicle_plate' => $plate,
                    'cycle_paid_count' => 0,
                    'lifetime_paid_count' => 0,
                ]);
            }

            $code = 'GW-MANUAL-' . strtoupper(substr(md5(uniqid((string) $plate . mt_rand(), true)), 0, 10));
            $expires = $expiresDays && $expiresDays > 0 ? $now->copy()->addDays($expiresDays) : null;

            $voucher = WashRewardVoucher::create([
                'code' => $code,
                'wash_loyalty_counter_id' => $counter->id,
                'wash_customer_id' => $counter->wash_customer_id,
                'wash_member_id' => $counter->wash_member_id,
                'vehicle_plate' => $plate,
                'reward_type' => $rt,
                'source' => 'manual',
                'source_reason' => $reason,
                'status' => 'available',
                'expires_at' => $expires,
                'issued_at' => $now,
            ]);

            $this->auditLog->logAction('wash_loyalty.manual_voucher_issued', $voucher, [
                'vehicle_plate' => $plate,
                'reward_type' => $rt,
                'source_reason' => $reason,
                'expires_at' => $expires?->toIso8601String(),
            ]);

            return $voucher;
        });
    }

    public function rollbackLastIncrement(WashTransaction $transaction): array
    {
        if (! Schema::hasTable('wash_loyalty_counters')) {
            return ['success' => false, 'message' => 'wash_loyalty_counters table not found'];
        }
        $plate = $this->normalizePlate($transaction->vehicle_plate);
        if ($plate === '') {
            return ['success' => false, 'message' => 'Plat nomor kosong'];
        }

        return DB::transaction(function () use ($transaction, $plate) {
            $counter = WashLoyaltyCounter::query()
                ->where('vehicle_plate', $plate)
                ->lockForUpdate()
                ->first();
            if (! $counter) {
                return ['success' => false, 'message' => 'Counter tidak ditemukan untuk plat ini'];
            }

            if ((int) $counter->last_paid_transaction_id !== (int) $transaction->id) {
                // Bukan increment terakhir, tapi masih boleh rollback jika user memaksa (tetap kurangi 1, cuma tidak "last")
            }

            $newCycle = max(0, ((int) $counter->cycle_paid_count) - 1);
            $newLifetime = max(0, ((int) $counter->lifetime_paid_count) - 1);

            // Jika rollback menyebabkan target tercapai ter-UNDO, hapus voucher terakhir yang status=available dari plat ini (created_at <= transaction_created + 2 menit)
            $undoVoucher = null;
            $issuedVouchers = WashRewardVoucher::query()
                ->where('vehicle_plate', $plate)
                ->where('status', 'available')
                ->where('source', '!=', 'manual')
                ->where('issued_at', '>=', $transaction->created_at->copy()->subSecond(30))
                ->where('issued_at', '<=', $transaction->created_at->copy()->addMinutes(2))
                ->orderByDesc('id')
                ->limit(1)
                ->get();

            foreach ($issuedVouchers as $vc) {
                // Hanya revoke jika target baru (cycle_paid_count SEBELUM rollback = target). Setelah rollback cycle = target-1
                $prevCycle = (int) $counter->cycle_paid_count;
                $target = $this->target();
                if ($prevCycle === 0 && $target > 0) {
                    // Cycle baru saja di-reset → berarti voucher baru saja dibuat di transaksi INI. Revoke.
                    $vc->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'revoked_reason' => 'rollback_transaction:' . $transaction->id,
                    ]);
                    $undoVoucher = $vc->code;
                    $this->auditLog->logAction('wash_loyalty.voucher_revoked', $vc, [
                        'reason' => 'rollback_transaction',
                        'transaction_id' => $transaction->id,
                    ]);
                    // Karena cycle sebelumnya = 0 (baru di-reset), kembalikan jadi target-1
                    $newCycle = max(0, $target - 1);
                    break;
                }
            }

            $counter->cycle_paid_count = $newCycle;
            $counter->lifetime_paid_count = $newLifetime;
            if ((int) $counter->last_paid_transaction_id === (int) $transaction->id) {
                $counter->last_paid_transaction_id = null;
                $counter->last_paid_at = null;
            }
            $counter->save();

            // Jika transaction punya loyalty_counted_at, tandai sebagai tidak dihitung
            if (Schema::hasColumn('wash_transactions', 'loyalty_counted_at')) {
                $transaction->update(['loyalty_counted_at' => null]);
            }

            $this->auditLog->logAction('wash_loyalty.counter_rollback', $counter, [
                'transaction_id' => $transaction->id,
                'new_cycle' => $newCycle,
                'new_lifetime' => $newLifetime,
                'revoked_voucher' => $undoVoucher,
            ]);

            return [
                'success' => true,
                'new_cycle' => $newCycle,
                'new_lifetime' => $newLifetime,
                'revoked_voucher' => $undoVoucher,
                'message' => $undoVoucher
                    ? 'Rollback sukses. Voucher ' . $undoVoucher . ' telah di-revoke (cycle dikembalikan ke ' . $newCycle . ').'
                    : 'Rollback sukses. Counter dikurangi 1.',
            ];
        });
    }

    public function retroactivelyApplyAsBonus(WashTransaction $transaction, string $mode = 'retro_voucher'): array
    {
        // Dua mode:
        // 'retro_voucher' = Buat total transaksi ini = Rp0 (set discount 100%) DAN tandai notes=reward_voucher, LALU buat & auto-redeem voucher MANUAL
        // 'retro_settle'   = Hanya set discount 100% + notes = bonus_cuci_Nx. Tidak ubah counter (asumsi counter sudah benar sebelumnya).
        //                    Digunakan jika user LUPA memakai voucher di POS, tapi transaksi 11x terlanjur dibayar.
        if (! in_array($mode, ['retro_voucher', 'retro_settle'])) {
            $mode = 'retro_settle';
        }

        $plate = $this->normalizePlate($transaction->vehicle_plate);
        $gross = 0;
        foreach ($transaction->items as $it) {
            $gross += (float) ($it->price ?? 0) * (int) ($it->quantity ?? 0);
        }
        if ($gross <= 0) {
            return ['success' => false, 'message' => 'Total gross transaksi 0, tidak perlu retroactive bonus.'];
        }

        $target = $this->target();

        return DB::transaction(function () use ($transaction, $mode, $plate, $gross, $target) {
            $redeemedCode = null;

            if ($mode === 'retro_voucher') {
                // 1. Rollback loyalty counter untuk transaksi INI (karena nanti setelah marked as bonus, isRedemption=true → seharusnya tidak dihitung)
                $counter = WashLoyaltyCounter::query()->where('vehicle_plate', $plate)->first();
                if ($counter && Schema::hasColumn('wash_transactions', 'loyalty_counted_at') && $transaction->loyalty_counted_at) {
                    $this->rollbackLastIncrement($transaction);
                }
                // 2. Issue manual voucher
                $voucher = $this->issueManualVoucher($plate, 'retroactive_bonus:transaction_' . $transaction->id, $transaction, null, 365);
                $redeemedCode = $voucher->code;

                // 3. Auto-redeem voucher ke transaksi INI
                $voucher->update([
                    'status' => 'used',
                    'used_at' => now(),
                    'used_wash_transaction_id' => $transaction->id,
                ]);
                if (Schema::hasTable('wash_reward_redemptions')) {
                    \App\Models\WashRewardRedemption::create([
                        'wash_reward_voucher_id' => $voucher->id,
                        'wash_transaction_id' => $transaction->id,
                        'redeemed_by_user_id' => Auth::id(),
                        'amount' => (int) round($gross),
                        'redeemed_at' => now(),
                        'notes' => 'retroactive redemption',
                    ]);
                }
                $this->auditLog->logAction('wash_loyalty.retroactive_redeem', $transaction, [
                    'voucher_code' => $voucher->code,
                    'vehicle_plate' => $plate,
                    'gross' => $gross,
                ]);
            }

            // 4. Update transaksi: discount 100%, total_amount=0, notes sesuai mode
            $newNotes = $mode === 'retro_voucher'
                ? 'reward_voucher:retro_' . $redeemedCode
                : 'bonus_cuci_' . $target . 'x:retro_settle';

            $updateData = [
                'discount_type' => $mode === 'retro_voucher' ? 'reward_voucher' : 'manual_bonus',
                'discount_amount' => $gross,
                'notes' => $transaction->notes ? ($transaction->notes . ' | ' . $newNotes) : $newNotes,
                'total_amount' => 0,
                'change_amount' => $transaction->payment_method === 'cash'
                    ? max(0, (float) ($transaction->cash_amount ?? 0))
                    : 0,
            ];
            $transaction->update($updateData);

            // 5. Jika sudah dibuat jurnal, update juga (opsional: best effort)
            try {
                $journals = \App\Models\Journal::where('source_type', 'wash_transaction')
                    ->where('source_id', $transaction->id)
                    ->with('entries')
                    ->get();
                foreach ($journals as $journal) {
                    foreach ($journal->entries as $entry) {
                        if ((float) $entry->debit > 0) {
                            $entry->update(['debit' => 0, 'credit' => 0]);
                        } elseif ((float) $entry->credit > 0) {
                            $entry->update(['debit' => 0, 'credit' => 0]);
                        }
                    }
                }
            } catch (\Throwable $e) { /* ignore journal errors, core logic already correct */ }

            return [
                'success' => true,
                'mode' => $mode,
                'new_total' => 0,
                'redeemed_code' => $redeemedCode,
                'message' => $mode === 'retro_voucher'
                    ? 'Retroactive voucher: Transaksi dibuat Rp0, voucher ' . $redeemedCode . ' auto-redeemed.'
                    : 'Retroactive settle: Transaksi dibuat Rp0 (discount manual), tanpa ubah counter loyalty.',
            ];
        });
    }

    public function checkInstantBonusEligibility(string $vehiclePlate): array
    {
        $mode = $this->bonusMode();
        $result = [
            'mode' => $mode,
            'eligible' => false,
            'target' => $this->target(),
            'progress' => 0,
            'remaining' => $this->target(),
            'note' => null,
        ];
        $plate = $this->normalizePlate($vehiclePlate);
        if ($plate === '' || ! Schema::hasTable('wash_loyalty_counters')) {
            return $result;
        }
        $counter = WashLoyaltyCounter::query()->where('vehicle_plate', $plate)->first();
        if (! $counter) {
            return $result;
        }
        $target = $this->target();
        $cycle = (int) ($counter->cycle_paid_count ?? 0);
        $progress = $cycle % $target;
        if ($progress === 0) {
            $remaining = $target;
        } else {
            $remaining = $target - $progress;
        }
        $result['progress'] = $progress;
        $result['remaining'] = $remaining;
        if ($remaining === 1) {
            $result['eligible'] = true;
            if ($mode === 'instant_discount') {
                $result['note'] = 'Transaksi ini adalah kunjungan ke-'.$target.' → GRATIS (instant discount).';
            } else {
                $result['note'] = 'Transaksi ini adalah kunjungan ke-'.$target.' → GRATIS (voucher otomatis diterbitkan & langsung dipakai).';
            }
        } else {
            if ($mode !== 'instant_discount') {
                $result['note'] = 'Mode loyalty saat ini adalah voucher (dapat voucher & langsung dipakai di kunjungan ke-'.$target.').';
            }
        }

        return $result;
    }

    /**
     * Check if a transaction is a reward/bonus redemption (should NOT count toward loyalty)
     */
    public function isRedemptionTransaction(WashTransaction $transaction): bool
    {
        $notes = strtolower(trim((string) ($transaction->notes ?? '')));
        $paymentMethod = strtolower(trim((string) ($transaction->payment_method ?? '')));
        $totalAmount = (float) ($transaction->total_amount ?? 0);
        $discountAmount = (float) ($transaction->discount_amount ?? 0);

        // 🔥 Pengecualian AUTO-REDEEM: ini adalah transaksi ke-N (target) yang menyebabkan voucher
        // terbit & langsung dipakai. Counter tetap harus dihitung (increment+reset), jadi TIDAK BOLEH
        // dianggap sebagai redemption biasa. Ditandai dengan prefix "auto_reward_voucher:"
        if (str_starts_with($notes, 'auto_reward_voucher')) {
            return false;
        }

        // 1. Transaction notes indicate reward voucher / bonus wash / instant bonus
        if (str_starts_with($notes, 'reward_voucher')) {
            return true;
        }
        if (str_starts_with($notes, 'bonus_cuci') || str_starts_with($notes, 'voucher_free') || str_starts_with($notes, 'instant_bonus')) {
            return true;
        }

        // 2. Paid using "voucher" payment method (and total = 0, indicating free wash)
        //    Pengecualian: notes = auto_reward_voucher (sudah ditangani di atas)
        if ($paymentMethod === 'voucher') {
            return true;
        }

        // 3. Has a corresponding WashRewardRedemption record
        //    (kecuali auto-redeem yang merupakan transaksi asal dari voucher tsb)
        if (Schema::hasTable('wash_reward_redemptions') && method_exists($transaction, 'redemption')) {
            try {
                $hasRedemption = $transaction->redemption()->exists();
                if ($hasRedemption) {
                    // Jika ini transaksi auto-redeem origin, izinkan agar counter jalan
                    // (biasanya sudah ter-filter oleh notes di atas, tapi safety net di sini)
                    return true;
                }
            } catch (\Throwable $e) {
                // ignore relationship errors
            }
        }

        // 4. 100% DISCOUNT FREE WASH SAFETY NET:
        // If discount is >= total amount AND we detect a LOYALTY/bonus pattern in notes
        if ($totalAmount <= 0 && $discountAmount > 0 && $notes !== '') {
            // Use a more specific regex to avoid false positives (e.g. "tambah poles cuci kaca" should NOT match)
            if (preg_match('/(bonus|reward|voucher|loyalty|gratis|instant).*(cuci|wash|mobil|motor|kendaraan)/i', $notes) ||
                preg_match('/(cuci|wash|mobil|motor|kendaraan).*(bonus|reward|voucher|loyalty|gratis|instant)/i', $notes)) {
                return true;
            }
        }

        return false;
    }

    public function incrementOnPaidTransaction(WashTransaction $transaction, bool $forceCount = false, bool $autoRedeemCreatedVoucher = false): array
    {
        if (! Schema::hasTable('wash_loyalty_counters')) {
            return ['created_voucher' => null, 'progress' => null, 'instant_bonus_applied' => false, 'redeemed_voucher_code' => null];
        }

        $plate = $this->normalizePlate($transaction->vehicle_plate);
        if ($plate === '') {
            return ['created_voucher' => null, 'progress' => null, 'instant_bonus_applied' => false, 'redeemed_voucher_code' => null];
        }

        $status = strtolower((string) $transaction->status);
        if (! in_array($status, ['lunas', 'posted'])) {
            return ['created_voucher' => null, 'progress' => null, 'instant_bonus_applied' => false, 'redeemed_voucher_code' => null];
        }

        $notes = strtolower(trim((string) ($transaction->notes ?? '')));
        $isInstantBonusNote = str_starts_with($notes, 'instant_bonus_');

        if (((float) $transaction->total_amount) <= 0 && ! $isInstantBonusNote) {
            return ['created_voucher' => null, 'progress' => null, 'instant_bonus_applied' => false, 'redeemed_voucher_code' => null];
        }

        // Never count redemptions/bonus transactions toward the bonus!
        // EXCEPTION: instant_bonus_* notes — this visit is the TARGET visit (cycle closer).
        // It must be counted (increment + reset) so the cycle closes correctly (no double-free).
        // This applies even when $forceCount = true (they are NOT visits!).
        if (! $isInstantBonusNote && $this->isRedemptionTransaction($transaction)) {
            $counter = $this->getOrCreateCounter($transaction->washCustomer, $plate);
            return [
                'created_voucher' => null,
                'progress' => $this->progress($counter),
                'redeemed_voucher_code' => null,
            ];
        }

        // Check if this transaction has any NON-coffee items
        // Use the relationship first, but fall back to checking the stored service name
        // (in case the service has been deleted or wash_service_id is missing)
        $hasNonCoffee = $transaction->items()
            ->whereHas('service', function ($q) {
                $q->where('vehicle_type', '!=', 'coffee');
            })
            ->exists();

        if (! $hasNonCoffee) {
            // Fallback: check each item's stored service name
            $items = $transaction->items;
            foreach ($items as $item) {
                $isCoffee = false;
                $service = $item->service;
                if ($service && strtolower((string) ($service->vehicle_type ?? '')) === 'coffee') {
                    $isCoffee = true;
                } else {
                    $serviceName = strtolower(trim((string) ($item->service_name ?? '')));
                    if (
                        $serviceName === 'kopi' ||
                        $serviceName === 'caffe' ||
                        $serviceName === 'warkop' ||
                        str_contains($serviceName, 'kopi') ||
                        str_contains($serviceName, 'caffe') ||
                        str_contains($serviceName, 'warkop')
                    ) {
                        $isCoffee = true;
                    }
                }
                if (! $isCoffee) {
                    $hasNonCoffee = true;
                    break;
                }
            }
        }

        if (! $hasNonCoffee) {
            return ['created_voucher' => null, 'progress' => null, 'instant_bonus_applied' => false, 'redeemed_voucher_code' => null];
        }

        // Check if transaction has already been counted
        if (! $forceCount && Schema::hasColumn('wash_transactions', 'loyalty_counted_at') && $transaction->loyalty_counted_at) {
            // Already counted, just return current progress
            $counter = $this->getOrCreateCounter($transaction->washCustomer, $plate);
            return [
                'created_voucher' => null,
                'progress' => $this->progress($counter),
                'instant_bonus_applied' => false,
                'redeemed_voucher_code' => null,
            ];
        }

        $target = $this->target();
        $mode = $this->bonusMode();

        return DB::transaction(function () use ($transaction, $plate, $target, $mode, $autoRedeemCreatedVoucher) {
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
                'mode' => $mode,
            ]);

            $createdVoucher = null;
            $instantBonusApplied = false;
            $redeemedVoucherCode = null;
            if (((int) $counter->cycle_paid_count) >= $target) {
                if ($mode === 'instant_discount') {
                    // INSTANT mode: TIDAK buat voucher, reset cycle saja.
                    $instantBonusApplied = true;
                    $counter->cycle_paid_count = 0;
                    $counter->save();

                    $this->auditLog->logAction('wash_loyalty.counter_reset', $counter, [
                        'transaction_id' => $transaction->id,
                        'vehicle_plate' => $plate,
                        'reason' => 'instant_bonus_applied',
                    ]);
                } else {
                    // VOUCHER mode: buat voucher.
                    $createdVoucher = $this->issueVoucherForCounter($counter, $transaction);
                    $counter->cycle_paid_count = 0;
                    $counter->save();

                    $this->auditLog->logAction('wash_loyalty.counter_reset', $counter, [
                        'transaction_id' => $transaction->id,
                        'vehicle_plate' => $plate,
                        'reason' => 'reward_issued',
                    ]);

                    // 🔥 AUTO-REDEEM: Jika diminta ($autoRedeemCreatedVoucher = true),
                    // voucher yang baru dibuat langsung dipakai untuk transaksi INI SENDIRI.
                    // Ini berarti transaksi ke-11 (yang menyebabkan voucher terbit), jadi GRATIS.
                    if ($autoRedeemCreatedVoucher && $createdVoucher instanceof WashRewardVoucher) {
                        $redeemedVoucherCode = $createdVoucher->code;
                        $firstWashItem = $transaction->items()
                            ->whereHas('service', function ($q) {
                                $q->where('vehicle_type', '!=', 'coffee');
                            })
                            ->orderBy('id')
                            ->first();
                        $washItems = $firstWashItem ? [[
                            'wash_service_id' => $firstWashItem->wash_service_id,
                            'id' => $firstWashItem->wash_service_id,
                        ]] : [];
                        try {
                            $totalAmount = (float) $transaction->discount_amount;
                            if ($totalAmount <= 0) {
                                $totalAmount = (float) ($transaction->items->sum(fn($i) => (float)$i->price * (int)$i->quantity) ?? 0);
                            }
                            $this->redeemVoucher($createdVoucher->code, $transaction, max(0, $totalAmount), $washItems);
                        } catch (\Throwable $e) {
                            // Redeem failed: voucher remains available.
                            // Catat di audit log tapi tidak throw (agar transaksi tidak batal).
                            try {
                                $this->auditLog->logAction('wash_loyalty.auto_redeem_failed', $transaction, [
                                    'voucher_code' => $createdVoucher->code,
                                    'error' => $e->getMessage(),
                                ]);
                            } catch (\Throwable) {}
                            $redeemedVoucherCode = null;
                        }
                    }
                }
            }

            $progress = $this->progress($counter);

            return [
                'created_voucher' => $createdVoucher,
                'progress' => $progress,
                'instant_bonus_applied' => $instantBonusApplied,
                'redeemed_voucher_code' => $redeemedVoucherCode,
            ];
        });
    }

    public function redeemVoucher(string $code, WashTransaction $transaction, float $totalAmount, array $washItems = []): WashRewardVoucher
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

        return DB::transaction(function () use ($cleanCode, $transaction, $plate, $totalAmount, $washItems) {
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

            // VALIDASI TIPE KENDARAAN vs reward_type voucher
            if (count($washItems) > 0) {
                $firstItemId = $washItems[0]['wash_service_id'] ?? $washItems[0]['id'] ?? null;
                if ($firstItemId) {
                    $service = \App\Models\WashService::query()->find($firstItemId);
                    if ($service && ! $this->vehicleTypeMatchesReward($service->vehicle_type, $voucher->reward_type)) {
                        $allowedLabel = $this->rewardTypeLabel($voucher->reward_type);
                        abort(422, "Voucher ini hanya berlaku untuk: {$allowedLabel}.");
                    }
                }
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
                'reward_type' => $voucher->reward_type,
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
