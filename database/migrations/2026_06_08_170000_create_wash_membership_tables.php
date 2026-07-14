<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wash_member_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 64);
            $table->unsignedInteger('min_transactions')->default(0);
            $table->unsignedInteger('max_transactions')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->unsignedInteger('priority_rank')->default(0);
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wash_members', function (Blueprint $table) {
            $table->id();
            $table->string('member_number', 32)->unique();
            $table->string('name')->index();
            $table->string('whatsapp', 32)->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->foreignId('wash_member_level_id')->nullable()->constrained('wash_member_levels')->nullOnDelete();
            $table->unsignedInteger('total_transactions')->default(0);
            $table->unsignedInteger('total_visits')->default(0);
            $table->decimal('total_spending', 15, 2)->default(0);
            $table->string('status', 16)->default('active')->index();
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wash_member_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_member_id')->constrained('wash_members')->cascadeOnDelete();
            $table->string('vehicle_plate')->unique();
            $table->string('vehicle_type', 16)->nullable();
            $table->string('brand', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wash_member_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_member_id')->constrained('wash_members')->cascadeOnDelete();
            $table->string('card_number', 32)->unique();
            $table->string('verification_token', 64)->unique();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('status', 16)->default('active')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_member_cards');
        Schema::dropIfExists('wash_member_vehicles');
        Schema::dropIfExists('wash_members');
        Schema::dropIfExists('wash_member_levels');
    }
};

