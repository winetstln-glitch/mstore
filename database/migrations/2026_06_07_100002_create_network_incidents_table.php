<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('network_incidents')) {
            return;
        }

        Schema::create('network_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['fiber_cut', 'olt_down', 'odp_down', 'power_outage', 'maintenance', 'other'])->default('other');
            $table->enum('status', ['detected', 'investigating', 'in_progress', 'resolved', 'closed'])->default('detected');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('olt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('estimated_resolution_at')->nullable();
            $table->json('affected_customers')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('network_incidents');
    }
};
