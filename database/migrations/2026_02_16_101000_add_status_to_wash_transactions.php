<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_transactions', 'status')) {
                $table->string('status')->default('lunas')->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
