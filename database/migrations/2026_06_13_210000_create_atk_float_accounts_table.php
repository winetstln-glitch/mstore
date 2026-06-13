<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_float_accounts')) {
            Schema::create('atk_float_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('account_type', ['bank', 'e-wallet', 'ppob_deposit']);
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_float_accounts');
    }
};
