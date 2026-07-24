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
            $table->timestamp('loyalty_counted_at')->nullable()->after('posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropColumn('loyalty_counted_at');
        });
    }
};
