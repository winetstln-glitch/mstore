<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'hotspot_profile_id')) {
                $table->foreignId('hotspot_profile_id')->nullable()->constrained('hotspot_profiles')->nullOnDelete()->after('package_id');
            }
        });

        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'hotspot_profile_id')) {
                $table->foreignId('hotspot_profile_id')->nullable()->constrained('hotspot_profiles')->nullOnDelete()->after('voucher_template_id');
            }
        });

        Schema::table('voucher_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('voucher_batches', 'hotspot_profile_id')) {
                $table->foreignId('hotspot_profile_id')->nullable()->constrained('hotspot_profiles')->nullOnDelete()->after('profile');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voucher_batches', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_batches', 'hotspot_profile_id')) {
                $table->dropConstrainedForeignId('hotspot_profile_id');
            }
        });

        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'hotspot_profile_id')) {
                $table->dropConstrainedForeignId('hotspot_profile_id');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'hotspot_profile_id')) {
                $table->dropConstrainedForeignId('hotspot_profile_id');
            }
        });
    }
};
