<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update atk_products
        Schema::table('atk_products', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_products', 'barcode')) {
                $table->string('barcode')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('atk_products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('barcode')->constrained('atk_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_products', 'selling_price')) {
                $table->decimal('selling_price', 15, 2)->default(0)->after('category_id');
            }
            if (! Schema::hasColumn('atk_products', 'current_stock')) {
                $table->integer('current_stock')->default(0)->after('selling_price');
            }
            if (! Schema::hasColumn('atk_products', 'stock_alert')) {
                $table->boolean('stock_alert')->default(false)->after('minimum_stock');
            }
            
            // Drop old columns if they exist (optional, uncomment if needed)
            // if (Schema::hasColumn('atk_products', 'price')) {
            //     $table->dropColumn('price');
            // }
            // if (Schema::hasColumn('atk_products', 'stock')) {
            //     $table->dropColumn('stock');
            // }
            // if (Schema::hasColumn('atk_products', 'category')) {
            //     $table->dropColumn('category');
            // }
        });

        // Update atk_transactions
        Schema::table('atk_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['product', 'service', 'transfer', 'cash_withdrawal', 'topup', 'ppob', 'refund'])->default('product')->after('transaction_number');
            }
            if (! Schema::hasColumn('atk_transactions', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('completed')->after('payment_method');
            }
            if (! Schema::hasColumn('atk_transactions', 'journal_status')) {
                $table->enum('journal_status', ['pending', 'posted', 'failed'])->default('pending')->after('payment_status');
            }
            if (! Schema::hasColumn('atk_transactions', 'atk_float_account_id')) {
                $table->foreignId('atk_float_account_id')->nullable()->after('atk_cash_register_id')->constrained('atk_float_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_transactions', 'grand_total')) {
                $table->decimal('grand_total', 15, 2)->default(0)->after('total_amount');
            }
        });

        // Update atk_transaction_items
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_transaction_items', 'item_type')) {
                $table->enum('item_type', ['product', 'service'])->default('product')->after('atk_transaction_id');
            }
            if (! Schema::hasColumn('atk_transaction_items', 'qty')) {
                $table->integer('qty')->default(1)->after('price');
            }
            if (! Schema::hasColumn('atk_transaction_items', 'cost')) {
                $table->decimal('cost', 15, 2)->default(0)->after('qty');
            }
            
            // Drop old column if needed (optional)
            // if (Schema::hasColumn('atk_transaction_items', 'quantity')) {
            //     $table->dropColumn('quantity');
            // }
        });
    }

    public function down(): void
    {
        Schema::table('atk_products', function (Blueprint $table) {
            if (Schema::hasColumn('atk_products', 'barcode')) {
                $table->dropColumn('barcode');
            }
            if (Schema::hasColumn('atk_products', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('atk_products', 'selling_price')) {
                $table->dropColumn('selling_price');
            }
            if (Schema::hasColumn('atk_products', 'current_stock')) {
                $table->dropColumn('current_stock');
            }
            if (Schema::hasColumn('atk_products', 'stock_alert')) {
                $table->dropColumn('stock_alert');
            }
        });

        Schema::table('atk_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transactions', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
            if (Schema::hasColumn('atk_transactions', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('atk_transactions', 'journal_status')) {
                $table->dropColumn('journal_status');
            }
            if (Schema::hasColumn('atk_transactions', 'atk_float_account_id')) {
                $table->dropForeign(['atk_float_account_id']);
                $table->dropColumn('atk_float_account_id');
            }
            if (Schema::hasColumn('atk_transactions', 'grand_total')) {
                $table->dropColumn('grand_total');
            }
        });

        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transaction_items', 'item_type')) {
                $table->dropColumn('item_type');
            }
            if (Schema::hasColumn('atk_transaction_items', 'qty')) {
                $table->dropColumn('qty');
            }
            if (Schema::hasColumn('atk_transaction_items', 'cost')) {
                $table->dropColumn('cost');
            }
        });
    }
};
