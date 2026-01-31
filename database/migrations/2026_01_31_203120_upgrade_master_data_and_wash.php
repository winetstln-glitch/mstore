<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['product', 'service', 'both'])->default('both');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Upgrade ATK Products
        Schema::table('atk_products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('categories')->nullOnDelete();
        });

        // 3. Upgrade Wash Services
        Schema::table('wash_services', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('categories')->nullOnDelete();
            $table->decimal('cost_price', 12, 2)->default(0)->after('price'); // Modal
            $table->integer('stock')->default(0)->nullable()->after('vehicle_type'); // For physical items
            $table->enum('type', ['service', 'physical'])->default('service')->after('name');
        });

        // 4. Upgrade Wash Transactions
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('transaction_code')->constrained('customers')->nullOnDelete();
            // Status update if needed (already has pending, completed, cancelled)
            // Maybe add 'washing' status?
            // $table->enum('status', ...)->change(); // Requires dbal
        });

        // 5. Upgrade Wash Transaction Items
        Schema::table('wash_transaction_items', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('subtotal')->constrained('users')->nullOnDelete();
        });

        // 6. Upgrade Customers (Loyalty)
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('loyalty_points')->default(0)->after('phone');
        });

        // 7. Loyalty Logs
        Schema::create('loyalty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('wash_transaction_id')->nullable()->constrained('wash_transactions')->nullOnDelete();
            $table->integer('points'); // Positive (earned) or Negative (redeemed)
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_logs');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('loyalty_points');
        });

        Schema::table('wash_transaction_items', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('wash_services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'cost_price', 'stock', 'type']);
        });

        Schema::table('atk_products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
