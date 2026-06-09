<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noc_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('captured_at')->index();

            $table->unsignedInteger('onu_online')->default(0);
            $table->unsignedInteger('onu_offline')->default(0);
            $table->unsignedInteger('onu_los')->default(0);
            $table->unsignedInteger('onu_dying_gasp')->default(0);
            $table->unsignedInteger('onu_weak_signal')->default(0);

            $table->unsignedInteger('pppoe_online')->default(0);
            $table->unsignedInteger('pppoe_offline')->default(0);
            $table->unsignedInteger('pppoe_active_sessions')->default(0);
            $table->unsignedInteger('pppoe_total_users')->default(0);

            $table->unsignedInteger('outage_active')->default(0);
            $table->unsignedInteger('outage_maintenance')->default(0);
            $table->unsignedInteger('outage_fiber_cut')->default(0);
            $table->unsignedInteger('outage_olt_down')->default(0);

            $table->unsignedInteger('ticket_open')->default(0);
            $table->unsignedInteger('ticket_in_progress')->default(0);
            $table->unsignedInteger('ticket_pending')->default(0);
            $table->unsignedInteger('ticket_closed')->default(0);

            $table->unsignedInteger('technician_online')->default(0);
            $table->unsignedInteger('technician_offline')->default(0);
            $table->unsignedInteger('technician_handling_ticket')->default(0);
            $table->unsignedInteger('technician_available')->default(0);

            $table->unsignedTinyInteger('network_health_score')->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['captured_at', 'network_health_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noc_metric_snapshots');
    }
};

