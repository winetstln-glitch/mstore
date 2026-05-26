<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class GiveAllRolesDashboardViewPermission extends Command
{
    protected $signature = 'roles:give-dashboard-view';
    protected $description = 'Memberikan izin dashboard.view ke semua role yang belum punya';

    public function handle()
    {
        $this->info('Memulai memberikan izin dashboard.view ke semua role...');

        $dashboardPermission = Permission::firstOrCreate(
            ['name' => 'dashboard.view'],
            ['label' => 'Lihat Dashboard', 'group' => 'Dashboard']
        );

        $roles = Role::all();
        $countUpdated = 0;

        foreach ($roles as $role) {
            if (! $role->permissions()->where('name', 'dashboard.view')->exists()) {
                $role->permissions()->attach($dashboardPermission);
                $this->info("✓ Memberikan izin dashboard.view ke role: {$role->name}");
                $countUpdated++;
            }
        }

        $this->info("Selesai! Total {$countUpdated} role telah diperbarui!");
    }
}