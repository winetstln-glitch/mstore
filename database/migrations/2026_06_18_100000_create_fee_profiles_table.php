<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fee_profiles')) {
            Schema::create('fee_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('transaction_type')->index();
                $table->enum('fee_mode', ['fixed', 'percentage', 'fixed_percentage', 'tier', 'cost_plus', 'custom'])->default('fixed');
                $table->text('custom_formula')->nullable();
                $table->decimal('cost_price', 15, 2)->nullable();
                $table->decimal('markup_value', 15, 2)->nullable();
                $table->enum('markup_type', ['fixed', 'percentage'])->default('fixed')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('allow_override')->default(false);
                $table->string('module')->default('atk');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_profiles');
    }
};
