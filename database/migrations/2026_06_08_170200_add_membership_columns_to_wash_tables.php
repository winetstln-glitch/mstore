<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_transactions', 'wash_member_id')) {
                $table->foreignId('wash_member_id')->nullable()->after('wash_customer_id')->constrained('wash_members')->nullOnDelete();
            }
            if (! Schema::hasColumn('wash_transactions', 'member_discount_amount')) {
                $table->decimal('member_discount_amount', 15, 2)->default(0)->after('discount_amount');
            }
        });

        Schema::table('wash_loyalty_counters', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_loyalty_counters', 'wash_member_id')) {
                $table->foreignId('wash_member_id')->nullable()->after('wash_customer_id')->constrained('wash_members')->nullOnDelete();
            }
        });

        Schema::table('wash_reward_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_reward_vouchers', 'wash_member_id')) {
                $table->foreignId('wash_member_id')->nullable()->after('wash_customer_id')->constrained('wash_members')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_reward_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('wash_reward_vouchers', 'wash_member_id')) {
                $table->dropConstrainedForeignId('wash_member_id');
            }
        });

        Schema::table('wash_loyalty_counters', function (Blueprint $table) {
            if (Schema::hasColumn('wash_loyalty_counters', 'wash_member_id')) {
                $table->dropConstrainedForeignId('wash_member_id');
            }
        });

        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'member_discount_amount')) {
                $table->dropColumn('member_discount_amount');
            }
            if (Schema::hasColumn('wash_transactions', 'wash_member_id')) {
                $table->dropConstrainedForeignId('wash_member_id');
            }
        });
    }
};

