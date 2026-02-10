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
        if (!Schema::hasTable('atk_products')) {
            Schema::create('atk_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique()->nullable();
                $table->string('category')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('cost_price', 15, 2)->default(0);
                $table->integer('stock')->default(0);
                $table->string('unit')->nullable();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        } else {
            Schema::table('atk_products', function (Blueprint $table) {
                if (!Schema::hasColumn('atk_products', 'name')) {
                    $table->string('name')->after('id');
                }
                if (!Schema::hasColumn('atk_products', 'code')) {
                    $table->string('code')->unique()->nullable()->after('name');
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
                if (!Schema::hasColumn('atk_products', 'image')) {
                    $table->string('image')->nullable()->after('description');
                }
                if (!Schema::hasColumn('atk_products', 'employee_id')) {
                    $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete()->after('image');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_products', function (Blueprint $table) {
            if (Schema::hasColumn('atk_products', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
        });
        Schema::dropIfExists('atk_products');
    }
};
