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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('pppoe_user')->nullable()->unique()->after('status');
            $table->string('pppoe_password')->nullable()->after('pppoe_user');
            $table->string('onu_serial')->nullable()->after('pppoe_password');
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete()->after('onu_serial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['router_id']);
            $table->dropColumn(['pppoe_user', 'pppoe_password', 'onu_serial', 'router_id']);
        });
    }
};
