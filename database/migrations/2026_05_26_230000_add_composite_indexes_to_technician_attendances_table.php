<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('technician_attendances', function (Blueprint $table) {
                $table->index(['user_id', 'work_date']);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('technician_attendances', function (Blueprint $table) {
                $table->index(['user_id', 'clock_in']);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    public function down(): void
    {
        try {
            Schema::table('technician_attendances', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'work_date']);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
        
        try {
            Schema::table('technician_attendances', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'clock_in']);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
    }
};
