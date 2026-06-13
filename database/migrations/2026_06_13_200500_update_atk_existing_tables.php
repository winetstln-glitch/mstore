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
            if (! Schema::hasColumn('atk_products', 'atk_supplier_id')) {
                $table->foreignId('atk_supplier_id')->nullable()->after('image')->constrained('atk_suppliers')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_products', 'minimum_stock')) {
                $table->integer('minimum_stock')->default(0)->after('stock');
            }
            if (! Schema::hasColumn('atk_products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('minimum_stock');
            }
        });

        // Update atk_transactions
        Schema::table('atk_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_transactions', 'atk_customer_id')) {
                $table->foreignId('atk_customer_id')->nullable()->after('user_id')->constrained('atk_customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_transactions', 'atk_cash_register_id')) {
                $table->foreignId('atk_cash_register_id')->nullable()->after('atk_customer_id')->constrained('atk_cash_registers')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_transactions', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (! Schema::hasColumn('atk_transactions', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('atk_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('queue_number');
            }
            if (! Schema::hasColumn('atk_transactions', 'status')) {
                $table->enum('status', ['draft', 'completed', 'refunded'])->default('completed')->after('notes');
            }
            
            $table->index('transaction_number');
            $table->index('created_at');
        });

        // Update atk_transaction_items
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_transaction_items', 'atk_service_id')) {
                $table->foreignId('atk_service_id')->nullable()->after('product_id')->constrained('atk_services')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_transaction_items', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('atk_transaction_items', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('discount');
            }
            if (! Schema::hasColumn('atk_transaction_items', 'notes')) {
                $table->text('notes')->nullable()->after('tax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('atk_products', function (Blueprint $table) {
            if (Schema::hasColumn('atk_products', 'atk_supplier_id')) {
                $table->dropForeign(['atk_supplier_id']);
                $table->dropColumn('atk_supplier_id');
            }
            if (Schema::hasColumn('atk_products', 'minimum_stock')) {
                $table->dropColumn('minimum_stock');
            }
            if (Schema::hasColumn('atk_products', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::table('atk_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transactions', 'atk_customer_id')) {
                $table->dropForeign(['atk_customer_id']);
                $table->dropColumn('atk_customer_id');
            }
            if (Schema::hasColumn('atk_transactions', 'atk_cash_register_id')) {
                $table->dropForeign(['atk_cash_register_id']);
                $table->dropColumn('atk_cash_register_id');
            }
            if (Schema::hasColumn('atk_transactions', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('atk_transactions', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
            if (Schema::hasColumn('atk_transactions', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('atk_transactions', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transaction_items', 'atk_service_id')) {
                $table->dropForeign(['atk_service_id']);
                $table->dropColumn('atk_service_id');
            }
            if (Schema::hasColumn('atk_transaction_items', 'discount')) {
                $table->dropColumn('discount');
            }
            if (Schema::hasColumn('atk_transaction_items', 'tax')) {
                $table->dropColumn('tax');
            }
            if (Schema::hasColumn('atk_transaction_items', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
