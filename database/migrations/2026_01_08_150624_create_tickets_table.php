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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // gangguan, pasang_baru, maintenance
            $table->string('priority')->default('medium'); // low, medium, high
            $table->enum('status', ['open', 'assigned', 'in_progress', 'pending', 'solved', 'closed'])->default('open');
            $table->text('description')->nullable();
            $table->string('location')->nullable(); // Coordinates or address override
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
