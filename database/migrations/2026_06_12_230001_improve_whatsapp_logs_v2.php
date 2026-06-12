<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Duplicate protection
            if (!Schema::hasColumn('whatsapp_logs', 'duplicate_count')) {
                $table->integer('duplicate_count')->default(0);
            }
            if (!Schema::hasColumn('whatsapp_logs', 'duplicate_detected_at')) {
                $table->timestamp('duplicate_detected_at')->nullable();
            }
            
            // AI fields
            if (!Schema::hasColumn('whatsapp_logs', 'ai_confidence')) {
                $table->decimal('ai_confidence', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'detected_intent')) {
                $table->string('detected_intent')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'ai_history_id')) {
                $table->foreignId('ai_history_id')->nullable();
            }
            
            // CS & Customer
            if (!Schema::hasColumn('whatsapp_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'customer_id')) {
                $table->foreignId('customer_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_logs', 'duplicate_count')) {
                $table->dropColumn('duplicate_count');
            }
            if (Schema::hasColumn('whatsapp_logs', 'duplicate_detected_at')) {
                $table->dropColumn('duplicate_detected_at');
            }
            if (Schema::hasColumn('whatsapp_logs', 'ai_confidence')) {
                $table->dropColumn('ai_confidence');
            }
            if (Schema::hasColumn('whatsapp_logs', 'detected_intent')) {
                $table->dropColumn('detected_intent');
            }
            if (Schema::hasColumn('whatsapp_logs', 'ai_history_id')) {
                $table->dropColumn('ai_history_id');
            }
            if (Schema::hasColumn('whatsapp_logs', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('whatsapp_logs', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
            
            // Drop indexes
            try {
                $table->dropIndex(['provider_message_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropIndex(['conversation_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropIndex(['sender_type']);
            } catch (\Exception $e) {}
        });
    }
};
