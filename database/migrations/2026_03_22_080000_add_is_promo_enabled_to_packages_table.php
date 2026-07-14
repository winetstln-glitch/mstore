<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages') && ! Schema::hasColumn('packages', 'is_promo_enabled')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->boolean('is_promo_enabled')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'is_promo_enabled')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('is_promo_enabled');
            });
        }
    }
};
