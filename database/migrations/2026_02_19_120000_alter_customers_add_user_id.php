<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
