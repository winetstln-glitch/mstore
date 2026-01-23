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
            ['name' => 'attendance.edit', 'label' => 'Edit Attendance', 'group' => 'Attendance'],
            ['name' => 'attendance.delete', 'label' => 'Delete Attendance', 'group' => 'Attendance'],
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

            // HTB Management
            ['name' => 'htb.view', 'label' => 'View HTBs', 'group' => 'HTB Management'],
            ['name' => 'htb.create', 'label' => 'Create HTB', 'group' => 'HTB Management'],
            ['name' => 'htb.edit', 'label' => 'Edit HTB', 'group' => 'HTB Management'],
            ['name' => 'htb.delete', 'label' => 'Delete HTB', 'group' => 'HTB Management'],

            // OLT Management
            ['name' => 'olt.view', 'label' => 'View OLTs', 'group' => 'OLT Management'],
            ['name' => 'olt.create', 'label' => 'Create OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.edit', 'label' => 'Edit OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.delete', 'label' => 'Delete OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.test_connection', 'label' => 'Test Connection', 'group' => 'OLT Management'],

            // Router Management
            ['name' => 'router.view', 'label' => 'View Routers', 'group' => 'Router Management'],
            ['name' => 'router.create', 'label' => 'Create Router', 'group' => 'Router Management'],
            ['name' => 'router.edit', 'label' => 'Edit Router', 'group' => 'Router Management'],
            ['name' => 'router.delete', 'label' => 'Delete Router', 'group' => 'Router Management'],

            // Finance
            ['name' => 'finance.view', 'label' => 'View Finance', 'group' => 'Finance'],
            ['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance'],

            // Map
            ['name' => 'map.view', 'label' => 'View Map', 'group' => 'Map'],
            ['name' => 'map.manage', 'label' => 'Manage Map', 'group' => 'Map'],

            // Leave Management
            ['name' => 'leave.view', 'label' => 'View Leave Requests', 'group' => 'Leave Management'],
            ['name' => 'leave.create', 'label' => 'Create Leave Request', 'group' => 'Leave Management'],
            ['name' => 'leave.manage', 'label' => 'Manage Leave Requests', 'group' => 'Leave Management'],

            // Schedule Management
            ['name' => 'schedule.view', 'label' => 'View Schedules', 'group' => 'Schedule Management'],
            ['name' => 'schedule.manage', 'label' => 'Manage Schedules', 'group' => 'Schedule Management'],

            // Settings
            ['name' => 'setting.view', 'label' => 'View Settings', 'group' => 'Settings'],
            ['name' => 'setting.update', 'label' => 'Update Settings', 'group' => 'Settings'],

            // API Keys
            ['name' => 'apikey.view', 'label' => 'View API Keys', 'group' => 'Settings'],
            ['name' => 'apikey.manage', 'label' => 'Manage API Keys', 'group' => 'Settings'],

            // WhatsApp
            ['name' => 'chat.view', 'label' => 'View WhatsApp', 'group' => 'WhatsApp'],
            ['name' => 'chat.manage', 'label' => 'Manage WhatsApp', 'group' => 'WhatsApp'],

            // Telegram
            ['name' => 'telegram.view', 'label' => 'View Telegram', 'group' => 'Telegram'],
            ['name' => 'telegram.manage', 'label' => 'Manage Telegram', 'group' => 'Telegram'],

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

            // Coordinator Management
            ['name' => 'coordinator.view', 'label' => 'View Coordinators', 'group' => 'Coordinator Management'],
            ['name' => 'coordinator.create', 'label' => 'Create Coordinator', 'group' => 'Coordinator Management'],
            ['name' => 'coordinator.edit', 'label' => 'Edit Coordinator', 'group' => 'Coordinator Management'],
            ['name' => 'coordinator.delete', 'label' => 'Delete Coordinator', 'group' => 'Coordinator Management'],

            // Investor Management
            ['name' => 'investor.view', 'label' => 'View Investors', 'group' => 'Investor Management'],
            ['name' => 'investor.create', 'label' => 'Create Investor', 'group' => 'Investor Management'],
            ['name' => 'investor.edit', 'label' => 'Edit Investor', 'group' => 'Investor Management'],
            ['name' => 'investor.delete', 'label' => 'Delete Investor', 'group' => 'Investor Management'],

            // Region Management
            ['name' => 'region.view', 'label' => 'View Regions', 'group' => 'Region Management'],
            ['name' => 'region.create', 'label' => 'Create Region', 'group' => 'Region Management'],
            ['name' => 'region.edit', 'label' => 'Edit Region', 'group' => 'Region Management'],
            ['name' => 'region.delete', 'label' => 'Delete Region', 'group' => 'Region Management'],

            // Package Management
            ['name' => 'package.view', 'label' => 'View Packages', 'group' => 'Package Management'],
            ['name' => 'package.create', 'label' => 'Create Package', 'group' => 'Package Management'],
            ['name' => 'package.edit', 'label' => 'Edit Package', 'group' => 'Package Management'],
            ['name' => 'package.delete', 'label' => 'Delete Package', 'group' => 'Package Management'],

            // Tools
            ['name' => 'calculator.view', 'label' => 'View Calculator PON', 'group' => 'Tools'],

            // Profile
            ['name' => 'profile.view', 'label' => 'View Profile', 'group' => 'Profile'],
            ['name' => 'profile.update', 'label' => 'Update Profile', 'group' => 'Profile'],

            // Notification
            ['name' => 'notification.view', 'label' => 'View Notifications', 'group' => 'Notification'],
            ['name' => 'notification.manage', 'label' => 'Manage Notifications', 'group' => 'Notification'],
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
