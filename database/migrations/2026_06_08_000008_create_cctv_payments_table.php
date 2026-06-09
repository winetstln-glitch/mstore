<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cctv_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cctv_booking_id')->constrained('cctv_bookings')->cascadeOnDelete();
            $table->string('type')->index();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cctv_payments');
    }
};

