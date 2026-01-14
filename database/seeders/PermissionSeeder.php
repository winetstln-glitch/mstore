<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'Dashboard'],

            // User Management
            ['name' => 'user.view', 'label' => 'View Users', 'group' => 'User Management'],
            ['name' => 'user.create', 'label' => 'Create User', 'group' => 'User Management'],
            ['name' => 'user.edit', 'label' => 'Edit User', 'group' => 'User Management'],
            ['name' => 'user.delete', 'label' => 'Delete User', 'group' => 'User Management'],

            // Role Management
            ['name' => 'role.view', 'label' => 'View Roles', 'group' => 'Role Management'],
            ['name' => 'role.create', 'label' => 'Create Role', 'group' => 'Role Management'],
            ['name' => 'role.edit', 'label' => 'Edit Role', 'group' => 'Role Management'],
            ['name' => 'role.delete', 'label' => 'Delete Role', 'group' => 'Role Management'],

            // Customer Management
            ['name' => 'customer.view', 'label' => 'View Customers', 'group' => 'Customer Management'],
            ['name' => 'customer.create', 'label' => 'Create Customer', 'group' => 'Customer Management'],
            ['name' => 'customer.edit', 'label' => 'Edit Customer', 'group' => 'Customer Management'],
            ['name' => 'customer.delete', 'label' => 'Delete Customer', 'group' => 'Customer Management'],

            // Ticket Management
            ['name' => 'ticket.view', 'label' => 'View Tickets', 'group' => 'Ticket Management'],
            ['name' => 'ticket.create', 'label' => 'Create Ticket', 'group' => 'Ticket Management'],
            ['name' => 'ticket.edit', 'label' => 'Edit Ticket', 'group' => 'Ticket Management'],
            ['name' => 'ticket.delete', 'label' => 'Delete Ticket', 'group' => 'Ticket Management'],

            // Installation Management
            ['name' => 'installation.view', 'label' => 'View Installations', 'group' => 'Installation Management'],
            ['name' => 'installation.create', 'label' => 'Create Installation', 'group' => 'Installation Management'],
            ['name' => 'installation.edit', 'label' => 'Edit Installation', 'group' => 'Installation Management'],
            ['name' => 'installation.delete', 'label' => 'Delete Installation', 'group' => 'Installation Management'],

            // Technician Management
            ['name' => 'technician.view', 'label' => 'View Technicians', 'group' => 'Technician Management'],
            ['name' => 'technician.create', 'label' => 'Create Technician', 'group' => 'Technician Management'],
            ['name' => 'technician.edit', 'label' => 'Edit Technician', 'group' => 'Technician Management'],
            ['name' => 'technician.delete', 'label' => 'Delete Technician', 'group' => 'Technician Management'],

            // Attendance
            ['name' => 'attendance.view', 'label' => 'View Attendance', 'group' => 'Attendance'],
            ['name' => 'attendance.create', 'label' => 'Clock In/Out', 'group' => 'Attendance'],
            ['name' => 'attendance.report', 'label' => 'View Attendance Report', 'group' => 'Attendance'],

            // ODC Management
            ['name' => 'odc.view', 'label' => 'View ODCs', 'group' => 'ODC Management'],
            ['name' => 'odc.create', 'label' => 'Create ODC', 'group' => 'ODC Management'],
            ['name' => 'odc.edit', 'label' => 'Edit ODC', 'group' => 'ODC Management'],
            ['name' => 'odc.delete', 'label' => 'Delete ODC', 'group' => 'ODC Management'],

            // ODP Management
            ['name' => 'odp.view', 'label' => 'View ODPs', 'group' => 'ODP Management'],
            ['name' => 'odp.create', 'label' => 'Create ODP', 'group' => 'ODP Management'],
            ['name' => 'odp.edit', 'label' => 'Edit ODP', 'group' => 'ODP Management'],
            ['name' => 'odp.delete', 'label' => 'Delete ODP', 'group' => 'ODP Management'],

            // OLT Management
            ['name' => 'olt.view', 'label' => 'View OLTs', 'group' => 'OLT Management'],
            ['name' => 'olt.create', 'label' => 'Create OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.edit', 'label' => 'Edit OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.delete', 'label' => 'Delete OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.test_connection', 'label' => 'Test Connection', 'group' => 'OLT Management'],

            // Finance
            ['name' => 'finance.view', 'label' => 'View Finance', 'group' => 'Finance'],
            ['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance'],

            // Map
            ['name' => 'map.view', 'label' => 'View Map', 'group' => 'Map'],

            // Settings
            ['name' => 'setting.view', 'label' => 'View Settings', 'group' => 'Settings'],
            ['name' => 'setting.update', 'label' => 'Update Settings', 'group' => 'Settings'],

            // WhatsApp
            ['name' => 'chat.view', 'label' => 'View WhatsApp', 'group' => 'WhatsApp'],
            ['name' => 'chat.manage', 'label' => 'Manage WhatsApp', 'group' => 'WhatsApp'],

            // GenieACS
            ['name' => 'genieacs.view', 'label' => 'View GenieACS', 'group' => 'Network Monitor'],
            ['name' => 'genieacs.manage', 'label' => 'Manage GenieACS', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.view', 'label' => 'View GenieACS Servers', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.create', 'label' => 'Create GenieACS Server', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.edit', 'label' => 'Edit GenieACS Server', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.delete', 'label' => 'Delete GenieACS Server', 'group' => 'Network Monitor'],

            // Inventory
            ['name' => 'inventory.view', 'label' => 'View Inventory', 'group' => 'Inventory'],
            ['name' => 'inventory.manage', 'label' => 'Manage Inventory', 'group' => 'Inventory'],
            ['name' => 'inventory.pickup', 'label' => 'Pickup Inventory', 'group' => 'Inventory'],
        ];

        $permissionNames = collect($permissions)->pluck('name')->toArray();

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Delete permissions that are not in the list
        Permission::whereNotIn('name', $permissionNames)->delete();
    }
}
