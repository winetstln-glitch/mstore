<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('transaction_type');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('receipt_template_id')->nullable();
            $table->string('status')->default('valid'); // valid, invalid, canceled
            $table->text('verification_url')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('barcode_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('receipt_template_id')->references('id')->on('receipt_templates')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['transaction_type', 'transaction_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('receipts');
    }
};
