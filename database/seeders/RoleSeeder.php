<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'label' => 'Administrator'],
            ['name' => 'noc', 'label' => 'Network Operations Center'],
            ['name' => 'network-operations-center', 'label' => 'Network Operations Center'], // Legacy/Existing role support
            ['name' => 'technician', 'label' => 'Technician'],
            ['name' => 'coordinator', 'label' => 'Coordinator'],
            ['name' => 'customer', 'label' => 'Customer'],
            ['name' => 'finance', 'label' => 'Finance Staff'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assign Permissions
            if ($role->name === 'admin') {
                // Admin gets all permissions
                $role->permissions()->sync(Permission::all());
            } elseif (in_array($role->name, ['noc', 'network-operations-center'])) {
                // NOC permissions
                $permissions = Permission::whereIn('group', [
                    'Dashboard',
                    'Customer Management',
                    'Ticket Management',
                    'Router Management',
                    'OLT Management',
                    'ODC Management',
                    'ODP Management',
                    'HTB Management',
                    'PPPoE Management',
                    'Radius',
                    'Map',
                    'Network Monitor',
                    'Profile',
                    'Notification'
                ])->get();
                $role->permissions()->sync($permissions);
            } elseif ($role->name === 'technician') {
                // Technician permissions
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'ticket.view',
                    'ticket.edit', // Can update status/notes
                    'installation.view',
                    'installation.edit', // Can update status/photos
                    'attendance.view',
                    'attendance.create',
                    'attendance.report',
                    'map.view',
                    'odp.view',
                    'odp.edit',
                    'odc.edit',
                    'leave.view',
                    'leave.create',
                    'schedule.view',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                ])->get();
                $role->permissions()->sync($permissions);
            } elseif ($role->name === 'coordinator') {
                // Coordinator permissions
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'inventory.view',
                    'inventory.pickup',
                    'map.view',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                ])->get();
                $role->permissions()->sync($permissions);
            } elseif ($role->name === 'finance') {
                // Finance Staff permissions
                // Get all permissions for groups: Finance, Investor Management, Package Management, Profile, Notification
                $groupPermissions = Permission::whereIn('group', [
                    'Finance',
                    'Investor Management',
                    'Package Management',
                    'Profile',
                    'Notification'
                ])->get();

                $specificPermissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'attendance.view', // Optional: if they need to see attendance for payroll
                    'attendance.report',
                    'inventory.view',
                    'inventory.manage',
                    'inventory.pickup',
                    'customer.view',
                    'coordinator.view',
                    'region.view',
                ])->get();

                $role->permissions()->sync($groupPermissions->merge($specificPermissions));
            }
        }
    }
}
