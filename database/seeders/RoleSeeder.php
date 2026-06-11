<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissionIds = Permission::query()->pluck('id')->all();
        $permissionIdByName = Permission::query()->pluck('id', 'name');

        foreach (DefaultRolePermissions::definitions() as $roleName => $definition) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['label' => $definition['label']]
            );

            if (DefaultRolePermissions::grantsAll($roleName)) {
                if (! empty($allPermissionIds)) {
                    $role->permissions()->syncWithoutDetaching($allPermissionIds);
                }
                continue;
            }

            $permissionIds = $permissionIdByName
                ->only(DefaultRolePermissions::permissionNames($roleName))
                ->values()
                ->all();

            if (! empty($permissionIds)) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }
}
