<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wash_service_price_rules')) {
            return;
        }

        Schema::create('wash_service_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_service_id')->constrained('wash_services')->cascadeOnDelete();
            $table->string('vehicle_type')->nullable();
            $table->string('size_tier')->default('none');
            $table->string('package_type')->default('general');
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['wash_service_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_service_price_rules');
    }
};
