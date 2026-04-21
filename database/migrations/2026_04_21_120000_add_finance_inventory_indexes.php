<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['coordinator_id', 'transaction_date'], 'idx_transactions_coord_date');
                $table->index(['investor_id', 'transaction_date'], 'idx_transactions_investor_date');
                $table->index(['type', 'category'], 'idx_transactions_type_category');
                $table->index('reference_number', 'idx_transactions_reference_number');
            });
        }

        if (Schema::hasTable('inventory_transactions')) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->index(['inventory_item_id', 'created_at'], 'idx_inventory_tx_item_created');
                $table->index(['coordinator_id', 'created_at'], 'idx_inventory_tx_coord_created');
                $table->index(['user_id', 'created_at'], 'idx_inventory_tx_user_created');
                $table->index(['type', 'created_at'], 'idx_inventory_tx_type_created');
            });
        }

        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->index(['type_group', 'category'], 'idx_inventory_items_type_category');
                $table->index('name', 'idx_inventory_items_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex('idx_transactions_coord_date');
                $table->dropIndex('idx_transactions_investor_date');
                $table->dropIndex('idx_transactions_type_category');
                $table->dropIndex('idx_transactions_reference_number');
            });
        }

        if (Schema::hasTable('inventory_transactions')) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->dropIndex('idx_inventory_tx_item_created');
                $table->dropIndex('idx_inventory_tx_coord_created');
                $table->dropIndex('idx_inventory_tx_user_created');
                $table->dropIndex('idx_inventory_tx_type_created');
            });
        }

        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropIndex('idx_inventory_items_type_category');
                $table->dropIndex('idx_inventory_items_name');
            });
        }
    }
};
