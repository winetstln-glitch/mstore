<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;

class DefaultRolePermissions
{
    public static function definitions(): array
    {
        return [
            Role::ADMIN => [
                'label' => 'Administrator',
                'grants_all' => true,
            ],
            Role::DIREKTUR => [
                'label' => 'Direktur',
                'grants_all' => true,
            ],
            Role::LEADER => [
                'label' => 'Leader',
                'permissions' => [
                    'dashboard.view',
                    'ticket.view', 'ticket.create', 'ticket.edit', 'ticket.delete', 'ticket.complete',
                    'attendance.view', 'attendance.report',
                    'schedule.view', 'schedule.manage',
                    'leave.view', 'leave.create', 'leave.edit',
                    'employee.view',
                    'inventory.view',
                    'map.view',
                    'profile.view', 'profile.update',
                    'notification.view', 'notification.manage',
                ],
            ],
            Role::NOC => [
                'label' => 'Network Operations Center',
                'permissions' => [
                    'dashboard.view',
                    'customer.view',
                    'installation.view',
                    'package.view',
                    'pppoe.view',
                    'hotspot.view',
                    'voucher.view',
                    'genieacs.view',
                    'genieacs_server.view',
                    'map.view',
                    'router.view',
                    'calculator.view',
                    'olt.view',
                    'odc.view',
                    'odp.view',
                    'closure.view',
                    'htb.view',
                    'ticket.view',
                    'modem-data.view',
                    'sla.monitoring.view',
                    'sla.escalation.view',
                    'noc.dashboard.view',
                    'noc.operational.view',
                    'noc.diagnostic_logs.view',
                    'noc.olt_monitoring.view',
                    'noc.fiber_monitoring.view',
                    'report.noc.export',
                    'report.sla.export',
                    'profile.view', 'profile.update',
                    'notification.view', 'notification.manage',
                ],
            ],
            Role::NOC_LEGACY => [
                'label' => 'Network Operations Center',
                'inherits' => Role::NOC,
            ],
            Role::TECHNICIAN => [
                'label' => 'Technician',
                'permissions' => [
                    'dashboard.view',
                    'ticket.view', 'ticket.edit', 'ticket.complete',
                    'installation.view', 'installation.edit',
                    'attendance.view', 'attendance.create', 'attendance.edit',
                    'schedule.view',
                    'leave.view', 'leave.create',
                    'map.view',
                    'odc.view', 'odc.edit',
                    'odp.view', 'odp.edit',
                    'inventory.view', 'inventory.pickup',
                    'modem-data.view', 'modem-data.create',
                    'calculator.view',
                    'profile.view', 'profile.update',
                    'notification.view',
                    'sla.monitoring.view',
                ],
            ],
            Role::COORDINATOR => [
                'label' => 'Coordinator',
                'permissions' => [
                    'dashboard.view',
                    'customer.view',
                    'finance.view',
                    'inventory.view', 'inventory.manage', 'inventory.pickup',
                    'map.view',
                    'odc.view',
                    'odp.view',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],
            Role::CUSTOMER_SERVICE => [
                'label' => 'Customer Service',
                'permissions' => [
                    'dashboard.view',
                    'customer.view', 'customer.create', 'customer.edit',
                    'installation.view',
                    'package.view',
                    'ticket.view', 'ticket.create', 'ticket.edit',
                    'chat.view', 'chat.manage',
                    'whatsapp.analytics.view',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],

            Role::CUSTOMER => [
                'label' => 'Customer',
                'permissions' => [],
            ],
            Role::RESELLER => [
                'label' => 'Reseller',
                'permissions' => [
                    'dashboard.view',
                    'customer.view', 'customer.create', 'customer.edit', 'customer.export',
                    'ticket.view', 'ticket.create', 'ticket.edit', 'ticket.complete',
                    'installation.view', 'installation.create', 'installation.edit',
                    'package.view',
                    'hotspot.view',
                    'pppoe.view',
                    'map.view',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],
            Role::FINANCE => [
                'label' => 'Finance Staff',
                'permissions' => [
                    'dashboard.view',
                    'finance.view', 'finance.manage',
                    'accounting.view',
                    'investor.view',
                    'attendance.view', 'attendance.report',
                    'profile.view', 'profile.update',
                    'notification.view', 'notification.manage',
                ],
            ],
            Role::HRD_MANAGER => [
                'label' => 'HRD Manager',
                'grants_all' => true,
            ],
            Role::KASIR_ATK => [
                'label' => 'Kasir ATK',
                'permissions' => [
                    'atk.view', 'atk.pos', 'atk.report', 'atk.cash-register.manage', 'atk.manage',
                    'receipt.view', 'receipt.manage', 'receipt.template.view',
                    'fee.view', 'fee.manage',
                    'attendance.view', 'attendance.create',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],
            Role::KASIR_WASH => [
                'label' => 'Kasir Wash',
                'permissions' => [
                    'dashboard.view',
                    'attendance.view', 'attendance.create',
                    'schedule.view',
                    'leave.view', 'leave.create', 'leave.edit',
                    'wash.view', 'wash.pos', 'wash.report', 'wash.manage',
                    'wash.member.view',
                    'wash.loyalty.view',
                    'wash.reward.view',
                    'wash.expense.view', 'wash.expense.create', 'wash.expense.update', 'wash.expense.delete',
                    'wash.shift.view',
                    'wash.shift.open',
                    'wash.shift.close',
                    'wash.cash.view',
                    'wash.cash.manage',
                    'wash.closing.view',
                    'wash.closing.create',
                    'wash.supplier.view',
                    'wash.package.view',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],
            Role::KARYAWAN_WASH => [
                'label' => 'Karyawan Wash',
                'permissions' => [
                    'dashboard.view',
                    'attendance.view', 'attendance.create',
                    'schedule.view',
                    'leave.view', 'leave.create', 'leave.edit',
                    'wash.view',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],
            Role::STAFF_GUDANG => [
                'label' => 'Staff Gudang',
                'permissions' => [
                    'dashboard.view',
                    'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete', 'inventory.manage', 'inventory.pickup', 'inventory.stock_in', 'inventory.stock_out', 'inventory.report',
                    'profile.view', 'profile.update',
                    'notification.view',
                ],
            ],
        ];
    }

    public static function primaryDefinitions(): array
    {
        return array_filter(self::definitions(), static fn (array $definition): bool => ! isset($definition['inherits']));
    }

    public static function resolve(string $roleName): array
    {
        $definitions = self::definitions();
        $definition = $definitions[$roleName] ?? ['label' => ucfirst($roleName), 'permissions' => []];

        if (isset($definition['inherits'])) {
            return self::resolve($definition['inherits']);
        }

        return $definition;
    }

    public static function permissionNames(string $roleName): array
    {
        $definition = self::resolve($roleName);

        return $definition['permissions'] ?? [];
    }

    public static function grantsAll(string $roleName): bool
    {
        $definition = self::resolve($roleName);

        return (bool) ($definition['grants_all'] ?? false);
    }

    public static function standardTemplatePermissionIds(Collection $allowedPermissions): array
    {
        $allowedIds = $allowedPermissions->pluck('id')->all();
        $allPermissionIds = $allowedPermissions->pluck('id')->values()->toArray();
        $allowedByName = $allowedPermissions->pluck('id', 'name');

        $templates = [];
        foreach (self::primaryDefinitions() as $roleName => $definition) {
            $label = $definition['label'];

            if (self::grantsAll($roleName)) {
                $templates[$label] = $allPermissionIds;
                continue;
            }

            $ids = $allowedByName->only(self::permissionNames($roleName))->values()->toArray();
            $templates[$label] = array_values(array_intersect($ids, $allowedIds));
        }

        return $templates;
    }
}
