<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeRolesCommand extends Command
{
    protected $signature = 'roles:normalize {--dry-run : Only show what would be changed}';

    protected $description = 'Normalize roles: migrate 16 legacy → 9 new roles, sync permissions, cleanup.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No database changes will be made.');
        }

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║  ROLE CONSOLIDATION: 16 Legacy Roles → 11 Fixed Roles         ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        $this->step0MigrateLegacyRoles($dryRun);
        $this->step1SyncIsSystemFlag($dryRun);
        $this->step2SyncPermissionsToTemplates($dryRun);
        $this->step3CleanupOrphaned($dryRun);

        $this->newLine();
        $this->info('✅ Role normalization & permission sync completed!');
        $this->line('Run with --dry-run first next time to preview changes.');

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 0 — MIGRASI: 16 ROLE LAMA → 11 ROLE BARU + SET SCOPE_CONFIG + JOB_TITLE
    |--------------------------------------------------------------------------
    */
    protected function step0MigrateLegacyRoles(bool $dryRun): void
    {
        $this->newLine();
        $this->info('🟢 STEP 0: Migrasi 16 legacy roles → 11 roles baru + scope_config');

        $migrationMap = config('roles.migration_map', []);
        if (! is_array($migrationMap) || $migrationMap === []) {
            $this->warn('  ⚠ config(roles.migration_map) is empty. Skipping legacy migration.');
            return;
        }

        $hasJobTitle = Schema::hasColumn('users', 'job_title');
        $hasScopeConfig = Schema::hasColumn('users', 'scope_config');

        $totalMigratedUsers = 0;
        $deletedRoles = [];

        foreach ($migrationMap as $oldName => $cfg) {
            $newRoleName = $cfg['new_role'] ?? null;
            $scopeConfig = $cfg['scope'] ?? null;
            $jobTitle = $cfg['job_title'] ?? null;
            if (! is_string($newRoleName)) {
                continue;
            }

            $oldRole = Role::where('name', $oldName)->first();
            if (! $oldRole) {
                continue;
            }

            $definition = config("roles.definitions.{$newRoleName}", []);
            $newLabel = is_array($definition) && isset($definition['label'])
                ? (string) $definition['label']
                : ucfirst(str_replace(['-', '_'], ' ', $newRoleName));

            $newRole = Role::firstOrCreate(
                ['name' => $newRoleName],
                [
                    'label' => $newLabel,
                    'is_system' => true,
                ]
            );

            $sameRole = (int) $oldRole->id === (int) $newRole->id;

            $userCount = $sameRole ? 0 : (int) DB::table('users')->where('role_id', $oldRole->id)->count();
            $totalMigratedUsers += $userCount;

            $this->line(sprintf(
                '  • %-30s → %-25s   %s user(s)   scope=%s',
                "\"{$oldName}\"",
                "\"{$newRoleName}\"",
                $userCount,
                json_encode($scopeConfig, JSON_UNESCAPED_SLASHES),
            ));

            if ($dryRun) {
                continue;
            }

            if (! $sameRole && $userCount > 0) {
                $updateData = ['role_id' => $newRole->id];
                if ($hasJobTitle && is_string($jobTitle) && $jobTitle !== '') {
                    $updateData['job_title'] = $jobTitle;
                }
                if ($hasScopeConfig && is_array($scopeConfig) && $scopeConfig !== []) {
                    $updateData['scope_config'] = json_encode($scopeConfig, JSON_UNESCAPED_UNICODE);
                }

                DB::table('users')->where('role_id', $oldRole->id)->update($updateData);
            }

            if (! $sameRole) {
                try {
                    $oldRole->permissions()->detach();
                    $oldRole->delete();
                    $deletedRoles[] = $oldName;
                } catch (\Throwable) {
                    // constraint violation? skip delete, user masih ada
                }
            }

            Cache::forget("sidebar.permission_map.role.{$newRole->id}");
        }

        $this->line(sprintf('  ——— Total user dipindah : %d user', $totalMigratedUsers));
        if ($deletedRoles !== []) {
            $this->line(sprintf('  ——— Role lama dihapus   : %s', implode(', ', $deletedRoles)));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 1 — SYNC kolom is_system di roles table + pastikan 11 role ada
    |--------------------------------------------------------------------------
    */
    protected function step1SyncIsSystemFlag(bool $dryRun): void
    {
        $this->newLine();
        $this->info('🟡 STEP 1: Sync kolom is_system di roles & pastikan semua system role ada');

        $definitions = (array) config('roles.definitions', []);
        foreach ($definitions as $roleName => $def) {
            if (! is_array($def)) {
                continue;
            }
            $label = (string) ($def['label'] ?? ucfirst(str_replace(['-', '_'], ' ', $roleName)));
            $isSystem = (bool) ($def['is_system'] ?? true);

            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                $this->line("  • Create missing system role: \"{$roleName}\" ({$label})");
                if (! $dryRun) {
                    $role = Role::create(['name' => $roleName, 'label' => $label, 'is_system' => $isSystem]);
                }
                continue;
            }

            $changed = false;
            if ($role->label !== $label) {
                $this->line("  • Update label: \"{$roleName}\" → {$label}");
                $changed = true;
            }

            if ((bool) $role->is_system !== $isSystem) {
                $this->line("  • Update is_system: \"{$roleName}\" → ".($isSystem ? 'true' : 'false'));
                $changed = true;
            }

            if ($changed && ! $dryRun) {
                $role->update(['label' => $label, 'is_system' => $isSystem]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2 — SYNC PERMISSION SETIAP ROLE ke DefaultRolePermissions template
    |--------------------------------------------------------------------------
    */
    protected function step2SyncPermissionsToTemplates(bool $dryRun): void
    {
        $this->newLine();
        $this->info('🟠 STEP 2: Sync permissions → DefaultRolePermissions template + wildcard expand');

        $definitions = DefaultRolePermissions::definitions();
        $allPermissions = Permission::all();
        $allPermissionIds = $allPermissions->pluck('id')->toArray();

        foreach ($definitions as $roleName => $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                $this->warn("  ⚠ Role \"{$roleName}\" tidak ada di DB (dibuat otomatis di step 1). Skip.");
                continue;
            }

            $rawPermNames = DefaultRolePermissions::permissionNames($roleName);
            $expandedNames = DefaultRolePermissions::expandPermissionNames($rawPermNames, $allPermissions);

            $grantsAll = isset($definition['grants_all']) && $definition['grants_all'] === true;
            $targetIds = $grantsAll
                ? $allPermissionIds
                : $allPermissions->whereIn('name', $expandedNames)->pluck('id')->toArray();

            $currentCount = $role->permissions()->count();
            $diff = count($targetIds) - $currentCount;
            $symbol = $diff === 0 ? '=' : ($diff > 0 ? "+{$diff}" : (string) $diff);

            $this->line(sprintf(
                '  • %-25s   %s %3d permission(s)  (sekarang %d, Δ %s)',
                "\"{$roleName}\"",
                $grantsAll ? '[ALL]' : '     ',
                count($targetIds),
                $currentCount,
                $symbol,
            ));

            if ($dryRun) {
                continue;
            }

            if ($targetIds !== []) {
                $role->permissions()->sync($targetIds);
            } else {
                $role->permissions()->detach();
            }

            $label = (string) ($definition['label'] ?? $role->label);
            if ($role->label !== $label) {
                $role->update(['label' => $label]);
            }

            Cache::forget("sidebar.permission_map.role.{$role->id}");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3 — CLEANUP cache sidebar & role buatan user yg tdk dipakai
    |--------------------------------------------------------------------------
    */
    protected function step3CleanupOrphaned(bool $dryRun): void
    {
        $this->newLine();
        $this->info('🔵 STEP 3: Cleanup cache & audit non-system roles');

        if (! $dryRun) {
            Cache::forget('sidebar.menu.tree.v11');
            Cache::forget('sidebar_menu_tree');
            Cache::forget('sidebar.menu.permission_map');

            foreach (Role::pluck('id')->toArray() as $rid) {
                Cache::forget("sidebar.permission_map.role.{$rid}");
            }
        }

        $systemRoleNames = Role::allSystemRoleNames();
        $customRoles = Role::whereNotIn('name', $systemRoleNames)
            ->withCount('users')
            ->orderBy('users_count', 'desc')
            ->get();

        if ($customRoles->count() === 0) {
            $this->line('  • Tidak ada custom role diluar daftar system roles. Bersih ✨');
            return;
        }

        $this->newLine();
        $this->warn('  ⚠ Ditemukan custom role di luar SSOT (pastikan memang dibutuhkan):');
        foreach ($customRoles as $r) {
            $this->warn(sprintf(
                '    - ID=%-3d  name=%-30s  users=%-3d  is_system=%s',
                $r->id,
                $r->name,
                $r->users_count,
                var_export((bool) $r->is_system, true),
            ));
        }
    }
}
