<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_member_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_member_packages', 'type')) {
                $table->string('type', 16)->default('wash')->after('code');
            }
            if (! Schema::hasColumn('wash_member_packages', 'network_type')) {
                $table->string('network_type', 16)->nullable()->after('type');
            }
            if (! Schema::hasColumn('wash_member_packages', 'hotspot_profile_id')) {
                $table->foreignId('hotspot_profile_id')->nullable()->after('network_type')
                    ->constrained('hotspot_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('wash_member_packages', 'pppoe_profile')) {
                $table->string('pppoe_profile', 64)->nullable()->after('hotspot_profile_id');
            }
            if (! Schema::hasColumn('wash_member_packages', 'rate_limit_mbps')) {
                $table->decimal('rate_limit_mbps', 8, 2)->nullable()->after('pppoe_profile');
            }
            if (! Schema::hasColumn('wash_member_packages', 'daily_wifi_minutes')) {
                $table->unsignedInteger('daily_wifi_minutes')->nullable()->after('rate_limit_mbps');
            }
            if (! Schema::hasColumn('wash_member_packages', 'router_id')) {
                $table->foreignId('router_id')->nullable()->after('daily_wifi_minutes')
                    ->constrained('routers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_member_packages', function (Blueprint $table) {
            $cols = ['type', 'network_type', 'pppoe_profile', 'rate_limit_mbps', 'daily_wifi_minutes'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('wash_member_packages', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (['router_id', 'hotspot_profile_id'] as $fcol) {
                if (Schema::hasColumn('wash_member_packages', $fcol)) {
                    try {
                        $table->dropConstrainedForeignId($fcol);
                    } catch (\Throwable $e) {
                        if (Schema::hasColumn('wash_member_packages', $fcol)) {
                            $table->dropColumn($fcol);
                        }
                    }
                }
            }
        });
    }
};
