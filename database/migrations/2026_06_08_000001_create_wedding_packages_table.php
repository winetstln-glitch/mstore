<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('capacity')->nullable();
            $table->json('facilities')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_packages');
    }
};

