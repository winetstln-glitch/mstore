<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wash_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_cash_register_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('wash_shift_session_id')->nullable()->constrained();
            $table->string('type'); // in, out
            $table->decimal('amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->text('description');
            $table->timestamp('movement_date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wash_cash_movements');
    }
};
