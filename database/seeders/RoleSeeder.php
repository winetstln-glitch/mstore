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
        $chatPermissionIds = Permission::whereIn('name', ['chat.view', 'chat.manage'])->pluck('id')->all();
        $allPermissionIds = Permission::query()->pluck('id')->all();

        $roles = [
            ['name' => 'admin', 'label' => 'Administrator'],
            ['name' => 'direktur', 'label' => 'Direktur'],
            ['name' => 'leader', 'label' => 'Leader'],
            ['name' => 'noc', 'label' => 'Network Operations Center'],
            ['name' => 'network-operations-center', 'label' => 'Network Operations Center'], // Legacy/Existing role support
            ['name' => 'technician', 'label' => 'Technician'],
            ['name' => 'coordinator', 'label' => 'Coordinator'],
            ['name' => 'customer-service', 'label' => 'Customer Service'],
            ['name' => 'hrd', 'label' => 'HRD'],
            ['name' => 'customer', 'label' => 'Customer'],
            ['name' => 'reseller', 'label' => 'Reseller'],
            ['name' => 'finance', 'label' => 'Finance Staff'],
            ['name' => 'hrd-manager', 'label' => 'HRD Manager'],
            ['name' => 'kasir-atk', 'label' => 'Kasir ATK'],
            ['name' => 'kasir-wash', 'label' => 'Kasir Wash'],
            ['name' => 'karyawan-wash', 'label' => 'Karyawan Wash'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assign Permissions
            if (in_array($role->name, ['admin', 'direktur'])) {
                // Admin and Direktur get all permissions
                if (! empty($allPermissionIds)) {
                    $role->permissions()->syncWithoutDetaching($allPermissionIds);
                }
            } elseif ($role->name === 'leader') {
                // Leader permissions: ticket management + attendance monitoring
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'ticket.delete',
                    'attendance.view',
                    'attendance.report',
                    'schedule.view',
                    'map.view',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif (in_array($role->name, ['noc', 'network-operations-center'])) {
                // NOC permissions (align with existing groups in PermissionSeeder)
                $permissions = Permission::whereIn('group', [
                    'Dashboard',
                    'Customer Management',
                    'Ticket Management',
                    'Installation Management',
                    'Technician Management',
                    'Router Management',
                    'OLT Management',
                    'ODC Management',
                    'ODP Management',
                    'HTB Management',
                    'Service Management', // PPPoE/Hotspot live services
                    'Map',
                    'Network Monitor',
                    'NOC Center',
                    'Profile',
                    'Notification',
                    'Reporting',
                    'Security',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
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
                    'attendance.edit',
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
                    'sla.monitoring.view',
                    'sla.escalation.view',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
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
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'customer-service') {
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'customer.view',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'chat.view',
                    'chat.manage',
                    'whatsapp.analytics.view',
                    'whatsapp.kb.manage',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'hrd') {
                $permissions = Permission::whereIn('group', [
                    'Dashboard',
                    'Employee Management',
                    'Attendance',
                    'Leave Management',
                    'Schedule Management',
                    'Profile',
                    'Notification',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'reseller') {
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'customer.view',
                    'customer.create',
                    'customer.edit',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'installation.view',
                    'router.view',
                    'hotspot.view',
                    'pppoe.view',
                    'map.view',
                    'finance.view',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'finance') {
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'ticket.view',
                    'ticket.edit',
                    'finance.view',
                    'finance.manage',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                    'whatsapp.analytics.view',
                    'report.whatsapp.export',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'kasir-atk') {
                // Kasir ATK: hanya menu ATK
                $permissions = Permission::whereIn('name', [
                    'atk.view',
                    'atk.pos',
                    'atk.report',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'profile.view',
                    'profile.update',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'kasir-wash') {
                // Izin kasir-wash: teknisi + modul wash
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'ticket.view',
                    'ticket.edit',
                    'installation.view',
                    'installation.edit',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
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
                    'inventory.pickup',
                    'wash.view',
                    'wash.pos',
                    'wash.manage',
                    'wash.report',
                    'wash.member.view',
                    'wash.member.manage',
                    'wash.loyalty.view',
                    'wash.loyalty.manage',
                    'wash.reward.view',
                    'wash.reward.manage',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'karyawan-wash') {
                // Izin karyawan-wash: teknisi + modul wash
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'ticket.view',
                    'ticket.edit',
                    'installation.view',
                    'installation.edit',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
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
                    'inventory.pickup',
                    'wash.view',
                    'wash.pos',
                    'wash.manage',
                    'wash.report',
                    'wash.member.view',
                    'wash.member.manage',
                    'wash.loyalty.view',
                    'wash.loyalty.manage',
                    'wash.reward.view',
                    'wash.reward.manage',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            } elseif ($role->name === 'hrd-manager') {
                // Samakan izin hrd-manager dengan teknisi
                $permissions = Permission::whereIn('name', [
                    'dashboard.view',
                    'ticket.view',
                    'ticket.edit',
                    'installation.view',
                    'installation.edit',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
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
                    'inventory.pickup',
                ])->get();
                $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
            }

            if (! empty($chatPermissionIds)) {
                $role->permissions()->syncWithoutDetaching($chatPermissionIds);
            }
        }
    }
}
