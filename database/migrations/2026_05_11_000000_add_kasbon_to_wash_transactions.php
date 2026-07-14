<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->string('kasbon_type')->nullable()->after('discount_amount');
            $table->unsignedBigInteger('kasbon_user_id')->nullable()->after('kasbon_type');
            $table->string('kasbon_name')->nullable()->after('kasbon_user_id');
            $table->boolean('kasbon_settled')->default(false)->after('kasbon_name');

            $table->foreign('kasbon_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropForeign(['kasbon_user_id']);
            $table->dropColumn(['kasbon_type', 'kasbon_user_id', 'kasbon_name', 'kasbon_settled']);
        });
    }
};