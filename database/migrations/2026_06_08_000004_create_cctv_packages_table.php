<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cctv_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->unsignedInteger('camera_count')->nullable();
            $table->string('dvr_nvr')->nullable();
            $table->string('hdd')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('warranty_months')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cctv_packages');
    }
};

