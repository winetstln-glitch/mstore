<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_no')->unique();
            $table->date('date');
            $table->string('description')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('period_id')->nullable()->index();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('posted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
