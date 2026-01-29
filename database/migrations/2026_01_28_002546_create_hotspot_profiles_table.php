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
        Schema::create('hotspot_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('shared_users')->default(1);
            $table->string('rate_limit')->nullable(); // e.g., "1M/1M"
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('validity_value')->nullable(); // e.g., 24
            $table->string('validity_unit')->nullable(); // e.g., "hours", "days"
            $table->string('mikrotik_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_profiles');
    }
};
