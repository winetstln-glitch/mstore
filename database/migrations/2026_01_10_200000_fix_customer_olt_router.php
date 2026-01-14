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
                // Drop foreign key first if it exists
                // We need to know the foreign key name. Laravel usually names it table_column_foreign
                // But since I can't be 100% sure of the name and dropForeign takes array of columns...
                try {
                    $table->dropForeign(['router_id']);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
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
            // We won't restore router_id as it's being deprecated
        });
    }
};
