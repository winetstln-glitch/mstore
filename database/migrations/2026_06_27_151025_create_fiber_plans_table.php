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
        Schema::create('fiber_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('olt_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('path')->nullable();
            $table->decimal('length_meters', 10, 2)->default(0);
            $table->string('status')->default('draft'); // draft, active, archived
            $table->json('meta_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiber_plans');
    }
};
