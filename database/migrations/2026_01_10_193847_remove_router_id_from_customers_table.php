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
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'router_id')) {
                $table->dropForeign(['router_id']);
                $table->dropColumn('router_id');
            }
            if (!Schema::hasColumn('customers', 'olt_id')) {
                $table->foreignId('olt_id')->nullable()->constrained('olts')->nullOnDelete()->after('odp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['olt_id']);
            $table->dropColumn('olt_id');
        });
    }
};
