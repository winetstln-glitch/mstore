<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('technician_assignments')) {
            return;
        }

        Schema::create('technician_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->string('assignment_key')->unique()->index();
            $table->enum('status', ['assigned', 'accepted', 'in_progress', 'completed', 'cancelled', 'escalated'])->default('assigned');
            $table->decimal('score', 5, 2)->nullable();
            $table->json('scoring_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('technician_assignments');
    }
};
