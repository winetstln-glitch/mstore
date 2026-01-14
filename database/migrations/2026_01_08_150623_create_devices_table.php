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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('ONU'); // ONU, Router
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('sn')->nullable(); // Serial Number
            $table->string('olt_port')->nullable();
            $table->string('vlan')->nullable();
            $table->string('status')->default('offline'); // online, offline
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
