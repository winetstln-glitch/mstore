<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class ClosurePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'closure.view', 'label' => 'View Closures', 'group' => 'Closure Management'],
            ['name' => 'closure.create', 'label' => 'Create Closures', 'group' => 'Closure Management'],
            ['name' => 'closure.edit', 'label' => 'Edit Closures', 'group' => 'Closure Management'],
            ['name' => 'closure.delete', 'label' => 'Delete Closures', 'group' => 'Closure Management'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Assign to Admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $permissionIds = Permission::whereIn('name', array_column($permissions, 'name'))->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
