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
        // Update whatsapp_conversations
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_conversations', 'is_group')) {
                $table->boolean('is_group')->default(false)->after('phone_number');
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'group_id')) {
                $table->string('group_id')->nullable()->after('is_group')->index();
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'last_intent')) {
                $table->string('last_intent')->nullable()->after('status');
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'confidence_score')) {
                $table->integer('confidence_score')->nullable()->after('last_intent');
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'sender_type')) {
                $table->enum('sender_type', ['customer', 'bot', 'agent', 'system'])->default('customer')->after('confidence_score');
            }
        });

        // Update whatsapp_logs
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_logs', 'is_group')) {
                $table->boolean('is_group')->default(false)->after('phone_number');
            }
            if (!Schema::hasColumn('whatsapp_logs', 'group_id')) {
                $table->string('group_id')->nullable()->after('is_group')->index();
            }
            if (!Schema::hasColumn('whatsapp_logs', 'intent')) {
                $table->string('intent')->nullable()->after('message');
            }
            if (!Schema::hasColumn('whatsapp_logs', 'confidence_score')) {
                $table->integer('confidence_score')->nullable()->after('intent');
            }
            if (!Schema::hasColumn('whatsapp_logs', 'sender_type')) {
                $table->enum('sender_type', ['customer', 'bot', 'agent', 'system'])->default('customer')->after('confidence_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropColumn(['is_group', 'group_id', 'last_intent', 'confidence_score', 'sender_type']);
        });

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn(['is_group', 'group_id', 'intent', 'confidence_score', 'sender_type']);
        });
    }
};
