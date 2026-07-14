<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wash_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_member_id')->nullable()->constrained();
            $table->foreignId('wash_customer_id')->nullable()->constrained();
            $table->foreignId('wash_transaction_id')->nullable()->constrained();
            $table->string('type'); // earn, redeem
            $table->integer('points');
            $table->integer('balance_after');
            $table->text('description');
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wash_point_transactions');
    }
};
