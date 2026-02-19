<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            return;
        }
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->unsignedBigInteger('amount'); // in smallest currency unit (e.g., IDR)
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('midtrans_order_id')->nullable()->index();
            $table->string('snap_token')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
