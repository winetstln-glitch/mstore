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
        Schema::table('technician_daily_schedules', function (Blueprint $table) {
            $table->string('status')->default('off')->change();
        });

        Schema::table('technician_schedules', function (Blueprint $table) {
            $table->string('status')->default('off')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_daily_schedules', function (Blueprint $table) {
            $table->enum('status', ['piket', 'off', 'backup', 'longshift'])->default('off')->change();
        });

        Schema::table('technician_schedules', function (Blueprint $table) {
            $table->enum('status', ['piket', 'off', 'backup', 'longshift'])->default('off')->change();
        });
    }
};
