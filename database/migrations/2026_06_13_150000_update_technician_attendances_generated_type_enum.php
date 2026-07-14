<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('technician_attendances') || ! Schema::hasColumn('technician_attendances', 'generated_type')) {
            return;
        }

        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->enum('generated_type', [
                'manual',
                'system_alpha',
                'system_leave',
                'leave_request',
                'leave_request_rejected',
            ])->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('technician_attendances') || ! Schema::hasColumn('technician_attendances', 'generated_type')) {
            return;
        }

        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->enum('generated_type', [
                'manual',
                'system_alpha',
                'system_leave',
            ])->nullable()->default(null)->change();
        });
    }
};
