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
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('atk_transactions', 'coordinator_id')) {
                    if (Schema::hasTable('coordinators')) {
                        $table->foreignId('coordinator_id')->nullable()->after('user_id')->constrained('coordinators')->nullOnDelete();
                    } else {
                        $table->unsignedBigInteger('coordinator_id')->nullable()->after('user_id');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('atk_transactions', 'coordinator_id')) {
                    $table->dropConstrainedForeignId('coordinator_id');
                }
            });
        }
    }
};

