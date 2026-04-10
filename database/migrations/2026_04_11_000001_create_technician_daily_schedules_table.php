<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_daily_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['piket', 'backup', 'off'])->default('off');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_daily_schedules');
    }
};
