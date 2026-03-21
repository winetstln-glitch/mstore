<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages') && ! Schema::hasColumn('packages', 'devices_limit')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->unsignedInteger('devices_limit')->nullable()->after('speed');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'devices_limit')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('devices_limit');
            });
        }
    }
};
