<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cctv_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cctv_booking_id')->constrained('cctv_bookings')->cascadeOnDelete();
            $table->timestamp('survey_date')->nullable()->index();
            $table->foreignId('surveyor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('location');
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cctv_surveys');
    }
};

