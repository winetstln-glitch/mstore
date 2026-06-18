<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('receipt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('transaction_type')->default('pos'); // pos, bank, cashout, topup, ppob, qris, receivable, expense
            $table->string('size')->default('80mm'); // 80mm, 58mm, A4
            $table->string('orientation')->default('portrait');
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_qr')->default(true);
            $table->boolean('show_barcode')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('receipt_templates');
    }
};
