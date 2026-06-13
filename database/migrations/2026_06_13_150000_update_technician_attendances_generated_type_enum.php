<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update enum to add new values
        DB::statement("ALTER TABLE `technician_attendances` MODIFY COLUMN `generated_type` ENUM('manual', 'system_alpha', 'system_leave', 'leave_request', 'leave_request_rejected') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE `technician_attendances` MODIFY COLUMN `generated_type` ENUM('manual', 'system_alpha', 'system_leave') NULL DEFAULT NULL");
    }
};
