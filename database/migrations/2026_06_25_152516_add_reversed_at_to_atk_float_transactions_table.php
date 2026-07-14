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
        Schema::table('atk_float_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('atk_float_transactions', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('atk_float_transactions', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_float_transactions', function (Blueprint $table) {
            $table->dropForeign(['reversed_by']);
            $table->dropColumn(['reversed_at', 'reversed_by']);
        });
    }
};
