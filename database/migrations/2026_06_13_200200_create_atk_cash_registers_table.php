<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_cash_registers')) {
            Schema::create('atk_cash_registers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('initial_balance', 15, 2)->default(0);
                $table->decimal('final_balance', 15, 2)->nullable();
                $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_cash_registers');
    }
};
