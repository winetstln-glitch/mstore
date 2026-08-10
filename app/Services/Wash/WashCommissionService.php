<?php

namespace App\Services\Wash;

use App\Models\Setting;
use App\Models\WashCommissionEarning;
use App\Models\WashEmployee;
use App\Models\WashService;
use App\Models\WashTransaction;
use App\Models\WashTransactionItem;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WashCommissionService
{
    public function __construct(
        protected AuditLogService $auditLog,
    ) {}

    protected function hasCommissionTable(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $cached = Schema::hasTable('wash_commission_earnings');
        } catch (\Throwable) {
            $cached = false;
        }
        return (bool) $cached;
    }

    public function setting(string $key, $default = null)
    {
        try {
            $val = Setting::where('key', $key)->value('value');
        } catch (\Throwable) {
            $val = null;
        }
        if ($val === null || $val === '') {
            return $default;
        }
        return $val;
    }

    public function defaultRates(): array
    {
        return [
            'car_small_medium'    => (int) ($this->setting('wash_commission_car_small_medium', 13000) ?: 13000),
            'car_large_xlarge'    => (int) ($this->setting('wash_commission_car_large_xlarge', 15000) ?: 15000),
            'motor_small_medium'  => (int) ($this->setting('wash_commission_motor_small_medium', 6000) ?: 6000),
            'motor_large_xlarge'  => (int) ($this->setting('wash_commission_motor_large_xlarge', 8000) ?: 8000),
        ];
    }

    public function isExcludeFreeWash(): bool
    {
        return (bool) ((int) $this->setting('wash_commission_exclude_free_wash', 1) ?: 0);
    }

    public function isOnlyMainServices(): bool
    {
        return (bool) ((int) $this->setting('wash_commission_only_main_services', 1) ?: 0);
    }

    public function isRequireEmployee(): bool
    {
        return (bool) ((int) $this->setting('wash_commission_require_employee', 0) ?: 0);
    }

    public function resolveRateForService(WashService $service = null, ?string $vehicleType = null, ?string $sizeTier = null): int
    {
        $rates = $this->defaultRates();
        $vt = $vehicleType ?? ($service?->vehicle_type ?? null);
        $st = $sizeTier ?? ($service?->size_tier ?? null);

        if (! $vt || $vt === '' || $vt === 'coffee' || $vt === 'none') {
            return 0;
        }

        if (! $st || $st === '' || $st === 'none') {
            // Tidak ada size tier. Pakai yang terkecil untuk jenisnya (anggap "small_medium").
            $st = 'kecil';
        }

        $smallGroup = ['kecil', 'sedang'];
        $largeGroup = ['besar', 'extra_besar'];

        $group = in_array($st, $smallGroup, true) ? 'small_medium'
            : (in_array($st, $largeGroup, true) ? 'large_xlarge' : 'small_medium');

        $key = strtolower($vt) . '_' . $group;

        return (int) ($rates[$key] ?? 0);
    }

    public function shouldSkipItem(WashTransaction $transaction, WashTransactionItem $item, ?WashService $service = null): bool
    {
        $srv = $service ?? ($item->service ?? null);

        if ($this->isOnlyMainServices() && $srv && (string) $srv->service_category !== 'main') {
            return true;
        }

        if ($this->isExcludeFreeWash() && (int) ($transaction->total_amount ?? 0) <= 0) {
            return true;
        }

        if (empty($item->employee_id)) {
            return true;
        }

        if ($srv && ($srv->vehicle_type === 'coffee' || $srv->vehicle_type === '' || $srv->vehicle_type === null)) {
            return true;
        }
        $itemServiceName = (string) ($item->service_name ?? '');
        if (! $srv && ($itemServiceName === '' || stripos($itemServiceName, 'kopi') !== false || stripos($itemServiceName, 'caffe') !== false || stripos($itemServiceName, 'warkop') !== false)) {
            return true;
        }

        return false;
    }

    public function calculateAndStoreForTransaction(WashTransaction $transaction): array
    {
        if (! $this->hasCommissionTable()) {
            return ['success' => false, 'message' => 'wash_commission_earnings table not found'];
        }

        if (! in_array(strtolower((string) $transaction->status), ['posted', 'paid', 'lunas', 'selesai', 'done'], true)) {
            return ['success' => false, 'message' => 'Transaction not posted/paid yet'];
        }

        $txId = $transaction->id;

        return DB::transaction(function () use ($transaction, $txId) {
            // Idempotency: void dulu earnings existing untuk transaksi ini (jika ada recalc)
            $this->voidForTransaction($transaction, 'recalc_before_reinsert');

            $items = WashTransactionItem::with('service')
                ->where('wash_transaction_id', $txId)
                ->get();

            $created = [];
            $summary = [
                'total_commission' => 0,
                'item_count' => 0,
                'skipped_count' => 0,
                'missing_employee_count' => 0,
            ];

            foreach ($items as $item) {
                $svc = $item->service ?? null;

                if (empty($item->employee_id)) {
                    $summary['missing_employee_count']++;
                    if ($this->shouldSkipItem($transaction, $item, $svc)) {
                        $summary['skipped_count']++;
                        continue;
                    }
                }

                if ($this->shouldSkipItem($transaction, $item, $svc)) {
                    $summary['skipped_count']++;
                    continue;
                }

                $rate = $this->resolveRateForService($svc);
                if ($rate <= 0) {
                    $summary['skipped_count']++;
                    continue;
                }

                $qty = max(1, (int) ($item->quantity ?? 1));
                $total = $rate * $qty;

                $vehicleTypeSnapshot = $svc?->vehicle_type ?? null;
                $sizeTierSnapshot = $svc?->size_tier ?? null;

                try {
                    $earning = WashCommissionEarning::query()->create([
                        'wash_employee_id' => $item->employee_id,
                        'wash_transaction_item_id' => $item->id,
                        'wash_transaction_id' => $txId,
                        'vehicle_type_snapshot' => $vehicleTypeSnapshot,
                        'size_tier_snapshot' => $sizeTierSnapshot,
                        'quantity' => $qty,
                        'rate_per_unit' => $rate,
                        'total_earned' => $total,
                        'status' => WashCommissionEarning::STATUS_EARNED,
                        'notes' => null,
                    ]);
                    $created[] = $earning;
                    $summary['total_commission'] += $total;
                    $summary['item_count']++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Sudah ada (race condition). Load yang existing, pastikan status earned.
                    try {
                        $existing = WashCommissionEarning::query()
                            ->where('wash_transaction_item_id', $item->id)
                            ->where('wash_employee_id', $item->employee_id)
                            ->first();
                        if ($existing && $existing->status === WashCommissionEarning::STATUS_VOIDED) {
                            $existing->update([
                                'status' => WashCommissionEarning::STATUS_EARNED,
                                'notes' => 'un_void_by_recalc',
                            ]);
                            $created[] = $existing;
                            $summary['total_commission'] += (int) $existing->total_earned;
                            $summary['item_count']++;
                        }
                    } catch (\Throwable) {}
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            try {
                $this->auditLog->logAction('wash_commission.calculated', $transaction, [
                    'transaction_id' => $txId,
                    'summary' => $summary,
                    'created_ids' => collect($created)->pluck('id')->values()->toArray(),
                ]);
            } catch (\Throwable) {}

            return [
                'success' => true,
                'created' => $created,
                'summary' => $summary,
            ];
        });
    }

    public function voidForTransaction(WashTransaction $transaction, string $reason = 'transaction_updated'): int
    {
        if (! $this->hasCommissionTable()) {
            return 0;
        }
        $txId = $transaction->id;
        if (! $txId) {
            return 0;
        }

        $affected = WashCommissionEarning::query()
            ->where('wash_transaction_id', $txId)
            ->whereIn('status', [WashCommissionEarning::STATUS_EARNED])
            ->update([
                'status' => WashCommissionEarning::STATUS_VOIDED,
                'notes' => (string) $reason . (empty($transaction->status) ? '' : ' status=' . $transaction->status),
            ]);

        if ($affected > 0) {
            try {
                $this->auditLog->logAction('wash_commission.voided', $transaction, [
                    'transaction_id' => $txId,
                    'reason' => $reason,
                    'voided_count' => $affected,
                ]);
            } catch (\Throwable) {}
        }

        return $affected;
    }

    public function recalcForTransaction(WashTransaction $transaction): array
    {
        return $this->calculateAndStoreForTransaction($transaction);
    }

    public function summaryForEmployee(WashEmployee|int $employee, ?string $startDate = null, ?string $endDate = null, array $statusFilter = null): array
    {
        if (! $this->hasCommissionTable()) {
            return [
                'rows' => collect(),
                'count' => 0,
                'total' => 0,
            ];
        }

        try {
            $empId = $employee instanceof WashEmployee ? $employee->id : (int) $employee;
            $query = WashCommissionEarning::query()->where('wash_employee_id', $empId);

            if ($statusFilter !== null && count($statusFilter) > 0) {
                $query->whereIn('status', $statusFilter);
            }
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $rows = $query->with('transaction', 'transactionItem')->orderByDesc('id')->get();
            $total = (int) $rows->sum('total_earned');
            $count = $rows->count();

            return [
                'rows' => $rows,
                'count' => $count,
                'total' => $total,
            ];
        } catch (\Throwable) {
            return [
                'rows' => collect(),
                'count' => 0,
                'total' => 0,
            ];
        }
    }
}
