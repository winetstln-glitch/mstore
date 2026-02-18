<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('atk_transactions', 'coordinator_id')) {
                    if (Schema::hasTable('coordinators')) {
                        $table->foreignId('coordinator_id')->nullable()->after('change_amount')->constrained('coordinators')->nullOnDelete();
                    } else {
                        $table->unsignedBigInteger('coordinator_id')->nullable()->after('change_amount');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('atk_transactions', 'coordinator_id')) {
                    try {
                        $table->dropForeign(['coordinator_id']);
                    } catch (\Throwable $e) {
                        // ignore if foreign key doesn't exist
                    }
                    $table->dropColumn('coordinator_id');
                }
            });
        }
    }
};

