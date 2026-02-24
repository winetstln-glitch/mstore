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
        Schema::table('wash_transaction_items', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_transaction_items', 'service_name')) {
                $table->string('service_name')->nullable()->after('wash_service_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transaction_items', 'service_name')) {
                $table->dropColumn('service_name');
            }
        });
    }
};
