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
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->string('duitku_reference')->nullable()->after('payment_reference');
            $table->boolean('use_pop')->default(true)->after('qr_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropColumn(['duitku_reference', 'use_pop']);
        });
    }
};
