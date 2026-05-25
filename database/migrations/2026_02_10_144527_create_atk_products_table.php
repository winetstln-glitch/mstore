<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations safely.
     */
    public function up(): void
    {
        // Buat tabel hanya jika belum ada
        if (! Schema::hasTable('atk_products')) {
            Schema::create('atk_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations safely.
     */
    public function down(): void
    {
        // Hapus tabel hanya jika ada
        if (Schema::hasTable('atk_products')) {
            Schema::drop('atk_products');
        }
    }
};
