<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cctv_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->index();
            $table->string('customer_whatsapp')->index();
            $table->text('address');
            $table->foreignId('cctv_package_id')->constrained('cctv_packages')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->unsignedBigInteger('quotation_amount')->nullable();
            $table->unsignedBigInteger('dp_amount')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cctv_bookings');
    }
};

