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
            if (! Schema::hasColumn('wash_transactions', 'queue_number')) {
                $table->integer('queue_number')->nullable()->after('transaction_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'queue_number')) {
                $table->dropColumn('queue_number');
            }
        });
    }
};
