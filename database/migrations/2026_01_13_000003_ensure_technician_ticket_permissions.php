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
        // Ensure the permission exists
        $permission = Permission::firstOrCreate(
            ['name' => 'ticket.edit'],
            ['label' => 'Edit Ticket', 'group' => 'Ticket Management']
        );

        $role = Role::where('name', 'technician')->first();

        if ($role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't necessarily want to remove it on down as it might have been there before
    }
};
