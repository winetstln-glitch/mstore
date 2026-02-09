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
        Schema::create('wash_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->integer('visit_count')->default(0);
            $table->integer('free_wash_eligibility')->default(0); // Number of free washes available
            $table->timestamps();
        });

        Schema::table('wash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transactions', 'vehicle_brand')) {
                $table->string('vehicle_brand')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('wash_transactions', 'wash_customer_id')) {
                $table->foreignId('wash_customer_id')->nullable()->after('vehicle_brand')->constrained('wash_customers')->nullOnDelete();
            }
            if (!Schema::hasColumn('wash_transactions', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('wash_transactions', 'plate_number')) {
                $table->string('plate_number')->nullable()->after('vehicle_brand');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropForeign(['wash_customer_id']);
            $table->dropColumn(['vehicle_brand', 'wash_customer_id', 'discount_amount', 'plate_number']);
        });

        Schema::dropIfExists('wash_customers');
    }
};
