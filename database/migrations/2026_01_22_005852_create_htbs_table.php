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
        Schema::create('htbs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('odp_id')->constrained('odps')->onDelete('cascade');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('filled')->default(0);
            $table->text('description')->nullable();
            $table->string('color')->nullable()->default('#007bff'); // Default Blue
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('htbs');
    }
};
