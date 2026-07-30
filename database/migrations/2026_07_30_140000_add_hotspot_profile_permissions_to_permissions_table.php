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
        $groupId = DB::table('groups')->where('name', 'Service Management')->value('id');
        if (!$groupId) {
            $groupId = DB::table('groups')->where('name', 'Hotspot Management')->value('id');
        }

        $now = now();

        $permissions = [
            [
                'name' => 'hotspot.profile.view',
                'label' => 'View Hotspot Profiles (Paket Internet)',
                'group' => 'Service Management',
                'group_id' => $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'hotspot.profile.manage',
                'label' => 'Manage Hotspot Profiles (Paket Internet)',
                'group' => 'Service Management',
                'group_id' => $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $perm) {
            $this->insertPermission($perm);
        }

        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            foreach ($permissions as $perm) {
                $permId = DB::table('permissions')->where('name', $perm['name'])->value('id');
                if ($permId) {
                    $exists = DB::table('permission_role')
                        ->where('permission_id', $permId)
                        ->where('role_id', $adminRole->id)
                        ->exists();
                    if (!$exists) {
                        DB::table('permission_role')->insert([
                            'permission_id' => $permId,
                            'role_id' => $adminRole->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Tidak menghapus permission untuk menghindari data loss
    }
};
