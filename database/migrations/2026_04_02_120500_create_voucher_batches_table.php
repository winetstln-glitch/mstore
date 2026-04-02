<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voucher_batches')) {
            Schema::create('voucher_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_code')->unique();
                $table->string('profile')->nullable();
                $table->unsignedBigInteger('duration_seconds')->nullable();
                $table->unsignedBigInteger('quota_mb')->nullable();
                $table->unsignedInteger('total_vouchers')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_batches');
    }
};
