<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NormalizeRolesCommand extends Command
{
    protected $signature = 'roles:normalize {--dry-run : Only show what would be changed}';

    protected $description = 'Normalize roles and sync permissions to default templates';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No changes will be made.');
        }

        $this->info('Step 1: Merging/Renaming legacy role names...');

        $roleMapping = [
            'administrator' => Role::ADMIN,
            'director' => Role::DIREKTUR,
            'network operations center' => Role::NOC,
            'coordinator' => Role::COORDINATOR,
            'koordinator' => Role::COORDINATOR,
            'finance' => Role::FINANCE,
            'staf-keuangan' => Role::FINANCE,
            'hrd manager' => Role::HRD_MANAGER,
            'manager-hrd' => Role::HRD_MANAGER,
            'operator wash' => Role::KARYAWAN_WASH,
        ];

        foreach ($roleMapping as $oldName => $newName) {
            if ($oldName === $newName) {
                continue;
            }

            $oldRole = Role::where('name', $oldName)->first();
            $newRole = Role::where('name', $newName)->first();

            if ($oldRole && $newRole) {
                $this->info("Merging \"$oldName\" into \"$newName\"");
                if (!$dryRun) {
                    DB::table('users')->where('role_id', $oldRole->id)->update(['role_id' => $newRole->id]);
                    $oldRole->permissions()->detach();
                    $oldRole->delete();
                    Cache::forget("sidebar.permission_map.role.{$newRole->id}");
                }
            } elseif ($oldRole) {
                $this->info("Renaming \"$oldName\" to \"$newName\"");
                if (!$dryRun) {
                    $oldRole->update(['name' => $newName]);
                    Cache::forget("sidebar.permission_map.role.{$oldRole->id}");
                }
            }
        }

        $this->info("\nStep 2: Syncing permissions to default templates...");

        $definitions = DefaultRolePermissions::definitions();
        $allPermissionIds = Permission::pluck('id')->toArray();

        foreach ($definitions as $roleName => $definition) {
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                $this->warn("Role \"$roleName\" not found in database, creating...");
                if (!$dryRun) {
                    $role = Role::create([
                        'name' => $roleName,
                        'label' => $definition['label'] ?? ucfirst($roleName),
                    ]);
                } else {
                    continue;
                }
            }

            $this->info("Processing permissions for role: {$role->name}");

            $targetPermissionIds = [];
            if (isset($definition['grants_all']) && $definition['grants_all']) {
                $targetPermissionIds = $allPermissionIds;
            } else {
                $permissionNames = DefaultRolePermissions::permissionNames($roleName);
                $targetPermissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
            }

            if (!$dryRun) {
                $role->permissions()->sync($targetPermissionIds);
                
                // Also update label if it changed
                if (isset($definition['label']) && $role->label !== $definition['label']) {
                    $role->update(['label' => $definition['label']]);
                }

                Cache::forget("sidebar.permission_map.role.{$role->id}");
                $this->info("  - Synced " . count($targetPermissionIds) . " permissions.");
            } else {
                $currentCount = $role->permissions()->count();
                $this->info("  - Would sync " . count($targetPermissionIds) . " permissions (currently has $currentCount).");
            }
        }

        $this->info("\nStep 3: Cleaning up orphaned permissions...");
        if (!$dryRun) {
            // Optional: remove permissions that are not assigned to any role anymore if they are obsolete
            // But usually we want to keep permissions in the database.
            // Clear general sidebar cache if exists
            Cache::forget('sidebar_menu_tree');
        }

        $this->info("\nRoles normalization and permission sync completed!");
        return 0;
    }
}
