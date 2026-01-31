<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atk_products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });

        Schema::table('wash_services', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });

        Schema::table('atk_transactions', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
        });

        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('atk_products', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('wash_services', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('atk_transactions', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
