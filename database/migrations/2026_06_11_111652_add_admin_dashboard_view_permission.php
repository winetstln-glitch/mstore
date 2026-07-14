<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions') || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $permissions = [
            ['name' => 'admin.dashboard.view', 'label' => 'View Admin/HRD Dashboard', 'group' => 'Dashboard'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')->where('name', $permission['name'])->first();
            if ($existing) {
                $permissionIds[$permission['name']] = (int) $existing->id;
                continue;
            }

            $id = (int) DB::table('permissions')->insertGetId([
                ...$permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $permissionIds[$permission['name']] = $id;
        }

        $roleNamesToPermissionNames = [
            'admin' => array_keys($permissionIds),
            'direktur' => array_keys($permissionIds),
            'hrd' => array_keys($permissionIds),
            'hrd-manager' => array_keys($permissionIds),
        ];

        $roles = DB::table('roles')->whereIn('name', array_keys($roleNamesToPermissionNames))->get(['id', 'name']);
        foreach ($roles as $role) {
            $permissionNames = $roleNamesToPermissionNames[$role->name] ?? [];
            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;
                if (! $permissionId) {
                    continue;
                }
                $exists = DB::table('permission_role')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions') || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $names = ['admin.dashboard.view'];

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id')->all();
        if (! empty($ids)) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
