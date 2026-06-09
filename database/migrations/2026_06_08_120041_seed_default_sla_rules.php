<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('sla_rules')) {
            return;
        }

        if (! Schema::hasColumn('sla_rules', 'threshold_minutes') || ! Schema::hasColumn('sla_rules', 'status')) {
            Schema::table('sla_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('sla_rules', 'threshold_minutes')) {
                    $table->unsignedInteger('threshold_minutes')->nullable()->after('name')->index();
                }
                if (! Schema::hasColumn('sla_rules', 'status')) {
                    $table->string('status', 20)->nullable()->after('threshold_minutes')->index();
                }
            });
        }

        $rows = [
            [
                'name' => 'OPEN > 24 JAM',
                'threshold_minutes' => 24 * 60,
                'status' => 'warning',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'OPEN > 48 JAM',
                'threshold_minutes' => 48 * 60,
                'status' => 'critical',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'OPEN > 72 JAM',
                'threshold_minutes' => 72 * 60,
                'status' => 'breached',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rows as $row) {
            if (! Schema::hasColumn('sla_rules', 'threshold_minutes') || ! Schema::hasColumn('sla_rules', 'status')) {
                continue;
            }

            $exists = DB::table('sla_rules')
                ->where('threshold_minutes', $row['threshold_minutes'])
                ->where('status', $row['status'])
                ->exists();

            if (! $exists) {
                DB::table('sla_rules')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('sla_rules')) {
            return;
        }

        if (! Schema::hasColumn('sla_rules', 'threshold_minutes') || ! Schema::hasColumn('sla_rules', 'status')) {
            return;
        }

        DB::table('sla_rules')
            ->whereIn('threshold_minutes', [24 * 60, 48 * 60, 72 * 60])
            ->whereIn('status', ['warning', 'critical', 'breached'])
            ->delete();
    }
};
