<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_logs', 'conversation_id')) {
                $table->string('conversation_id')->nullable()->index();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'sender_type')) {
                $table->string('sender_type')->nullable()->default('unknown');
            }
            if (!Schema::hasColumn('whatsapp_logs', 'message_type')) {
                $table->string('message_type')->nullable()->default('text');
            }
            if (!Schema::hasColumn('whatsapp_logs', 'processing_time_ms')) {
                $table->unsignedInteger('processing_time_ms')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'ai_history_id')) {
                $table->unsignedBigInteger('ai_history_id')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            }
            // Add indexes only if they don't exist (for SQLite compatibility)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn([
                'conversation_id',
                'sender_type',
                'message_type',
                'processing_time_ms',
                'ai_history_id',
                'user_id',
                'customer_id',
            ]);
        });
    }
};
