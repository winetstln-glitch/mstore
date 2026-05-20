<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            // Drop unique index by known name
            $table->dropUnique('onus_serial_number_unique');
            
            // Make serial_number nullable
            $table->string('serial_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->string('serial_number')->unique()->change();
        });
    }
};
