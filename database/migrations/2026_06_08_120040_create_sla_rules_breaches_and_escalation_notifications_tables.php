<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sla_breaches')) {
            Schema::create('sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('sla_rule_id')->constrained('sla_rules')->cascadeOnDelete();
            $table->timestamp('breached_at')->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->string('current_status', 20)->index();
            $table->timestamps();

            $table->unique(['ticket_id', 'sla_rule_id']);
            $table->index(['ticket_id', 'current_status']);
            });
        }

        if (! Schema::hasTable('escalation_notifications')) {
            Schema::create('escalation_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('sla_rule_id')->nullable()->constrained('sla_rules')->nullOnDelete();

            $table->string('channel', 20)->index();
            $table->string('target', 120)->nullable()->index();
            $table->string('recipient_role', 30)->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempt')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'channel', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_notifications');
        Schema::dropIfExists('sla_breaches');
    }
};
