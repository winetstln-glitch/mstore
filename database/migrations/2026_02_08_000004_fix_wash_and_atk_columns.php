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
        // Fix Wash Transactions
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_transactions', 'cash_amount')) {
                $table->decimal('cash_amount', 15, 2)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('wash_transactions', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable()->after('cash_amount');
            }
            if (! Schema::hasColumn('wash_transactions', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (! Schema::hasColumn('wash_transactions', 'wash_customer_id')) {
                $table->foreignId('wash_customer_id')->nullable()->after('user_id')->constrained('wash_customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('wash_transactions', 'vehicle_brand')) {
                $table->string('vehicle_brand')->nullable()->after('vehicle_plate');
            }
        });

        // Fix ATK Transaction Items
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transaction_items', 'atk_product_id')) {
                // Make it nullable so we can use product_id instead
                $table->unsignedBigInteger('atk_product_id')->nullable()->change();
            }

            if (! Schema::hasColumn('atk_transaction_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('atk_transaction_id')->constrained('atk_products')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropColumn(['cash_amount', 'change_amount', 'discount_amount', 'wash_customer_id', 'vehicle_brand']);
        });
    }
};
