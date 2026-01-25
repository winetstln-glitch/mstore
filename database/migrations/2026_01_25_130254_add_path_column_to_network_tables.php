<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'odcs' => 'description',
            'odps' => 'description',
            'htbs' => 'description',
            'customers' => 'updated_at'
        ];

        foreach ($tables as $tableName => $afterColumn) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'path')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $afterColumn) {
                    if (Schema::hasColumn($tableName, $afterColumn)) {
                        $table->json('path')->nullable()->after($afterColumn);
                    } else {
                        $table->json('path')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['odcs', 'odps', 'htbs', 'customers'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'path')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('path');
                });
            }
        }
    }
};
