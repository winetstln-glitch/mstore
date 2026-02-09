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
        Schema::table('atk_products', function (Blueprint $table) {
            // Check if columns exist before adding them to avoid duplication errors if partial migration happened
            if (!Schema::hasColumn('atk_products', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('atk_products', 'code')) {
                $table->string('code')->unique()->after('name');
            }
            if (!Schema::hasColumn('atk_products', 'category')) {
                $table->string('category')->nullable()->after('code');
            }
            if (!Schema::hasColumn('atk_products', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('category');
            }
            if (!Schema::hasColumn('atk_products', 'cost_price')) {
                $table->decimal('cost_price', 15, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('atk_products', 'stock')) {
                $table->integer('stock')->default(0)->after('cost_price');
            }
            if (!Schema::hasColumn('atk_products', 'unit')) {
                $table->string('unit')->nullable()->after('stock');
            }
            if (!Schema::hasColumn('atk_products', 'description')) {
                $table->text('description')->nullable()->after('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_products', function (Blueprint $table) {
            $table->dropColumn(['name', 'code', 'category', 'price', 'cost_price', 'stock', 'unit', 'description']);
        });
    }
};
