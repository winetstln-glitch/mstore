<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpn_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('ip_public');
            $table->unsignedInteger('port')->default(1701);
            $table->enum('protocol', ['l2tp', 'pptp', 'sstp', 'openvpn'])->default('l2tp');
            $table->enum('status', ['active', 'maintenance'])->default('active');
            $table->unsignedInteger('last_reported_load')->nullable();
            $table->unsignedInteger('last_latency_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_servers');
    }
};
