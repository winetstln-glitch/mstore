<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_reward_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_reward_vouchers', 'source')) {
                $table->string('source', 32)->default('auto')->index()->after('reward_type');
            }
            if (! Schema::hasColumn('wash_reward_vouchers', 'source_reason')) {
                $table->string('source_reason', 255)->nullable()->after('source');
            }
            if (! Schema::hasColumn('wash_reward_vouchers', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->index()->after('used_wash_transaction_id');
            }
            if (! Schema::hasColumn('wash_reward_vouchers', 'revoked_reason')) {
                $table->string('revoked_reason', 255)->nullable()->after('revoked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_reward_vouchers', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('wash_reward_vouchers', 'source')) $cols[] = 'source';
            if (Schema::hasColumn('wash_reward_vouchers', 'source_reason')) $cols[] = 'source_reason';
            if (Schema::hasColumn('wash_reward_vouchers', 'revoked_at')) $cols[] = 'revoked_at';
            if (Schema::hasColumn('wash_reward_vouchers', 'revoked_reason')) $cols[] = 'revoked_reason';
            if (count($cols) > 0) {
                $table->dropColumn($cols);
            }
        });
    }
};
