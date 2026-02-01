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
                    'Inventory',
                    'Profile',
                    'Notification'
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
                    'router.view',
                    'genieacs.view',
                    
                    // Communications & Tools
                    'chat.view',     // WhatsApp
                    'telegram.view', // Telegram
                    'calculator.view',
                    
                    // Settings (View only)
                    'setting.view',
                ];
                $viewPermissions = Permission::whereIn('name', $viewPermissionNames)->get();

                $role->permissions()->sync($managePermissions->merge($viewPermissions));
            }
        }
    }
}
