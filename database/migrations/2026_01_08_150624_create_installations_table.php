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
        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['registered', 'survey', 'approved', 'installation', 'completed', 'cancelled'])->default('registered');
            $table->date('plan_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('coordinates')->nullable(); // Lat,Long
            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
