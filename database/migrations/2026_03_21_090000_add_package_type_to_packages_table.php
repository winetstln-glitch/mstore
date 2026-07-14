<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages') && ! Schema::hasColumn('packages', 'package_type')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->string('package_type', 20)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'package_type')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('package_type');
            });
        }
    }
};
