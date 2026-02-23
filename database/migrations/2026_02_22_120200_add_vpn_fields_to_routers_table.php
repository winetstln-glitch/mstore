<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('vpn_tunnel_ip')->nullable()->after('host');
            $table->foreignId('vpn_account_id')->nullable()->after('vpn_tunnel_ip')->constrained('vpn_accounts')->nullOnDelete();
            $table->enum('vpn_status', ['unknown', 'connected', 'disconnected'])->default('unknown')->after('vpn_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vpn_account_id');
            $table->dropColumn(['vpn_tunnel_ip', 'vpn_status']);
        });
    }
};
