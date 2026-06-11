<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\DefaultRolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NormalizeRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed some permissions
        Permission::updateOrCreate(['name' => 'dashboard.view'], ['label' => 'View Dashboard', 'group' => 'Dashboard']);
        Permission::updateOrCreate(['name' => 'user.view'], ['label' => 'View Users', 'group' => 'User Management']);
        Permission::updateOrCreate(['name' => 'role.view'], ['label' => 'View Roles', 'group' => 'Role Management']);
    }

    public function test_it_merges_legacy_roles()
    {
        // Setup legacy role
        $adminRole = Role::create(['name' => Role::ADMIN, 'label' => 'Administrator']);
        $legacyRole = Role::create(['name' => 'administrator', 'label' => 'Legacy Admin']);
        
        $user = User::factory()->create(['role_id' => $legacyRole->id]);

        $this->assertEquals($legacyRole->id, $user->fresh()->role_id);

        Artisan::call('roles:normalize');

        $this->assertEquals($adminRole->id, $user->fresh()->role_id);
        $this->assertDatabaseMissing('roles', ['name' => 'administrator']);
    }

    public function test_it_renames_legacy_roles()
    {
        $legacyRole = Role::create(['name' => 'koordinator', 'label' => 'Legacy Coordinator']);
        
        Artisan::call('roles:normalize');

        $this->assertDatabaseHas('roles', ['name' => Role::COORDINATOR]);
        $this->assertDatabaseMissing('roles', ['name' => 'koordinator']);
    }

    public function test_it_syncs_permissions_exactly()
    {
        $role = Role::create(['name' => Role::LEADER, 'label' => 'Leader']);
        
        // Give it extra permission not in template
        $extraPermission = Permission::create(['name' => 'extra.permission', 'label' => 'Extra', 'group' => 'Test']);
        $role->permissions()->attach($extraPermission);
        
        // Ensure it has the extra permission
        $this->assertTrue($role->permissions->contains('name', 'extra.permission'));

        Artisan::call('roles:normalize');

        // Refresh role and permissions
        $role = $role->fresh();
        
        // Should NOT have the extra permission anymore
        $this->assertFalse($role->permissions->contains('name', 'extra.permission'));
        
        // Should have permissions from template (that exist in DB)
        $templatePermissions = DefaultRolePermissions::permissionNames(Role::LEADER);
        foreach ($templatePermissions as $permName) {
            if (Permission::where('name', $permName)->exists()) {
                $this->assertTrue($role->permissions->contains('name', $permName));
            }
        }
    }

    public function test_it_clears_cache()
    {
        $role = Role::create(['name' => Role::ADMIN, 'label' => 'Admin']);
        $cacheKey = "sidebar.permission_map.role.{$role->id}";
        
        Cache::put($cacheKey, ['some' => 'data']);
        $this->assertTrue(Cache::has($cacheKey));

        Artisan::call('roles:normalize');

        $this->assertFalse(Cache::has($cacheKey));
    }
}
