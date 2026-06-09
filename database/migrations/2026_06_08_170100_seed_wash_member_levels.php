<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wash_member_levels')) {
            return;
        }

        $exists = DB::table('wash_member_levels')->count();
        if ($exists > 0) {
            return;
        }

        $now = now();
        DB::table('wash_member_levels')->insert([
            [
                'code' => 'bronze',
                'name' => 'Bronze Member',
                'min_transactions' => 0,
                'max_transactions' => 9,
                'discount_percent' => 0,
                'priority_rank' => 10,
                'benefits' => json_encode(['Loyalty Program', 'Promo Member'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'silver',
                'name' => 'Silver Member',
                'min_transactions' => 10,
                'max_transactions' => 24,
                'discount_percent' => 3,
                'priority_rank' => 7,
                'benefits' => json_encode(['Loyalty Program', 'Promo Member'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'gold',
                'name' => 'Gold Member',
                'min_transactions' => 25,
                'max_transactions' => 49,
                'discount_percent' => 5,
                'priority_rank' => 4,
                'benefits' => json_encode(['Loyalty Program', 'Promo Member', 'Prioritas Booking'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'platinum',
                'name' => 'Platinum Member',
                'min_transactions' => 50,
                'max_transactions' => null,
                'discount_percent' => 10,
                'priority_rank' => 1,
                'benefits' => json_encode(['Loyalty Program', 'Prioritas Booking', 'Promo Eksklusif'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('wash_member_levels')) {
            return;
        }

        DB::table('wash_member_levels')->truncate();
    }
};

