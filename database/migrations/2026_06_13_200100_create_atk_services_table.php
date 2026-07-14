<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_services')) {
            Schema::create('atk_services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('cost_price', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_services');
    }
};
