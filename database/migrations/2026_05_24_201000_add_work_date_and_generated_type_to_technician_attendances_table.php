<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->date('work_date')->nullable()->after('user_id');
            $table->enum('generated_type', ['manual', 'system_alpha', 'system_leave'])->nullable()->after('status');
            $table->index(['user_id', 'work_date']);
            $table->index(['user_id', 'clock_in']);
        });
    }

    public function down(): void
    {
        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'work_date']);
            $table->dropIndex(['user_id', 'clock_in']);
            $table->dropColumn(['work_date', 'generated_type']);
        });
    }
};
