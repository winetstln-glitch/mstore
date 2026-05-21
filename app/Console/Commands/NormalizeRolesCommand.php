<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeRolesCommand extends Command
{
    protected $signature = 'roles:normalize';

    protected $description = 'Normalize role names in the database to be consistent (remove duplicates)';

    public function handle()
    {
        $this->info('Normalizing roles...');

        $roleMapping = [
            'administrator' => 'admin',
            'director' => 'direktur',
            'network operations center' => 'noc',
            'coordinator' => 'koordinator',
            'finance' => 'staf-keuangan',
            'hrd manager' => 'manager-hrd',
            'operator wash' => 'karyawan-wash',
        ];

        foreach ($roleMapping as $oldName => $newName) {
            $this->info("Processing: $oldName → $newName");

            $oldRole = DB::table('roles')->where('name', $oldName)->first();
            $newRole = DB::table('roles')->where('name', $newName)->first();

            if ($oldRole && $newRole) {
                DB::table('role_user')->where('role_id', $oldRole->id)->update(['role_id' => $newRole->id]);
                DB::table('permission_role')->where('role_id', $oldRole->id)->delete();
                DB::table('roles')->where('id', $oldRole->id)->delete();
                $this->info("  - Merged $oldName into $newName");
            } elseif ($oldRole) {
                DB::table('roles')->where('name', $oldName)->update(['name' => $newName]);
                $this->info("  - Renamed $oldName to $newName");
            }
        }

        $this->info('Roles normalized successfully!');
        return 0;
    }
}
