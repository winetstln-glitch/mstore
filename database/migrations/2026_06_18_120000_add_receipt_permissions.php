<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function insertPermission(array $data): void
    {
        $exists = DB::table('permissions')->where('name', $data['name'])->exists();
        if (!$exists) {
            DB::table('permissions')->insert($data);
        }
    }

    public function up(): void
    {
        $permissions = [
            // Receipt Engine
            ['name' => 'receipt.view', 'label' => 'Lihat Receipt', 'group' => 'ATK Store'],
            ['name' => 'receipt.manage', 'label' => 'Kelola Receipt', 'group' => 'ATK Store'],
            ['name' => 'receipt.template.view', 'label' => 'Lihat Template Receipt', 'group' => 'ATK Store'],
            ['name' => 'receipt.template.manage', 'label' => 'Kelola Template Receipt', 'group' => 'ATK Store'],

            // Fee Engine (if not already added)
            ['name' => 'fee.view', 'label' => 'Lihat Fee', 'group' => 'ATK Store'],
            ['name' => 'fee.manage', 'label' => 'Kelola Fee', 'group' => 'ATK Store'],
        ];

        foreach ($permissions as $perm) {
            $this->insertPermission($perm);
        }
    }

    public function down(): void
    {
        // Tidak menghapus permission untuk menghindari data loss
    }
};
