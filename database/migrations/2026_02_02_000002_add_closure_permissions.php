<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'closure.view',
            'closure.create',
            'closure.edit',
            'closure.delete',
        ];

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $p = Permission::firstOrCreate(
                ['name' => $permission],
                [
                    'label' => ucwords(str_replace('.', ' ', $permission)),
                    'group' => 'closure',
                ]
            );
            $permissionIds[] = $p->id;
        }

        // Assign to admin role (just in case)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign view to coordinator if needed
        $coordinatorRole = Role::where('name', 'coordinator')->first();
        if ($coordinatorRole) {
            $viewPerm = Permission::where('name', 'closure.view')->first();
            if ($viewPerm) {
                $coordinatorRole->permissions()->syncWithoutDetaching([$viewPerm->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: remove permissions
    }
};
