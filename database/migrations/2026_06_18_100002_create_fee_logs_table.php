<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fee_logs')) {
            Schema::create('fee_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fee_profile_id')->nullable()->constrained('fee_profiles')->nullOnDelete();
                $table->string('transaction_type')->index();
                $table->foreignId('transaction_id')->nullable()->index();
                $table->decimal('nominal', 15, 2)->default(0);
                $table->decimal('calculated_fee', 15, 2)->default(0);
                $table->decimal('manual_fee', 15, 2)->nullable();
                $table->decimal('final_fee', 15, 2)->default(0);
                $table->text('reason')->nullable();
                $table->string('module')->default('atk');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_logs');
    }
};
