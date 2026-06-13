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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transactions', 'source_type')) {
                $table->nullableMorphs('source');
            }
            if (!Schema::hasColumn('inventory_transactions', 'coordinator_id')) {
                $table->foreignId('coordinator_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('inventory_transactions', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('inventory_transactions', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('inventory_transactions', 'supplier_name')) {
                $table->string('supplier_name')->nullable();
            }
            if (!Schema::hasColumn('inventory_transactions', 'reference_no')) {
                $table->string('reference_no')->nullable();
            }
            if (!Schema::hasColumn('inventory_transactions', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('inventory_transactions', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropMorphs('source');
            $table->dropColumn(['coordinator_id', 'unit_cost', 'total_cost', 'supplier_name', 'reference_no', 'latitude', 'longitude']);
        });
    }
};
