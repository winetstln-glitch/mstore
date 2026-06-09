<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Setting;
use App\Models\WashMemberLevel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class WashProductionAuditCommand extends Command
{
    protected $signature = 'wash:production-audit';

    protected $description = 'Audit readiness modul GT Wash untuk verifikasi pasca-deploy';

    public function handle(): int
    {
        $checks = [
            $this->checkRequiredTables(),
            $this->checkRequiredColumns(),
            $this->checkMemberLevels(),
            $this->checkRoutes(),
            $this->checkPermissions(),
            $this->checkWhatsAppConfig(),
            $this->checkDuitkuConfig(),
        ];

        $hasCritical = collect($checks)->contains(fn (array $check) => $check['status'] === 'critical');
        $hasWarning = collect($checks)->contains(fn (array $check) => $check['status'] === 'warning');

        foreach ($checks as $check) {
            $icon = match ($check['status']) {
                'ok' => '✓',
                'warning' => '!',
                default => '✗',
            };

            $message = trim((string) ($check['message'] ?? ''));
            $line = $icon.' '.$check['name'];
            if ($message !== '') {
                $line .= ' - '.$message;
            }
            $this->line($line);
        }

        $this->newLine();
        $this->line('Snapshot GT Wash:');
        $this->line('- Total member: '.$this->safeCount('wash_members'));
        $this->line('- Total loyalty counter: '.$this->safeCount('wash_loyalty_counters'));
        $this->line('- Total voucher reward: '.$this->safeCount('wash_reward_vouchers'));
        $this->line('- Total redemption: '.$this->safeCount('wash_reward_redemptions'));
        $this->newLine();

        $final = $hasCritical ? 'CRITICAL' : ($hasWarning ? 'WARNING' : 'READY');
        $this->info('Status akhir GT Wash: '.$final);

        return $hasCritical ? 2 : ($hasWarning ? 1 : 0);
    }

    private function checkRequiredTables(): array
    {
        $tables = [
            'wash_members',
            'wash_member_levels',
            'wash_member_vehicles',
            'wash_member_cards',
            'wash_loyalty_counters',
            'wash_reward_vouchers',
            'wash_reward_redemptions',
            'wash_transactions',
            'permissions',
        ];

        $missing = collect($tables)
            ->reject(fn (string $table) => Schema::hasTable($table))
            ->values()
            ->all();

        return [
            'name' => 'Schema tabel GT Wash',
            'status' => count($missing) === 0 ? 'ok' : 'critical',
            'message' => count($missing) === 0 ? 'Semua tabel utama tersedia' : 'Kurang: '.implode(', ', $missing),
        ];
    }

    private function checkRequiredColumns(): array
    {
        $missing = [];

        $columnMap = [
            'wash_transactions' => ['wash_member_id', 'member_discount_amount', 'queue_number'],
            'wash_loyalty_counters' => ['wash_member_id', 'vehicle_plate', 'cycle_paid_count'],
            'wash_reward_vouchers' => ['wash_member_id', 'code', 'status', 'expires_at'],
            'wash_members' => ['member_number', 'wash_member_level_id', 'total_transactions', 'total_visits', 'total_spending', 'status'],
        ];

        foreach ($columnMap as $table => $columns) {
            if (! Schema::hasTable($table)) {
                return [
                    'name' => 'Kolom penting membership/loyalty',
                    'status' => 'warning',
                    'message' => 'Lewati cek kolom sampai migrasi tabel GT Wash selesai',
                ];
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = $table.'.'.$column;
                }
            }
        }

        return [
            'name' => 'Kolom penting membership/loyalty',
            'status' => count($missing) === 0 ? 'ok' : 'critical',
            'message' => count($missing) === 0 ? 'Lengkap' : 'Kurang: '.implode(', ', $missing),
        ];
    }

    private function checkMemberLevels(): array
    {
        if (! Schema::hasTable('wash_member_levels')) {
            return [
                'name' => 'Seed membership level',
                'status' => 'warning',
                'message' => 'Lewati cek seed sampai tabel wash_member_levels tersedia',
            ];
        }

        $codes = WashMemberLevel::query()->pluck('code')->map(fn ($code) => strtolower((string) $code))->all();
        $required = ['bronze', 'silver', 'gold', 'platinum'];
        $missing = array_values(array_diff($required, $codes));

        return [
            'name' => 'Seed membership level',
            'status' => count($missing) === 0 ? 'ok' : 'critical',
            'message' => count($missing) === 0 ? 'Bronze/Silver/Gold/Platinum tersedia' : 'Kurang: '.implode(', ', $missing),
        ];
    }

    private function checkRoutes(): array
    {
        $required = [
            'wash.dashboard',
            'wash.pos',
            'wash.members.index',
            'wash.members.card',
            'wash.members.verify',
            'wash.loyalty.index',
            'wash.loyalty.vouchers',
            'wash.loyalty.redemptions',
            'wash.reports.index',
            'wash.transactions.store',
            'wash.transactions.receipt',
            'wash.customer.check',
        ];

        $routes = collect(Route::getRoutes()->getRoutesByName())->keys()->all();
        $missing = array_values(array_diff($required, $routes));

        return [
            'name' => 'Route penting GT Wash',
            'status' => count($missing) === 0 ? 'ok' : 'critical',
            'message' => count($missing) === 0 ? 'Semua route utama terdaftar' : 'Kurang: '.implode(', ', $missing),
        ];
    }

    private function checkPermissions(): array
    {
        if (! Schema::hasTable('permissions')) {
            return [
                'name' => 'Permission GT Wash',
                'status' => 'critical',
                'message' => 'Tabel permissions belum tersedia',
            ];
        }

        $required = [
            'wash.view',
            'wash.pos',
            'wash.manage',
            'wash.report',
            'wash.member.view',
            'wash.member.manage',
            'wash.loyalty.view',
            'wash.loyalty.manage',
            'wash.reward.view',
            'wash.reward.manage',
        ];

        $existing = Permission::query()
            ->whereIn('name', $required)
            ->pluck('name')
            ->all();

        $missing = array_values(array_diff($required, $existing));

        return [
            'name' => 'Permission GT Wash',
            'status' => count($missing) === 0 ? 'ok' : 'critical',
            'message' => count($missing) === 0 ? 'Semua permission wash tersedia' : 'Kurang: '.implode(', ', $missing),
        ];
    }

    private function checkWhatsAppConfig(): array
    {
        $url = trim((string) Setting::getValue('whatsapp_api_url', config('services.whatsapp.url', '')));
        $key = trim((string) Setting::getValue('whatsapp_api_key', config('services.whatsapp.key', '')));
        $ok = $url !== '' && $key !== '';

        return [
            'name' => 'Konfigurasi WhatsApp',
            'status' => $ok ? 'ok' : 'warning',
            'message' => $ok ? 'Gateway siap untuk notifikasi membership' : 'URL/API key WhatsApp belum lengkap',
        ];
    }

    private function checkDuitkuConfig(): array
    {
        $merchantCode = trim((string) Setting::getValue('duitku_merchant_code', config('services.duitku.merchant_code', '')));
        $apiKey = trim((string) Setting::getValue('duitku_api_key', config('services.duitku.api_key', '')));
        $ok = $merchantCode !== '' && $apiKey !== '';

        return [
            'name' => 'Konfigurasi Duitku',
            'status' => $ok ? 'ok' : 'warning',
            'message' => $ok ? 'QRIS Duitku tersedia' : 'Merchant code/API key Duitku belum lengkap',
        ];
    }

    private function safeCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) Schema::getConnection()->table($table)->count();
    }
}
