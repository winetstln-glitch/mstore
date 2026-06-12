<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wash_member_packages')) {
            return;
        }

        $exists = DB::table('wash_member_packages')->count();
        if ($exists > 0) {
            return;
        }

        $now = now();
        DB::table('wash_member_packages')->insert([
            [
                'name' => 'Paket Bronze',
                'code' => 'bronze',
                'description' => 'Paket dasar untuk anggota baru',
                'price' => 50000,
                'duration_days' => 30,
                'discount_percent' => 5,
                'benefits' => json_encode(['Diskon 5%', 'Loyalty Program', 'Promo Member']),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Paket Silver',
                'code' => 'silver',
                'description' => 'Paket untuk anggota yang sering berkunjung',
                'price' => 100000,
                'duration_days' => 30,
                'discount_percent' => 10,
                'benefits' => json_encode(['Diskon 10%', 'Loyalty Program', 'Promo Member', 'Prioritas Antrian']),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Paket Gold',
                'code' => 'gold',
                'description' => 'Paket premium dengan banyak manfaat',
                'price' => 200000,
                'duration_days' => 30,
                'discount_percent' => 15,
                'benefits' => json_encode(['Diskon 15%', 'Loyalty Program', 'Promo Member', 'Prioritas Antrian', 'Promo Eksklusif']),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // No rollback needed for seeding
    }
};
