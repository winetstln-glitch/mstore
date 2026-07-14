<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions') || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $permissions = [
            ['name' => 'noc.dashboard.view', 'label' => 'View NOC Dashboard', 'group' => 'NOC Center'],
            ['name' => 'noc.operational.view', 'label' => 'View NOC Operational Menu', 'group' => 'NOC Center'],
            ['name' => 'noc.diagnostic_logs.view', 'label' => 'View Diagnostic Logs', 'group' => 'NOC Center'],
            ['name' => 'noc.olt_monitoring.view', 'label' => 'View OLT Monitoring', 'group' => 'NOC Center'],
            ['name' => 'noc.fiber_monitoring.view', 'label' => 'View Fiber Monitoring', 'group' => 'NOC Center'],

            ['name' => 'whatsapp.analytics.view', 'label' => 'View WhatsApp Analytics', 'group' => 'WhatsApp'],
            ['name' => 'whatsapp.kb.manage', 'label' => 'Manage AI Knowledge Base', 'group' => 'WhatsApp'],

            ['name' => 'sla.monitoring.view', 'label' => 'View SLA Monitoring', 'group' => 'Ticket Management'],
            ['name' => 'sla.escalation.view', 'label' => 'View Escalation Queue', 'group' => 'Ticket Management'],

            ['name' => 'report.noc.export', 'label' => 'Export NOC Report', 'group' => 'Reporting'],
            ['name' => 'report.whatsapp.export', 'label' => 'Export WhatsApp Report', 'group' => 'Reporting'],
            ['name' => 'report.sla.export', 'label' => 'Export SLA Report', 'group' => 'Reporting'],

            ['name' => 'security.monitoring.view', 'label' => 'View Security & Monitoring', 'group' => 'Security'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')->where('name', $permission['name'])->first();
            if ($existing) {
                $permissionIds[$permission['name']] = (int) $existing->id;
                continue;
            }

            $id = (int) DB::table('permissions')->insertGetId([
                ...$permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $permissionIds[$permission['name']] = $id;
        }

        $roleNamesToPermissionNames = [
            'noc' => [
                'noc.dashboard.view',
                'noc.operational.view',
                'noc.diagnostic_logs.view',
                'noc.olt_monitoring.view',
                'noc.fiber_monitoring.view',
                'whatsapp.analytics.view',
                'sla.monitoring.view',
                'sla.escalation.view',
                'report.noc.export',
                'report.whatsapp.export',
                'report.sla.export',
                'security.monitoring.view',
            ],
            'network-operations-center' => [
                'noc.dashboard.view',
                'noc.operational.view',
                'noc.diagnostic_logs.view',
                'noc.olt_monitoring.view',
                'noc.fiber_monitoring.view',
                'whatsapp.analytics.view',
                'sla.monitoring.view',
                'sla.escalation.view',
                'report.noc.export',
                'report.whatsapp.export',
                'report.sla.export',
                'security.monitoring.view',
            ],
            'technician' => [
                'sla.monitoring.view',
                'sla.escalation.view',
            ],
            'finance' => [
                'whatsapp.analytics.view',
                'report.whatsapp.export',
            ],
            'admin' => array_keys($permissionIds),
        ];

        $roles = DB::table('roles')->whereIn('name', array_keys($roleNamesToPermissionNames))->get(['id', 'name']);
        foreach ($roles as $role) {
            $permissionNames = $roleNamesToPermissionNames[$role->name] ?? [];
            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;
                if (! $permissionId) {
                    continue;
                }
                $exists = DB::table('permission_role')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions') || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $names = [
            'noc.dashboard.view',
            'noc.operational.view',
            'noc.diagnostic_logs.view',
            'noc.olt_monitoring.view',
            'noc.fiber_monitoring.view',
            'whatsapp.analytics.view',
            'whatsapp.kb.manage',
            'sla.monitoring.view',
            'sla.escalation.view',
            'report.noc.export',
            'report.whatsapp.export',
            'report.sla.export',
            'security.monitoring.view',
        ];

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id')->all();
        if (! empty($ids)) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};

