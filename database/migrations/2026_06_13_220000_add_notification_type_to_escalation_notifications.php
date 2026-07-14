<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escalation_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('escalation_notifications', 'notification_type')) {
                $table->enum('notification_type', ['warning', 'critical', 'breach'])->default('breach')->after('sla_rule_id');
                $table->index('notification_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('escalation_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('escalation_notifications', 'notification_type')) {
                $table->dropColumn('notification_type');
            }
        });
    }
};
