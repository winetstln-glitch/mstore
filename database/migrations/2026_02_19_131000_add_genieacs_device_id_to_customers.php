<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'genieacs_device_id')) {
                $table->string('genieacs_device_id')->nullable()->after('ip_address')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'genieacs_device_id')) {
                $table->dropColumn('genieacs_device_id');
            }
        });
    }
};
