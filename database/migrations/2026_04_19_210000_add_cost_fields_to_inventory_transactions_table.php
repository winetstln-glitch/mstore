<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_transactions', 'unit_cost')) {
                $table->decimal('unit_cost', 14, 2)->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('inventory_transactions', 'total_cost')) {
                $table->decimal('total_cost', 14, 2)->nullable()->after('unit_cost');
            }
            if (! Schema::hasColumn('inventory_transactions', 'source_type')) {
                $table->string('source_type', 50)->nullable()->after('total_cost');
            }
            if (! Schema::hasColumn('inventory_transactions', 'supplier_name')) {
                $table->string('supplier_name', 150)->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('inventory_transactions', 'reference_no')) {
                $table->string('reference_no', 100)->nullable()->after('supplier_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_transactions', 'reference_no')) {
                $table->dropColumn('reference_no');
            }
            if (Schema::hasColumn('inventory_transactions', 'supplier_name')) {
                $table->dropColumn('supplier_name');
            }
            if (Schema::hasColumn('inventory_transactions', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('inventory_transactions', 'total_cost')) {
                $table->dropColumn('total_cost');
            }
            if (Schema::hasColumn('inventory_transactions', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });
    }
};
