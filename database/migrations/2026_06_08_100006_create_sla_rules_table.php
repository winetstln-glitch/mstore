<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('sla_rules')) {
            return;
        }

        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('ticket_category')->nullable();
            $table->integer('warning_threshold_hours')->default(24);
            $table->integer('critical_threshold_hours')->default(48);
            $table->integer('escalation_threshold_hours')->default(72);
            $table->json('escalation_recipients')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (!Schema::hasColumns('tickets', ['sla_status', 'last_escalation_level', 'first_response_at', 'resolved_at', 'sla_breached_at'])) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->enum('sla_status', ['ok', 'warning', 'critical', 'breached'])->default('ok')->after('status');
                $table->integer('last_escalation_level')->default(0)->after('sla_status');
                $table->timestamp('first_response_at')->nullable()->after('last_escalation_level');
                $table->timestamp('resolved_at')->nullable()->after('first_response_at');
                $table->timestamp('sla_breached_at')->nullable()->after('resolved_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sla_rules');

        if (Schema::hasColumns('tickets', ['sla_status', 'last_escalation_level', 'first_response_at', 'resolved_at', 'sla_breached_at'])) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn(['sla_status', 'last_escalation_level', 'first_response_at', 'resolved_at', 'sla_breached_at']);
            });
        }
    }
};
