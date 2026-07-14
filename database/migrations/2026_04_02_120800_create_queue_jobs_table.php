<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queue_jobs')) {
            Schema::create('queue_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->json('payload')->nullable();
                $table->string('status')->default('queued');
                $table->unsignedInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_jobs');
    }
};
