<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class AddMissingPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'name' => 'leave.manage',
                'label' => 'Kelola Cuti/Izin',
                'group' => 'Leave Management',
            ],
            [
                'name' => 'chat.view',
                'label' => 'Lihat Chat WhatsApp',
                'group' => 'WhatsApp',
            ],
            [
                'name' => 'apikey.view',
                'label' => 'Lihat API Key',
                'group' => 'Integrasi',
            ],
            [
                'name' => 'calculator.view',
                'label' => 'Lihat Kalkulator PON',
                'group' => 'Utilities',
            ],
            [
                'name' => 'genieacs_server.view',
                'label' => 'Lihat Server GenieACS',
                'group' => 'Network Monitor',
            ],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
            $this->command->info("✅ Permission '{$perm['name']}' berhasil ditambahkan!");
        }

        $this->command->info("\n🎉 Semua permission missing berhasil ditambahkan!");
    }
}
