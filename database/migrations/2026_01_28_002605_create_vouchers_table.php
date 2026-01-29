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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotspot_profile_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('password')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('batch_id')->nullable()->index(); // For grouping prints
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
