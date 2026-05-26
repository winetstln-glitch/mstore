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
        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->timestamp('clock_in')->nullable()->change();
            $table->timestamp('clock_out')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->timestamp('clock_in')->nullable(false)->change();
            $table->timestamp('clock_out')->nullable(false)->change();
        });
    }
};
