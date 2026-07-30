<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('hotspot_profiles', 'name')) {
                $table->string('name', 100)->after('id');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'mikrotik_profile_name')) {
                $table->string('mikrotik_profile_name', 64)->nullable()->after('name');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'package_type')) {
                $table->string('package_type', 16)->default('voucher')->after('mikrotik_profile_name');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'rate_limit_mbps')) {
                $table->decimal('rate_limit_mbps', 8, 2)->nullable()->after('package_type');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'shared_users')) {
                $table->unsignedInteger('shared_users')->default(1)->after('rate_limit_mbps');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'limit_uptime')) {
                $table->string('limit_uptime', 32)->nullable()->after('shared_users');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'duration_seconds')) {
                $table->unsignedBigInteger('duration_seconds')->nullable()->after('limit_uptime');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'quota_mb')) {
                $table->unsignedBigInteger('quota_mb')->nullable()->after('duration_seconds');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('quota_mb');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'description')) {
                $table->text('description')->nullable()->after('price');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'color_badge')) {
                $table->string('color_badge', 16)->nullable()->after('description');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'router_id')) {
                $table->foreignId('router_id')->nullable()->after('color_badge')->constrained('routers')->nullOnDelete();
            }
            if (! Schema::hasColumn('hotspot_profiles', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('router_id');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
            if (! Schema::hasColumn('hotspot_profiles', 'meta')) {
                $table->json('meta')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_profiles', function (Blueprint $table) {
            $dropCols = ['name', 'mikrotik_profile_name', 'package_type', 'rate_limit_mbps', 'shared_users',
                'limit_uptime', 'duration_seconds', 'quota_mb', 'price', 'description', 'color_badge',
                'sort_order', 'is_active', 'meta'];
            foreach ($dropCols as $col) {
                if (Schema::hasColumn('hotspot_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('hotspot_profiles', 'router_id')) {
                $table->dropConstrainedForeignId('router_id');
            }
        });
    }
};
