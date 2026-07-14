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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('attachment_disk', 50)->nullable()->after('body');
            $table->string('attachment_path')->nullable()->after('attachment_disk');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');

            $table->index(['thread_id', 'attachment_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['thread_id', 'attachment_path']);
            $table->dropColumn([
                'attachment_disk',
                'attachment_path',
                'attachment_name',
                'attachment_mime',
                'attachment_size',
            ]);
        });
    }
};
