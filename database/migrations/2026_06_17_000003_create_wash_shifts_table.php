<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wash_shift_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_shift_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('wash_cash_register_id')->nullable()->constrained();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wash_shift_sessions');
    }
};
