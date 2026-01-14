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
        // 1. Create ticket.complete permission
        $completePermission = Permission::firstOrCreate(
            ['name' => 'ticket.complete'],
            ['label' => 'Complete Ticket', 'group' => 'Ticket Management']
        );

        // 2. Assign to Admin (always gets all, but explicit sync in seeder usually)
        // But for safety let's ensure Admin has it if using 'all' logic dynamically.
        // Actually the CheckPermission middleware for Admin returns true always.
        
        // 3. Assign to Technician
        $technician = Role::where('name', 'technician')->first();
        if ($technician) {
            $technician->permissions()->syncWithoutDetaching([$completePermission->id]);
            
            // 4. Remove ticket.edit from Technician
            $editPermission = Permission::where('name', 'ticket.edit')->first();
            if ($editPermission) {
                $technician->permissions()->detach($editPermission->id);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: Give back edit, take away complete
        $technician = Role::where('name', 'technician')->first();
        $completePermission = Permission::where('name', 'ticket.complete')->first();
        $editPermission = Permission::where('name', 'ticket.edit')->first();

        if ($technician) {
            if ($completePermission) {
                $technician->permissions()->detach($completePermission->id);
            }
            if ($editPermission) {
                $technician->permissions()->syncWithoutDetaching([$editPermission->id]);
            }
        }
    }
};
