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
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_transactions', 'vehicle_plate')) {
                $table->string('vehicle_plate')->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('wash_transactions', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('transaction_number');
            }
            if (! Schema::hasColumn('wash_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('change_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropColumn(['vehicle_plate', 'customer_name', 'notes']);
        });
    }
};
