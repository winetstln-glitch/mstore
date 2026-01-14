<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = Role::where('name', 'technician')->first();
        $permission = Permission::where('name', 'customer.view')->first();

        if ($role && $permission) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'technician')->first();
        $permission = Permission::where('name', 'customer.view')->first();

        if ($role && $permission) {
            $role->permissions()->detach($permission->id);
        }
    }
};
