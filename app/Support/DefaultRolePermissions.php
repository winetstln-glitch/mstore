<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DefaultRolePermissions
{
    public static function definitions(): array
    {
        return [
            'super-admin' => [
                'label' => 'Super Administrator',
                'grants_all' => true,
            ],

            'manager' => [
                'label' => 'Manager',
                'permissions' => [
                    'dashboard.view',
                    'finance.view',
                    'finance.report',
                    'investor.view',
                    'map.view',
                    'customer.view',
                    'ticket.view',
                    'atk.*',
                    'wash.*',
                    'wedding.*',
                    'cctv.*',
                    'inventory.view',
                    'attendance.*',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'schedule.*',
                    'report.*.export',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                    'kasbon.view',
                    'kasbon.manage',
                ],
            ],

            'finance' => [
                'label' => 'Finance / Admin Keuangan',
                'permissions' => [
                    'dashboard.view',
                    'finance.*',
                    'investor.view',
                    'investor.create',
                    'investor.edit',
                    'accounting.*',
                    'receipt.*',
                    'fee.*',
                    'payment.*',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'attendance.delete',
                    'attendance.report',
                    'leave.*',
                    'schedule.*',
                    'employee.view',
                    'employee.create',
                    'employee.edit',
                    'ticket.view',
                    'ticket.edit',
                    'customer.view',
                    'customer.create',
                    'customer.edit',
                    'kasbon.view',
                    'kasbon.manage',
                    'report.*.export',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'notification.manage',
                ],
            ],

            'wash-cashier' => [
                'label' => 'Kasir POS Wash',
                'permissions' => [
                    'dashboard.view',
                    'wash.view',
                    'wash.pos',
                    'wash.report',
                    'wash.expense.view',
                    'wash.expense.create',
                    'wash.shift.view',
                    'wash.shift.open',
                    'wash.shift.close',
                    'wash.cash.view',
                    'wash.cash.manage',
                    'wash.closing.view',
                    'wash.closing.create',
                    'wash.supplier.view',
                    'wash.package.view',
                    'wash.member.view',
                    'wash.loyalty.view',
                    'wash.reward.view',
                    'inventory.view',
                    'inventory.create',
                    'inventory.edit',
                    'inventory.manage',
                    'inventory.pickup',
                    'attendance.view',
                    'attendance.create',
                    'schedule.view',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'atk-cashier' => [
                'label' => 'Kasir POS ATK',
                'permissions' => [
                    'dashboard.view',
                    'atk.view',
                    'atk.pos',
                    'atk.report',
                    'atk.cash-register.manage',
                    'inventory.view',
                    'inventory.create',
                    'inventory.edit',
                    'inventory.manage',
                    'inventory.pickup',
                    'attendance.view',
                    'attendance.create',
                    'schedule.view',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'wash-operator' => [
                'label' => 'Operator Wash / Tukang Cuci',
                'permissions' => [
                    'dashboard.view',
                    'wash.view',
                    'attendance.view',
                    'attendance.create',
                    'schedule.view',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'noc-operator' => [
                'label' => 'NOC / Network Monitoring',
                'permissions' => [
                    'dashboard.view',
                    'customer.view',
                    'map.view',
                    'olt.view',
                    'router.view',
                    'odc.view',
                    'odp.view',
                    'closure.view',
                    'htb.view',
                    'network-monitor.view',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'ticket.complete',
                    'attendance.view',
                    'attendance.create',
                    'schedule.view',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'field-leader' => [
                'label' => 'Leader Jaringan',
                'permissions' => [
                    'dashboard.view',
                    'customer.view',
                    'customer.create',
                    'customer.edit',
                    'map.view',
                    'odc.view',
                    'odp.view',
                    'closure.view',
                    'htb.view',
                    'installation.view',
                    'installation.create',
                    'installation.edit',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'ticket.complete',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'schedule.view',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'technician.view',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'field-technician' => [
                'label' => 'Teknisi Lapangan',
                'permissions' => [
                    'dashboard.view',
                    'customer.view',
                    'map.view',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'ticket.complete',
                    'installation.view',
                    'installation.edit',
                    'attendance.view',
                    'attendance.create',
                    'schedule.view',
                    'leave.view',
                    'leave.create',
                    'leave.edit',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'partner' => [
                'label' => 'Mitra / Reseller',
                'permissions' => [
                    'dashboard.view',
                    'customer.view',
                    'customer.create',
                    'customer.edit',
                    'ticket.view',
                    'ticket.create',
                    'ticket.edit',
                    'ticket.complete',
                    'package.view',
                    'map.view',
                    'profile.view',
                    'profile.update',
                    'notification.view',
                ],
            ],

            'customer' => [
                'label' => 'Pelanggan',
                'permissions' => [
                    'profile.view',
                    'profile.update',
                    'notification.view',
                    'ticket.create',
                ],
            ],
        ];
    }

    public static function primaryDefinitions(): array
    {
        return array_filter(
            self::definitions(),
            static fn (array $definition): bool => ! isset($definition['inherits'])
        );
    }

    public static function resolve(string $roleName): array
    {
        $definitions = self::definitions();
        $definition = $definitions[$roleName] ?? ['label' => ucfirst($roleName), 'permissions' => []];

        if (isset($definition['inherits'])) {
            $parent = self::resolve((string) $definition['inherits']);
            $parentPerms = $parent['permissions'] ?? [];
            $extra = $definition['extra_permissions'] ?? [];
            $exclude = $definition['exclude_permissions'] ?? [];

            if (! is_array($parentPerms)) {
                $parentPerms = [];
            }

            $merged = array_unique(array_merge($parentPerms, (array) $extra));
            if ($exclude) {
                $merged = array_values(array_diff($merged, (array) $exclude));
            }

            return [
                'label' => $definition['label'] ?? ($parent['label'] ?? ucfirst($roleName)),
                'permissions' => array_values($merged),
            ];
        }

        return $definition;
    }

    public static function permissionNames(string $roleName): array
    {
        $definition = self::resolve($roleName);

        return (array) ($definition['permissions'] ?? []);
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

            $permissionNames = self::permissionNames($roleName);
            $ids = [];
            foreach ($permissionNames as $permName) {
                if (str_contains($permName, '.*') || str_ends_with($permName, '*')) {
                    $prefix = rtrim($permName, '*');
                    foreach ($allowedByName as $name => $id) {
                        if (str_starts_with((string) $name, $prefix)) {
                            $ids[] = $id;
                        }
                    }
                } else {
                    if (isset($allowedByName[$permName])) {
                        $ids[] = $allowedByName[$permName];
                    }
                }
            }

            $ids = array_values(array_unique(array_intersect($ids, $allowedIds)));
            $templates[$label] = $ids;
        }

        return $templates;
    }

    public static function expandPermissionNames(array $rawPermissionNames, Collection $allPermissions): array
    {
        $flatNames = $allPermissions->pluck('name')->all();
        $expanded = [];
        foreach ($rawPermissionNames as $perm) {
            if (! is_string($perm)) {
                continue;
            }
            if (str_contains($perm, '.*') || str_ends_with($perm, '*')) {
                $prefix = rtrim($perm, '.*');
                foreach ($flatNames as $dbName) {
                    if (str_starts_with((string) $dbName, $prefix)) {
                        $expanded[] = (string) $dbName;
                    }
                }
            } else {
                $expanded[] = $perm;
            }
        }
        return array_values(array_unique($expanded));
    }
}
