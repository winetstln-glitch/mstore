<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            if (!Schema::hasColumn('olts', 'snmp_version')) {
                $table->string('snmp_version', 10)->default('2c')->after('longitude');
            }
            if (!Schema::hasColumn('olts', 'snmp_community')) {
                $table->string('snmp_community')->nullable()->after('snmp_version');
            }
            if (!Schema::hasColumn('olts', 'snmp_port')) {
                $table->integer('snmp_port')->default(161)->after('snmp_community');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            if (Schema::hasColumn('olts', 'snmp_port')) {
                $table->dropColumn('snmp_port');
            }
            if (Schema::hasColumn('olts', 'snmp_community')) {
                $table->dropColumn('snmp_community');
            }
            if (Schema::hasColumn('olts', 'snmp_version')) {
                $table->dropColumn('snmp_version');
            }
        });
    }
};

