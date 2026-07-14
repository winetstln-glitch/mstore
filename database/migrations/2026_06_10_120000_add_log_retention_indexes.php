<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            try {
                $table->index('created_at', 'whatsapp_logs_created_at_idx');
            } catch (\Throwable) {
            }
            try {
                $table->index(['type', 'status', 'created_at'], 'whatsapp_logs_type_status_created_at_idx');
            } catch (\Throwable) {
            }
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            try {
                $table->index('created_at', 'notification_logs_created_at_idx');
            } catch (\Throwable) {
            }
            try {
                $table->index(['type', 'status', 'created_at'], 'notification_logs_type_status_created_at_idx');
            } catch (\Throwable) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            try {
                $table->dropIndex('whatsapp_logs_created_at_idx');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('whatsapp_logs_type_status_created_at_idx');
            } catch (\Throwable) {
            }
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            try {
                $table->dropIndex('notification_logs_created_at_idx');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('notification_logs_type_status_created_at_idx');
            } catch (\Throwable) {
            }
        });
    }
};

