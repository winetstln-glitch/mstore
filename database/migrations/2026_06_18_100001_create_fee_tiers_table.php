<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fee_tiers')) {
            Schema::create('fee_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fee_profile_id')->constrained('fee_profiles')->cascadeOnDelete();
                $table->decimal('min_amount', 15, 2)->default(0);
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->enum('fee_type', ['fixed', 'percentage', 'fixed_percentage'])->default('fixed');
                $table->decimal('fee_value', 15, 2)->default(0);
                $table->decimal('fixed_value', 15, 2)->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_tiers');
    }
};
