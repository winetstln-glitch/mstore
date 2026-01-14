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
        if (!Schema::hasTable('onus')) {
            Schema::create('onus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
                $table->string('name')->nullable();
                $table->string('serial_number')->unique();
                $table->string('mac_address')->nullable();
                $table->string('interface'); // e.g., gpon-onu_1/2/1:1
                $table->string('status')->default('offline'); // online, offline, los, power_fail
                $table->string('signal')->nullable(); // e.g., -20.5 dBm
                $table->integer('distance')->nullable(); // in meters
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onus');
    }
};
