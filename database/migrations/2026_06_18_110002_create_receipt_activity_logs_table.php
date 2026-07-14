<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('receipt_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->string('action'); // print, reprint, download, share, verify
            $table->string('channel')->nullable(); // whatsapp, email, telegram, etc.
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('receipt_id')->references('id')->on('receipts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('receipt_activity_logs');
    }
};
