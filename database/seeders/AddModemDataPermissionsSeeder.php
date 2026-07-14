<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class AddModemDataPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'modem-data.view', 'label' => 'View Modem Data', 'group' => 'Utilities'],
            ['name' => 'modem-data.create', 'label' => 'Create Modem Data', 'group' => 'Utilities'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
