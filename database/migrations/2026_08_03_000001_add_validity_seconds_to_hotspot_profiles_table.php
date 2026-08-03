<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('hotspot_profiles', 'validity_seconds')) {
                $table->unsignedBigInteger('validity_seconds')->nullable()->after('duration_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('hotspot_profiles', 'validity_seconds')) {
                $table->dropColumn('validity_seconds');
            }
        });
    }
};
