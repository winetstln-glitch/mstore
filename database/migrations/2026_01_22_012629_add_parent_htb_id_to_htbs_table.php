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
        Schema::table('htbs', function (Blueprint $table) {
            $table->foreignId('parent_htb_id')->nullable()->after('odp_id')->constrained('htbs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('htbs', function (Blueprint $table) {
            $table->dropForeign(['parent_htb_id']);
            $table->dropColumn('parent_htb_id');
        });
    }
};
