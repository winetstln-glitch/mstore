<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('whatsapp_analytics_logs')) {
            return;
        }

        Schema::create('whatsapp_analytics_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number')->index();
            $table->enum('message_type', ['incoming', 'outgoing']);
            $table->longText('content')->nullable();
            $table->string('intent')->nullable()->index();
            $table->boolean('ai_handled')->default(false);
            $table->boolean('ai_resolved')->default(false);
            $table->string('media_type')->nullable();
            $table->string('media_url')->nullable();
            $table->foreignId('related_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('related_payment_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
            $table->index(['message_type', 'created_at']);
            $table->index(['ai_handled', 'ai_resolved']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_analytics_logs');
    }
};
