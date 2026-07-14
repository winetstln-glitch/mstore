<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Kode unit bisnis: ISP, ATK, WASH, CCTV, WEDDING');
            $table->string('name', 100);
            $table->enum('type', ['ISP', 'RETAIL', 'SERVICE', 'INVESTMENT'])->default('SERVICE');
            $table->string('tax_id', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_units');
    }
};
