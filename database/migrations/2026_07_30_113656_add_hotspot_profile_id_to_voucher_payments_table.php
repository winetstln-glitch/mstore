<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('voucher_payments', 'hotspot_profile_id')) {
                $table->foreignId('hotspot_profile_id')
                    ->nullable()
                    ->after('voucher_template_id')
                    ->constrained('hotspot_profiles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_payments', 'hotspot_profile_id')) {
                $table->dropConstrainedForeignId('hotspot_profile_id');
            }
        });
    }
};
