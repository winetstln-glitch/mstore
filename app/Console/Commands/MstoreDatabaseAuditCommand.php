<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MstoreDatabaseAuditCommand extends Command
{
    protected $signature = 'mstore:db-audit';
    protected $description = 'Audit konsistensi schema database untuk tabel kritikal MStore';

    public function handle(): int
    {
        $tables = [
            'customers',
            'tickets',
            'ticket_logs',
            'sla_rules',
            'sla_breaches',
            'network_diagnostics',
            'network_incidents',
            'area_outages',
            'technician_assignments',
            'payment_transactions',
            'whatsapp_logs',
            'whatsapp_sessions',
        ];

        $this->info('Database Consistency Audit');
        $this->line('Connection: '.DB::connection()->getName().' ('.config('database.default').')');
        $this->newLine();

        $issues = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $issues[] = ['severity' => 'warning', 'table' => $table, 'message' => 'Tabel tidak ditemukan'];
                $this->line('! '.$table.' — missing');
                continue;
            }

            $this->line('✓ '.$table);
        }

        $this->newLine();

        $issues = array_merge($issues, $this->auditSlaRules());
        $issues = array_merge($issues, $this->auditTickets());
        $issues = array_merge($issues, $this->auditForeignKeys());
        $issues = array_merge($issues, $this->auditIndexes());

        if (empty($issues)) {
            $this->info('Tidak ditemukan inconsistency yang terdeteksi oleh audit ini.');
            return 0;
        }

        $this->warn('Inconsistency ditemukan: '.count($issues));
        foreach ($issues as $issue) {
            $icon = $issue['severity'] === 'critical' ? '✗' : '!';
            $this->line($icon.' '.$issue['table'].' — '.$issue['message']);
        }

        $hasCritical = collect($issues)->contains(fn (array $i) => $i['severity'] === 'critical');
        return $hasCritical ? 2 : 1;
    }

    private function auditSlaRules(): array
    {
        if (! Schema::hasTable('sla_rules')) {
            return [];
        }

        $issues = [];
        $hasThresholdMinutes = Schema::hasColumn('sla_rules', 'threshold_minutes');
        $hasStatus = Schema::hasColumn('sla_rules', 'status');

        if (! $hasThresholdMinutes) {
            $issues[] = ['severity' => 'warning', 'table' => 'sla_rules', 'message' => 'Kolom threshold_minutes belum ada (dibutuhkan SLA Phase 6)'];
        }
        if (! $hasStatus) {
            $issues[] = ['severity' => 'warning', 'table' => 'sla_rules', 'message' => 'Kolom status belum ada (dibutuhkan SLA Phase 6)'];
        }

        $hasLegacy = Schema::hasColumn('sla_rules', 'warning_threshold_hours')
            || Schema::hasColumn('sla_rules', 'critical_threshold_hours')
            || Schema::hasColumn('sla_rules', 'escalation_threshold_hours');

        if ($hasLegacy && ($hasThresholdMinutes || $hasStatus)) {
            $issues[] = ['severity' => 'warning', 'table' => 'sla_rules', 'message' => 'Schema campuran (legacy *_threshold_hours + threshold_minutes/status)'];
        }

        return $issues;
    }

    private function auditTickets(): array
    {
        if (! Schema::hasTable('tickets')) {
            return [];
        }

        $issues = [];

        foreach (['customer_id', 'status', 'priority', 'created_at'] as $col) {
            if (! Schema::hasColumn('tickets', $col)) {
                $issues[] = ['severity' => 'critical', 'table' => 'tickets', 'message' => 'Kolom wajib tidak ditemukan: '.$col];
            }
        }

        if (Schema::hasColumn('tickets', 'sla_status')) {
            $issues[] = ['severity' => 'info', 'table' => 'tickets', 'message' => 'sla_status terdeteksi'];
        }

        return array_values(array_filter($issues, fn (array $i) => $i['severity'] !== 'info'));
    }

    private function auditForeignKeys(): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();

        $pairs = [
            ['table' => 'tickets', 'column' => 'customer_id', 'ref_table' => 'customers', 'ref_column' => 'id'],
            ['table' => 'sla_breaches', 'column' => 'ticket_id', 'ref_table' => 'tickets', 'ref_column' => 'id'],
            ['table' => 'sla_breaches', 'column' => 'sla_rule_id', 'ref_table' => 'sla_rules', 'ref_column' => 'id'],
            ['table' => 'whatsapp_logs', 'column' => 'whatsapp_session_id', 'ref_table' => 'whatsapp_sessions', 'ref_column' => 'id'],
        ];

        $issues = [];

        foreach ($pairs as $p) {
            if (! Schema::hasTable($p['table']) || ! Schema::hasTable($p['ref_table'])) {
                continue;
            }
            if (! Schema::hasColumn($p['table'], $p['column'])) {
                continue;
            }

            $has = $this->hasForeignKey($conn, $driver, $p['table'], $p['column'], $p['ref_table'], $p['ref_column']);
            if (! $has) {
                $issues[] = [
                    'severity' => 'warning',
                    'table' => $p['table'],
                    'message' => 'Foreign key tidak terdeteksi: '.$p['column'].' -> '.$p['ref_table'].'.'.$p['ref_column'],
                ];
            }
        }

        return $issues;
    }

    private function hasForeignKey(Connection $conn, string $driver, string $table, string $column, string $refTable, string $refColumn): bool
    {
        try {
            if ($driver === 'sqlite') {
                $rows = DB::select('PRAGMA foreign_key_list("'.$table.'")');
                foreach ($rows as $r) {
                    if (
                        isset($r->from, $r->table, $r->to)
                        && $r->from === $column
                        && $r->table === $refTable
                        && $r->to === $refColumn
                    ) {
                        return true;
                    }
                }
                return false;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $dbName = (string) ($conn->getDatabaseName());
                $rows = DB::select(
                    'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                     FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [$dbName, $table, $column]
                );
                foreach ($rows as $r) {
                    if (
                        ($r->REFERENCED_TABLE_NAME ?? null) === $refTable
                        && ($r->REFERENCED_COLUMN_NAME ?? null) === $refColumn
                    ) {
                        return true;
                    }
                }
                return false;
            }

            return true;
        } catch (\Throwable) {
            return true;
        }
    }

    private function auditIndexes(): array
    {
        $issues = [];

        $expected = [
            ['table' => 'tickets', 'columns' => ['status']],
            ['table' => 'tickets', 'columns' => ['customer_id']],
            ['table' => 'sla_breaches', 'columns' => ['ticket_id']],
            ['table' => 'sla_breaches', 'columns' => ['created_at']],
            ['table' => 'whatsapp_analytics_events', 'columns' => ['occurred_at']],
        ];

        foreach ($expected as $e) {
            if (! Schema::hasTable($e['table'])) {
                continue;
            }

            $missing = collect($e['columns'])->filter(fn (string $c) => ! Schema::hasColumn($e['table'], $c))->values()->all();
            if (! empty($missing)) {
                continue;
            }

            $has = $this->hasIndexOnColumns($e['table'], $e['columns']);
            if (! $has) {
                $issues[] = [
                    'severity' => 'warning',
                    'table' => $e['table'],
                    'message' => 'Index tidak terdeteksi untuk kolom: '.implode(',', $e['columns']),
                ];
            }
        }

        return $issues;
    }

    private function hasIndexOnColumns(string $table, array $columns): bool
    {
        try {
            $driver = DB::connection()->getDriverName();
            $columns = array_values($columns);

            if ($driver === 'sqlite') {
                $indexes = DB::select('PRAGMA index_list("'.$table.'")');
                foreach ($indexes as $idx) {
                    if (! isset($idx->name)) {
                        continue;
                    }
                    $info = DB::select('PRAGMA index_info("'.$idx->name.'")');
                    $idxCols = [];
                    foreach ($info as $col) {
                        if (isset($col->name)) {
                            $idxCols[] = $col->name;
                        }
                    }

                    if ($idxCols === $columns) {
                        return true;
                    }
                    if (count($columns) === 1 && in_array($columns[0], $idxCols, true)) {
                        return true;
                    }
                }

                return false;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $dbName = (string) DB::connection()->getDatabaseName();
                $rows = DB::select(
                    'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
                     FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                     GROUP BY INDEX_NAME',
                    [$dbName, $table]
                );

                foreach ($rows as $r) {
                    $cols = isset($r->cols) ? explode(',', (string) $r->cols) : [];
                    if ($cols === $columns) {
                        return true;
                    }
                    if (count($columns) === 1 && in_array($columns[0], $cols, true)) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        } catch (\Throwable) {
            return true;
        }
    }
}
