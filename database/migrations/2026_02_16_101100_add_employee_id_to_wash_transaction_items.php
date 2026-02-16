<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_transaction_items', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transaction_items', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('wash_service_id')->constrained('wash_employees')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transaction_items', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }
        });
    }
};
