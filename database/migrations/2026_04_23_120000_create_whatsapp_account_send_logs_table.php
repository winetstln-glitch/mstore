<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_account_send_logs')) {
            return;
        }

        Schema::create('whatsapp_account_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_phone', 30)->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('message_excerpt', 255)->nullable();
            $table->boolean('password_included')->default(false);
            $table->string('error_message', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_account_send_logs');
    }
};
