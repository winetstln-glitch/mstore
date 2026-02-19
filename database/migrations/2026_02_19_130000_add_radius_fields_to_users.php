<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'radius_username')) {
                $table->string('radius_username')->nullable()->unique()->after('username');
            }
            if (!Schema::hasColumn('users', 'radius_type')) {
                $table->string('radius_type')->nullable()->after('radius_username'); // pppoe|hotspot
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'radius_type')) {
                $table->dropColumn('radius_type');
            }
            if (Schema::hasColumn('users', 'radius_username')) {
                $table->dropUnique(['radius_username']);
                $table->dropColumn('radius_username');
            }
        });
    }
};
