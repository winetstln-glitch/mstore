<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        try {
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();

            if (! $exists && Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexName, $columns) {
                    $table->index($columns, $indexName);
                });
            }
        } catch (\Throwable $e) {
            // Skip silently to avoid breaking deploys
        }
    }

    public function up(): void
    {
        // ATK tables
        $this->addIndexIfMissing('atk_transactions', 'idx_atk_transactions_created_at', ['created_at']);
        $this->addIndexIfMissing('atk_transactions', 'idx_atk_transactions_user_id', ['user_id']);
        $this->addIndexIfMissing('atk_transactions', 'idx_atk_transactions_transaction_number', ['transaction_number']);

        $this->addIndexIfMissing('atk_transaction_items', 'idx_atk_items_transaction_id', ['atk_transaction_id']);
        $this->addIndexIfMissing('atk_transaction_items', 'idx_atk_items_product_id', ['product_id']);

        $this->addIndexIfMissing('atk_products', 'idx_atk_products_category', ['category']);
        $this->addIndexIfMissing('atk_products', 'idx_atk_products_name', ['name']);

        // Wash tables
        $this->addIndexIfMissing('wash_transactions', 'idx_wash_transactions_created_at', ['created_at']);
        $this->addIndexIfMissing('wash_transactions', 'idx_wash_transactions_user_id', ['user_id']);

        // Common users for avatar lookups
        $this->addIndexIfMissing('users', 'idx_users_avatar', ['avatar']);
    }

    public function down(): void
    {
        // No-op: dropping indexes safely by name (if exist)
        $drop = function (string $table, string $index) {
            try {
                if (Schema::hasTable($table)) {
                    Schema::table($table, function (Blueprint $table) use ($index) {
                        try {
                            $table->dropIndex($index);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    });
                }
            } catch (\Throwable $e) {
                // ignore
            }
        };

        $drop('atk_transactions', 'idx_atk_transactions_created_at');
        $drop('atk_transactions', 'idx_atk_transactions_user_id');
        $drop('atk_transactions', 'idx_atk_transactions_transaction_number');
        $drop('atk_transaction_items', 'idx_atk_items_transaction_id');
        $drop('atk_transaction_items', 'idx_atk_items_product_id');
        $drop('atk_products', 'idx_atk_products_category');
        $drop('atk_products', 'idx_atk_products_name');
        $drop('wash_transactions', 'idx_wash_transactions_created_at');
        $drop('wash_transactions', 'idx_wash_transactions_user_id');
        $drop('users', 'idx_users_avatar');
    }
};
