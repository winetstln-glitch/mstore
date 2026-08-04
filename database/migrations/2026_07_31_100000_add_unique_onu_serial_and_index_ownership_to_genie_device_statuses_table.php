<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasUniqueIndexForOnuSerial(): bool
    {
        $driver = DB::getDriverName();
        $table = 'genie_device_statuses';
        $indexName = 'genie_device_statuses_onu_serial_unique';
        $column = 'onu_serial';

        try {
            if ($driver === 'mysql') {
                $dbName = DB::getDatabaseName();
                $result = DB::select(
                    'SELECT COUNT(*) AS cnt FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ? 
                       AND non_unique = 0 AND seq_in_index = 1 AND column_name = ?',
                    [$dbName, $table, $indexName, $column]
                );
                return ($result[0]->cnt ?? 0) > 0;
            }

            if ($driver === 'sqlite') {
                $indexList = DB::select("PRAGMA index_list({$table})");
                foreach ($indexList as $idx) {
                    $idxName = $idx->name ?? null;
                    $isUnique = (bool) ($idx->unique ?? false);
                    if (! $isUnique) {
                        continue;
                    }
                    $cols = DB::select("PRAGMA index_info({$idxName})");
                    $cols = array_map(
                        fn ($c) => strtolower($c->name ?? ''),
                        $cols
                    );
                    if ($idxName === $indexName && $cols === [strtolower($column)]) {
                        return true;
                    }
                }
                return false;
            }

            if ($driver === 'pgsql') {
                $result = DB::select(
                    'SELECT COUNT(*) AS cnt 
                     FROM pg_indexes i
                     JOIN pg_class c ON c.relname = i.indexname
                     WHERE i.tablename = ? AND i.indexname = ?',
                    [$table, $indexName]
                );
                return ($result[0]->cnt ?? 0) > 0;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    private function hasIndex(string $targetIndexName): bool
    {
        $driver = DB::getDriverName();
        $table = 'genie_device_statuses';

        try {
            if ($driver === 'mysql') {
                $dbName = DB::getDatabaseName();
                $result = DB::select(
                    'SELECT COUNT(*) AS cnt FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                    [$dbName, $table, $targetIndexName]
                );
                return ($result[0]->cnt ?? 0) > 0;
            }

            if ($driver === 'sqlite') {
                $indexList = DB::select("PRAGMA index_list({$table})");
                foreach ($indexList as $idx) {
                    if (($idx->name ?? null) === $targetIndexName) {
                        return true;
                    }
                }
                return false;
            }

            if ($driver === 'pgsql') {
                $result = DB::select(
                    'SELECT COUNT(*) AS cnt FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                    [$table, $targetIndexName]
                );
                return ($result[0]->cnt ?? 0) > 0;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    public function up(): void
    {
        $uniqueIndexName = 'genie_device_statuses_onu_serial_unique';
        $ownershipIndexName = 'genie_device_statuses_customer_id_index';

        if (! $this->hasUniqueIndexForOnuSerial()) {
            try {
                Schema::table('genie_device_statuses', function (Blueprint $table) use ($uniqueIndexName) {
                    $table->unique('onu_serial', $uniqueIndexName);
                });
            } catch (\Throwable $e) {
                if (DB::getDriverName() === 'mysql') {
                    try {
                        DB::statement(
                            'ALTER IGNORE TABLE genie_device_statuses 
                             ADD UNIQUE INDEX ' . $uniqueIndexName . ' (onu_serial)'
                        );
                    } catch (\Throwable $e2) {
                    }
                }
            }
        }

        if (! $this->hasIndex($ownershipIndexName)) {
            try {
                Schema::table('genie_device_statuses', function (Blueprint $table) use ($ownershipIndexName) {
                    $table->index('customer_id', $ownershipIndexName);
                });
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('genie_device_statuses', function (Blueprint $table) {
                $table->dropUnique('genie_device_statuses_onu_serial_unique');
            });
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('genie_device_statuses', function (Blueprint $table) {
                $table->dropIndex('genie_device_statuses_customer_id_index');
            });
        } catch (\Throwable $e) {
        }
    }
};
