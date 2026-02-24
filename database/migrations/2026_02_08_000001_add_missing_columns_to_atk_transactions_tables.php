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
        Schema::table('atk_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_transactions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('atk_transactions', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('user_id'); // Made nullable to avoid SQLite error
            }
            if (! Schema::hasColumn('atk_transactions', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('transaction_number');
            }
            if (! Schema::hasColumn('atk_transactions', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('total_amount');
            }
            if (! Schema::hasColumn('atk_transactions', 'cash_amount')) {
                $table->decimal('cash_amount', 15, 2)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('atk_transactions', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable()->after('cash_amount');
            }
            if (! Schema::hasColumn('atk_transactions', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->nullable()->after('change_amount');
            }
        });

        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (! Schema::hasColumn('atk_transaction_items', 'atk_transaction_id')) {
                $table->foreignId('atk_transaction_id')->after('id')->constrained('atk_transactions')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('atk_transaction_items', 'product_id')) {
                if (Schema::hasTable('atk_products')) {
                    $table->foreignId('product_id')->nullable()->after('atk_transaction_id')->constrained('atk_products')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('product_id')->nullable()->after('atk_transaction_id');
                }
            }
            if (! Schema::hasColumn('atk_transaction_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id'); // Made nullable
            }
            if (! Schema::hasColumn('atk_transaction_items', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('product_name');
            }
            if (! Schema::hasColumn('atk_transaction_items', 'quantity')) {
                $table->integer('quantity')->default(1)->after('price');
            }
            if (! Schema::hasColumn('atk_transaction_items', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_transactions', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'transaction_number', 'total_amount', 'payment_method', 'cash_amount', 'change_amount', 'amount_paid']);
        });

        Schema::table('atk_transaction_items', function (Blueprint $table) {
            $table->dropForeign(['atk_transaction_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['atk_transaction_id', 'product_id', 'product_name', 'price', 'quantity', 'subtotal']);
        });
    }
};
