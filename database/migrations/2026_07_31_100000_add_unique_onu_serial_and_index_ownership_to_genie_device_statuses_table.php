<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genie_device_statuses', function (Blueprint $table) {
            $conn = Schema::getConnection();
            $sm = $conn->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('genie_device_statuses');
            $hasUniqueOnuSerial = false;
            foreach ($indexes as $idx) {
                $cols = $idx->getColumns();
                if ($idx->isUnique() && count($cols) === 1 && $cols[0] === 'onu_serial') {
                    $hasUniqueOnuSerial = true;
                    break;
                }
            }

            if (! $hasUniqueOnuSerial) {
                try {
                    $table->unique('onu_serial', 'genie_device_statuses_onu_serial_unique');
                } catch (\Throwable $e) {
                    if (DB::getDriverName() === 'mysql') {
                        DB::statement('ALTER IGNORE TABLE genie_device_statuses ADD UNIQUE INDEX genie_device_statuses_onu_serial_unique (onu_serial)');
                    }
                }
            }

            try {
                $table->index('customer_id', 'genie_device_statuses_customer_id_index');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('genie_device_statuses', function (Blueprint $table) {
            try {
                $table->dropUnique('genie_device_statuses_onu_serial_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('genie_device_statuses_customer_id_index');
            } catch (\Throwable $e) {
            }
        });
    }
};
