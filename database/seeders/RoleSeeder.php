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
            ['name' => 'management', 'label' => 'Management'],
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
                    'PPPoE',
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
                    // 'attendance.report', // Removed to restrict global report access
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
                    'inventory.view',
                    'inventory.pickup', // Added Inventory Pickup
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
                    'finance.view',
                    // Added based on user request for menu visibility
                    'odc.view',
                    'htb.view',
                    'odp.view',
                    'customer.view',
                    'calculator.view',
                    'genieacs.view',
                    'router.view',
                ])->get();
                $role->permissions()->sync($permissions);
            } elseif ($role->name === 'finance') {
                // Finance Staff permissions
                // Grant access to all major operational modules for visibility (Audit/Reporting)
                // and full management for Finance-specific modules.
                
                // 1. Full Management Access
                $manageGroups = [
                    'Finance',
                    'Investor Management',
                    'Package Management', 
                    'Inventory (Alat & Material)',
                    'Profile',
                    'Notification',
                    'Car Wash',
                    'ATK Cashier'
                ];
                $managePermissions = Permission::whereIn('group', $manageGroups)->get();

                // 2. View Only Access (for Menu visibility and Audit)
                $viewPermissionNames = [
                    // Core
                    'dashboard.view',
                    
                    // Operations
                    'customer.view',
                    'ticket.view',
                    'installation.view',
                    'technician.view',
                    'coordinator.view',
                    'region.view',
                    
                    // HR / Payroll
                    'attendance.view',
                    'attendance.report',
                    'leave.view',
                    'schedule.view',
                    
                    // Network Assets (for Asset Tracking/Audit)
                    'map.view',
                    'olt.view',
                    'odc.view',
                    'odp.view',
                    'htb.view',
                    'hotspot.view',
                    'router.view',
                    'genieacs.view',
                    
                    // Communications & Tools
                    'chat.view',     // WhatsApp
                    'telegram.view', // Telegram
                    'apikey.view',   // Google Map API
                    'calculator.view',
                    
                    // Settings (View only)
                    'setting.view',

                    // ATK Cashier
                    'atk.view',
                    'atk.manage',
                    'atk.pos',
                    'atk.report',
                ];
                $viewPermissions = Permission::whereIn('name', $viewPermissionNames)->get();

                $role->permissions()->sync($managePermissions->merge($viewPermissions));
            } elseif ($role->name === 'management') {
                // Management permissions
                // 1. View Access to Everything (Dashboard, Reports, Logs, etc.)
                $viewPermissions = Permission::where('name', 'like', '%.view')
                    ->orWhere('name', 'like', '%.report')
                    ->get();

                // 2. Manage Access to Business & Operations
                $manageGroups = [
                    'Dashboard',
                    'Customer Management',
                    'Ticket Management',
                    'Installation Management',
                    'Technician Management',
                    'Attendance',
                    'ODC Management',
                    'ODP Management',
                    'HTB Management',
                    'OLT Management',
                    'Router Management',
                    'Finance',
                    'Hotspot',
                    'PPPoE', // Added PPPoE
                    'Map',
                    'Leave Management',
                    'Schedule Management',
                    'Network Monitor',
                    'Inventory (Alat & Material)',
                    'Coordinator Management',
                    'Investor Management',
                    'Region Management',
                    'Package Management',
                    'Utilities',
                    'Profile',
                    'Notification',
                    'ATK Cashier',
                    'Car Wash', // Added Car Wash
                    'WhatsApp',
                    'Telegram'
                ];
                
                $managePermissions = Permission::whereIn('group', $manageGroups)->get();

                $role->permissions()->sync($viewPermissions->merge($managePermissions));
            }
        }
    }
}
