<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->index();
            $table->string('customer_whatsapp')->index();
            $table->date('event_date')->index();
            $table->string('location');
            $table->foreignId('wedding_package_id')->constrained('wedding_packages')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->unsignedBigInteger('quotation_amount')->nullable();
            $table->unsignedBigInteger('dp_amount')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_bookings');
    }
};

