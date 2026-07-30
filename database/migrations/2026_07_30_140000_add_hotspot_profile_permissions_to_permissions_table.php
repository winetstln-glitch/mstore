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
        $now = now();

        $permissions = [
            [
                'name' => 'hotspot.profile.view',
                'label' => 'View Hotspot Profiles (Paket Internet)',
                'group' => 'Service Management',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'hotspot.profile.manage',
                'label' => 'Manage Hotspot Profiles (Paket Internet)',
                'group' => 'Service Management',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $perm) {
            $this->insertPermission($perm);
        }

        try {
            $adminRole = DB::table('roles')->where('name', 'admin')->first();
            if (!$adminRole) {
                $adminRole = DB::table('roles')->where('name', 'like', '%admin%')->first();
            }
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
        } catch (\Exception $e) {
            // Skip role assignment jika tabel roles / permission_role belum siap
            // Permission sudah ter-insert, user bisa assign manual via UI Role & Permission
        }
    }

    public function down(): void
    {
        // Tidak menghapus permission untuk menghindari data loss
    }
};
