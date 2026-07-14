<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sla_rules')) {
            Schema::table('sla_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('sla_rules', 'threshold_minutes')) {
                    $table->unsignedInteger('threshold_minutes')->nullable()->after('name')->index();
                }
                if (! Schema::hasColumn('sla_rules', 'status')) {
                    $table->string('status', 20)->nullable()->after('threshold_minutes')->index();
                }
            });

            $this->ensureIndex('sla_rules', ['threshold_minutes']);
            $this->ensureIndex('sla_rules', ['status']);
        }

        if (Schema::hasTable('whatsapp_logs')) {
            $this->ensureIndex('whatsapp_logs', ['phone_number']);
            $this->ensureIndex('whatsapp_logs', ['type']);
            $this->ensureIndex('whatsapp_logs', ['status']);
            $this->ensureIndex('whatsapp_logs', ['created_at']);
            $this->ensureIndex('whatsapp_logs', ['phone_number', 'created_at']);
        }

        if (Schema::hasTable('payment_transactions')) {
            $this->ensureIndex('payment_transactions', ['phone_number']);
            $this->ensureIndex('payment_transactions', ['status']);
            $this->ensureIndex('payment_transactions', ['created_at']);
            $this->ensureIndex('payment_transactions', ['paid_at']);
            $this->ensureIndex('payment_transactions', ['expires_at']);
        }

        if (Schema::hasTable('area_outages')) {
            $this->ensureIndex('area_outages', ['type']);
            $this->ensureIndex('area_outages', ['status']);
            $this->ensureIndex('area_outages', ['started_at']);
            $this->ensureIndex('area_outages', ['region_id']);
            $this->ensureIndex('area_outages', ['olt_id']);
            $this->ensureIndex('area_outages', ['odp_id']);
        }

        if (Schema::hasTable('network_incidents')) {
            $this->ensureIndex('network_incidents', ['type']);
            $this->ensureIndex('network_incidents', ['status']);
            $this->ensureIndex('network_incidents', ['severity']);
            $this->ensureIndex('network_incidents', ['detected_at']);
            $this->ensureIndex('network_incidents', ['region_id']);
            $this->ensureIndex('network_incidents', ['olt_id']);
            $this->ensureIndex('network_incidents', ['odp_id']);
        }

        if (Schema::hasTable('network_diagnostics')) {
            $this->ensureIndex('network_diagnostics', ['status']);
            $this->ensureIndex('network_diagnostics', ['created_at']);
            $this->ensureIndex('network_diagnostics', ['customer_id', 'created_at']);
        }

        if (Schema::hasTable('technician_assignments')) {
            $this->ensureIndex('technician_assignments', ['status']);
            $this->ensureIndex('technician_assignments', ['assigned_at']);
            $this->ensureIndex('technician_assignments', ['technician_id', 'status']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sla_rules')) {
            if (Schema::hasColumn('sla_rules', 'status')) {
                $this->dropIndexIfExists('sla_rules', ['status']);
            }
            if (Schema::hasColumn('sla_rules', 'threshold_minutes')) {
                $this->dropIndexIfExists('sla_rules', ['threshold_minutes']);
            }
        }

        if (Schema::hasTable('whatsapp_logs')) {
            $this->dropIndexIfExists('whatsapp_logs', ['phone_number', 'created_at']);
            $this->dropIndexIfExists('whatsapp_logs', ['created_at']);
            $this->dropIndexIfExists('whatsapp_logs', ['status']);
            $this->dropIndexIfExists('whatsapp_logs', ['type']);
            $this->dropIndexIfExists('whatsapp_logs', ['phone_number']);
        }

        if (Schema::hasTable('payment_transactions')) {
            $this->dropIndexIfExists('payment_transactions', ['expires_at']);
            $this->dropIndexIfExists('payment_transactions', ['paid_at']);
            $this->dropIndexIfExists('payment_transactions', ['created_at']);
            $this->dropIndexIfExists('payment_transactions', ['status']);
            $this->dropIndexIfExists('payment_transactions', ['phone_number']);
        }

        if (Schema::hasTable('area_outages')) {
            $this->dropIndexIfExists('area_outages', ['odp_id']);
            $this->dropIndexIfExists('area_outages', ['olt_id']);
            $this->dropIndexIfExists('area_outages', ['region_id']);
            $this->dropIndexIfExists('area_outages', ['started_at']);
            $this->dropIndexIfExists('area_outages', ['status']);
            $this->dropIndexIfExists('area_outages', ['type']);
        }

        if (Schema::hasTable('network_incidents')) {
            $this->dropIndexIfExists('network_incidents', ['odp_id']);
            $this->dropIndexIfExists('network_incidents', ['olt_id']);
            $this->dropIndexIfExists('network_incidents', ['region_id']);
            $this->dropIndexIfExists('network_incidents', ['detected_at']);
            $this->dropIndexIfExists('network_incidents', ['severity']);
            $this->dropIndexIfExists('network_incidents', ['status']);
            $this->dropIndexIfExists('network_incidents', ['type']);
        }

        if (Schema::hasTable('network_diagnostics')) {
            $this->dropIndexIfExists('network_diagnostics', ['customer_id', 'created_at']);
            $this->dropIndexIfExists('network_diagnostics', ['created_at']);
            $this->dropIndexIfExists('network_diagnostics', ['status']);
        }

        if (Schema::hasTable('technician_assignments')) {
            $this->dropIndexIfExists('technician_assignments', ['technician_id', 'status']);
            $this->dropIndexIfExists('technician_assignments', ['assigned_at']);
            $this->dropIndexIfExists('technician_assignments', ['status']);
        }
    }

    private function ensureIndex(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->hasIndexOnColumns($table, $columns)) {
            return;
        }

        $indexName = $this->indexName($table, $columns);

        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $indexName = $this->indexName($table, $columns);
        $driver = DB::connection()->getDriverName();

        $exists = $this->hasIndexByName($table, $indexName, $driver);
        if (! $exists) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->dropIndex($indexName);
        });
    }

    private function indexName(string $table, array $columns): string
    {
        return $table.'_'.implode('_', $columns).'_index';
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

    private function hasIndexByName(string $table, string $indexName, string $driver): bool
    {
        try {
            if ($driver === 'sqlite') {
                $indexes = DB::select('PRAGMA index_list("'.$table.'")');
                foreach ($indexes as $idx) {
                    if (isset($idx->name) && $idx->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $dbName = (string) DB::connection()->getDatabaseName();
                $rows = DB::select(
                    'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                    [$dbName, $table, $indexName]
                );
                return ! empty($rows);
            }

            return true;
        } catch (\Throwable) {
            return true;
        }
    }
};

