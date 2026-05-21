
&lt;?php

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
        $this-&gt;info('Memulai memberikan izin dashboard.view ke semua role...');

        $dashboardPermission = Permission::firstOrCreate(
            ['name' =&gt; 'dashboard.view'],
            ['label' =&gt; 'Lihat Dashboard', 'group' =&gt; 'Dashboard']
        );

        $roles = Role::all();
        $countUpdated = 0;

        foreach ($roles as $role) {
            if (! $role-&gt;permissions()-&gt;where('name', 'dashboard.view')-&gt;exists()) {
                $role-&gt;permissions()-&gt;attach($dashboardPermission);
                $this-&gt;info("✓ Memberikan izin dashboard.view ke role: {$role-&gt;name}");
                $countUpdated++;
            }
        }

        $this-&gt;info("Selesai! Total {$countUpdated} role telah diperbarui!");
    }
}
