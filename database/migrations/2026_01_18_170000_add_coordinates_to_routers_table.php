<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (!Schema::hasColumn('routers', 'location')) {
                $table->string('location')->nullable()->after('username');
            }
            if (!Schema::hasColumn('routers', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('location');
            }
            if (!Schema::hasColumn('routers', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (Schema::hasColumn('routers', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('routers', 'latitude')) {
                $table->dropColumn('latitude');
            }
            if (Schema::hasColumn('routers', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};

