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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->foreignId('coordinator_id')->nullable()->constrained('coordinators')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['odp_id']);
            $table->dropForeign(['coordinator_id']);
            $table->dropColumn(['odp_id', 'coordinator_id']);
        });
    }
};
