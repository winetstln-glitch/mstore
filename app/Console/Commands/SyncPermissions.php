<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';
    protected $description = 'Sync all permissions from PermissionSeeder to database';

    public function handle()
    {
        $this->info('Syncing permissions...');

        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'Dashboard'],
            ['name' => 'ai.view', 'label' => 'View AI Center', 'group' => 'Dashboard'],

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
            ['name' => 'sla.monitoring.view', 'label' => 'View SLA Monitoring', 'group' => 'Ticket Management'],
            ['name' => 'sla.escalation.view', 'label' => 'View Escalation Queue', 'group' => 'Ticket Management'],

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
            ['name' => 'olt.pon_ports.view', 'label' => 'View OLT PON Ports', 'group' => 'OLT Management'],
            ['name' => 'olt.pon_ports.manage', 'label' => 'Manage OLT PON Ports', 'group' => 'OLT Management'],
            ['name' => 'olt.onus.view', 'label' => 'View OLT ONUs', 'group' => 'OLT Management'],
            ['name' => 'olt.onus.manage', 'label' => 'Manage OLT ONUs', 'group' => 'OLT Management'],

            // Router Management
            ['name' => 'router.view', 'label' => 'View Routers', 'group' => 'Router Management'],
            ['name' => 'router.create', 'label' => 'Create Router', 'group' => 'Router Management'],
            ['name' => 'router.edit', 'label' => 'Edit Router', 'group' => 'Router Management'],
            ['name' => 'router.delete', 'label' => 'Delete Router', 'group' => 'Router Management'],

            // Finance
            ['name' => 'finance.view', 'label' => 'View Finance', 'group' => 'Finance'],
            ['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance'],
            ['name' => 'finance.profit_loss.view', 'label' => 'View Profit & Loss', 'group' => 'Finance'],
            ['name' => 'finance.manager_report.view', 'label' => 'View Manager Report', 'group' => 'Finance'],
            ['name' => 'finance.material_report.view', 'label' => 'View Material Report', 'group' => 'Finance'],

            // Accounting
            ['name' => 'accounting.trial_balance.view', 'label' => 'View Trial Balance', 'group' => 'Accounting'],
            ['name' => 'accounting.income_statement.view', 'label' => 'View Income Statement', 'group' => 'Accounting'],
            ['name' => 'accounting.balance_sheet.view', 'label' => 'View Balance Sheet', 'group' => 'Accounting'],
            ['name' => 'accounting.general_ledger.view', 'label' => 'View General Ledger', 'group' => 'Accounting'],

            // Map
            ['name' => 'map.view', 'label' => 'View Map', 'group' => 'Map'],
            ['name' => 'map.manage', 'label' => 'Manage Map', 'group' => 'Map'],

            // Leave Management
            ['name' => 'leave.view', 'label' => 'View Leave Requests', 'group' => 'Leave Management'],
            ['name' => 'leave.create', 'label' => 'Create Leave Request', 'group' => 'Leave Management'],
            ['name' => 'leave.edit', 'label' => 'Edit Leave Request', 'group' => 'Leave Management'],
            ['name' => 'leave.manage', 'label' => 'Manage Leave Requests', 'group' => 'Leave Management'],

            // Schedule Management
            ['name' => 'schedule.view', 'label' => 'View Schedules', 'group' => 'Schedule Management'],
            ['name' => 'schedule.manage', 'label' => 'Manage Schedules', 'group' => 'Schedule Management'],

            // Settings
            ['name' => 'setting.view', 'label' => 'View Settings', 'group' => 'Settings'],
            ['name' => 'setting.update', 'label' => 'Update Settings', 'group' => 'Settings'],

            // Payment Gateway
            ['name' => 'payment.view', 'label' => 'View Payment Gateway', 'group' => 'Settings'],
            ['name' => 'payment.edit', 'label' => 'Edit Payment Gateway', 'group' => 'Settings'],
            ['name' => 'payment.test', 'label' => 'Test Payment Gateway', 'group' => 'Settings'],
            ['name' => 'payment.delete', 'label' => 'Delete Payment Gateway', 'group' => 'Settings'],

            // API Keys
            ['name' => 'apikey.view', 'label' => 'View API Keys', 'group' => 'Settings'],
            ['name' => 'apikey.manage', 'label' => 'Manage API Keys', 'group' => 'Settings'],

            // WhatsApp
            ['name' => 'chat.view', 'label' => 'View WhatsApp', 'group' => 'WhatsApp'],
            ['name' => 'chat.manage', 'label' => 'Manage WhatsApp', 'group' => 'WhatsApp'],
            ['name' => 'whatsapp.analytics.view', 'label' => 'View WhatsApp Analytics', 'group' => 'WhatsApp'],
            ['name' => 'whatsapp.kb.manage', 'label' => 'Manage AI Knowledge Base', 'group' => 'WhatsApp'],

            // Telegram
            ['name' => 'telegram.view', 'label' => 'View Telegram', 'group' => 'Telegram'],
            ['name' => 'telegram.manage', 'label' => 'Manage Telegram', 'group' => 'Telegram'],

            // Network Monitor
            ['name' => 'genieacs.view', 'label' => 'View GenieACS', 'group' => 'Network Monitor'],
            ['name' => 'genieacs.manage', 'label' => 'Manage GenieACS', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.view', 'label' => 'View GenieACS Servers', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.create', 'label' => 'Create GenieACS Server', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.edit', 'label' => 'Edit GenieACS Server', 'group' => 'Network Monitor'],
            ['name' => 'genieacs_server.delete', 'label' => 'Delete GenieACS Server', 'group' => 'Network Monitor'],

            // Inventory
            ['name' => 'inventory.view', 'label' => 'View Inventory', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.manage', 'label' => 'Manage Inventory', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.pickup', 'label' => 'Pickup Inventory', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.stock_in', 'label' => 'Stock In Inventory', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.stock_out', 'label' => 'Stock Out Inventory', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.report', 'label' => 'View Inventory Report', 'group' => 'Inventory (Alat & Material)'],

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

            // Closure Management
            ['name' => 'closure.view', 'label' => 'View Closures', 'group' => 'Closure Management'],
            ['name' => 'closure.create', 'label' => 'Create Closure', 'group' => 'Closure Management'],
            ['name' => 'closure.edit', 'label' => 'Edit Closure', 'group' => 'Closure Management'],
            ['name' => 'closure.delete', 'label' => 'Delete Closure', 'group' => 'Closure Management'],

            // Tools (Utilities)
            ['name' => 'calculator.view', 'label' => 'View Calculator PON', 'group' => 'Utilities'],
            ['name' => 'modem-data.view', 'label' => 'View Modem Data', 'group' => 'Utilities'],
            ['name' => 'modem-data.create', 'label' => 'Create Modem Data', 'group' => 'Utilities'],
            ['name' => 'network-analyzer.view', 'label' => 'View Network Analyzer', 'group' => 'Utilities'],
            ['name' => 'network-analyzer.manage', 'label' => 'Manage Network Analyzer', 'group' => 'Utilities'],
            ['name' => 'vpn-servers.view', 'label' => 'View VPN Servers', 'group' => 'Utilities'],
            ['name' => 'vpn-servers.create', 'label' => 'Create VPN Server', 'group' => 'Utilities'],
            ['name' => 'vpn-servers.edit', 'label' => 'Edit VPN Server', 'group' => 'Utilities'],
            ['name' => 'vpn-servers.delete', 'label' => 'Delete VPN Server', 'group' => 'Utilities'],
            ['name' => 'vpn-guide.view', 'label' => 'View VPN Guide', 'group' => 'Utilities'],
            ['name' => 'client-portal.view', 'label' => 'View Client Portal', 'group' => 'Utilities'],
            ['name' => 'mixradius.view', 'label' => 'View MixRadius', 'group' => 'Utilities'],

            // Profile
            ['name' => 'profile.view', 'label' => 'View Profile', 'group' => 'Profile'],
            ['name' => 'profile.update', 'label' => 'Update Profile', 'group' => 'Profile'],

            // Notification
            ['name' => 'notification.view', 'label' => 'View Notifications', 'group' => 'Notification'],
            ['name' => 'notification.manage', 'label' => 'Manage Notifications', 'group' => 'Notification'],

            // ATK Store
            ['name' => 'atk.view', 'label' => 'View ATK Dashboard', 'group' => 'ATK Store'],
            ['name' => 'atk.pos', 'label' => 'Access ATK POS', 'group' => 'ATK Store'],
            ['name' => 'atk.manage', 'label' => 'Manage ATK Products', 'group' => 'ATK Store'],
            ['name' => 'atk.report', 'label' => 'View ATK Reports', 'group' => 'ATK Store'],
            ['name' => 'atk.cash-register.manage', 'label' => 'Manage ATK Cash Registers', 'group' => 'ATK Store'],
            ['name' => 'atk.transaction.view', 'label' => 'View ATK Transactions', 'group' => 'ATK Store'],
            ['name' => 'atk.transaction.create', 'label' => 'Create ATK Transactions', 'group' => 'ATK Store'],
            ['name' => 'atk.transaction.update', 'label' => 'Update ATK Transactions', 'group' => 'ATK Store'],
            ['name' => 'atk.transaction.delete', 'label' => 'Delete ATK Transactions', 'group' => 'ATK Store'],
            ['name' => 'atk.expense.view', 'label' => 'View ATK Expenses', 'group' => 'ATK Store'],
            ['name' => 'atk.expense.create', 'label' => 'Create ATK Expenses', 'group' => 'ATK Store'],

            // Car Wash
            ['name' => 'wash.view', 'label' => 'View Wash Dashboard', 'group' => 'Car Wash'],
            ['name' => 'wash.pos', 'label' => 'Access Wash POS', 'group' => 'Car Wash'],
            ['name' => 'wash.manage', 'label' => 'Manage Wash Services', 'group' => 'Car Wash'],
            ['name' => 'wash.report', 'label' => 'View Wash Reports', 'group' => 'Car Wash'],
            ['name' => 'wash.member.view', 'label' => 'View Wash Members', 'group' => 'Car Wash'],
            ['name' => 'wash.member.manage', 'label' => 'Manage Wash Members', 'group' => 'Car Wash'],
            ['name' => 'wash.loyalty.view', 'label' => 'View Wash Loyalty', 'group' => 'Car Wash'],
            ['name' => 'wash.loyalty.manage', 'label' => 'Manage Wash Loyalty', 'group' => 'Car Wash'],
            ['name' => 'wash.reward.view', 'label' => 'View Wash Reward Vouchers', 'group' => 'Car Wash'],
            ['name' => 'wash.reward.manage', 'label' => 'Manage Wash Reward Vouchers', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.view', 'label' => 'View Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.create', 'label' => 'Create Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.update', 'label' => 'Update Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.transaction.delete', 'label' => 'Delete Wash Transactions', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.view', 'label' => 'View Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.create', 'label' => 'Create Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.update', 'label' => 'Update Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.delete', 'label' => 'Delete Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.expense.approve', 'label' => 'Approve Wash Expenses', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.view', 'label' => 'View Wash Shifts', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.open', 'label' => 'Open Wash Shift', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.close', 'label' => 'Close Wash Shift', 'group' => 'Car Wash'],
            ['name' => 'wash.shift.manage', 'label' => 'Manage Wash Shifts', 'group' => 'Car Wash'],
            ['name' => 'wash.cash.view', 'label' => 'View Wash Cash Registers', 'group' => 'Car Wash'],
            ['name' => 'wash.cash.manage', 'label' => 'Manage Wash Cash Registers', 'group' => 'Car Wash'],
            ['name' => 'wash.closing.view', 'label' => 'View Wash Daily Closings', 'group' => 'Car Wash'],
            ['name' => 'wash.closing.create', 'label' => 'Create Wash Daily Closing', 'group' => 'Car Wash'],
            ['name' => 'wash.closing.approve', 'label' => 'Approve Wash Daily Closing', 'group' => 'Car Wash'],
            ['name' => 'wash.supplier.view', 'label' => 'View Wash Suppliers', 'group' => 'Car Wash'],
            ['name' => 'wash.supplier.manage', 'label' => 'Manage Wash Suppliers', 'group' => 'Car Wash'],
            ['name' => 'wash.stock.view', 'label' => 'View Wash Stock', 'group' => 'Car Wash'],
            ['name' => 'wash.stock.manage', 'label' => 'Manage Wash Stock', 'group' => 'Car Wash'],

            // Wedding & Event
            ['name' => 'wedding.view', 'label' => 'View Wedding & Event', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.manage', 'label' => 'Manage Wedding Packages', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.booking', 'label' => 'Manage Wedding Bookings', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.payment', 'label' => 'Manage Wedding Payments', 'group' => 'Wedding & Event'],
            ['name' => 'wedding.report', 'label' => 'View Wedding Reports', 'group' => 'Wedding & Event'],

            // CCTV Installation
            ['name' => 'cctv.view', 'label' => 'View CCTV Installation', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.manage', 'label' => 'Manage CCTV Packages', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.booking', 'label' => 'Manage CCTV Bookings', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.payment', 'label' => 'Manage CCTV Payments', 'group' => 'CCTV Installation'],
            ['name' => 'cctv.report', 'label' => 'View CCTV Reports', 'group' => 'CCTV Installation'],

            // Hotspot & PPPoE (Services)
            ['name' => 'hotspot.view', 'label' => 'View Hotspot', 'group' => 'Service Management'],
            ['name' => 'hotspot.manage', 'label' => 'Manage Hotspot', 'group' => 'Service Management'],
            ['name' => 'pppoe.view', 'label' => 'View PPPoE', 'group' => 'Service Management'],
            ['name' => 'pppoe.manage', 'label' => 'Manage PPPoE', 'group' => 'Service Management'],

            // Employee Management
            ['name' => 'employee.view', 'label' => 'View Employees', 'group' => 'Employee Management'],
            ['name' => 'employee.create', 'label' => 'Create Employee', 'group' => 'Employee Management'],
            ['name' => 'employee.edit', 'label' => 'Edit Employee', 'group' => 'Employee Management'],
            ['name' => 'employee.delete', 'label' => 'Delete Employee', 'group' => 'Employee Management'],
            ['name' => 'employee.sync', 'label' => 'Sync Employees', 'group' => 'Employee Management'],
            ['name' => 'employee.export', 'label' => 'Export Employees', 'group' => 'Employee Management'],

            // NOC Center (Phase 6)
            ['name' => 'noc.dashboard.view', 'label' => 'View NOC Dashboard', 'group' => 'NOC Center'],
            ['name' => 'noc.operational.view', 'label' => 'View NOC Operational Menu', 'group' => 'NOC Center'],
            ['name' => 'noc.diagnostic_logs.view', 'label' => 'View Diagnostic Logs', 'group' => 'NOC Center'],
            ['name' => 'noc.olt_monitoring.view', 'label' => 'View OLT Monitoring', 'group' => 'NOC Center'],
            ['name' => 'noc.fiber_monitoring.view', 'label' => 'View Fiber Monitoring', 'group' => 'NOC Center'],

            // Reporting (Phase 6)
            ['name' => 'report.noc.export', 'label' => 'Export NOC Report', 'group' => 'Reporting'],
            ['name' => 'report.whatsapp.export', 'label' => 'Export WhatsApp Report', 'group' => 'Reporting'],
            ['name' => 'report.sla.export', 'label' => 'Export SLA Report', 'group' => 'Reporting'],
            ['name' => 'report.wedding.export', 'label' => 'Export Wedding Report', 'group' => 'Reporting'],
            ['name' => 'report.cctv.export', 'label' => 'Export CCTV Report', 'group' => 'Reporting'],

            // Security & Monitoring (Phase 6)
            ['name' => 'security.monitoring.view', 'label' => 'View Security & Monitoring', 'group' => 'Security'],
        ];

        $inserted = 0;
        $updated = 0;

        foreach ($permissions as $p) {
            $existing = Permission::where('name', $p['name'])->first();
            if (!$existing) {
                Permission::create($p);
                $inserted++;
                $this->info('Inserted: ' . $p['name'] . ' (' . $p['group'] . ')');
            } else {
                $existing->update([
                    'label' => $p['label'],
                    'group' => $p['group'],
                ]);
                $updated++;
            }
        }

        $this->info('Done!');
        $this->line('Inserted: ' . $inserted . ' permissions');
        $this->line('Updated: ' . $updated . ' permissions');
        $this->line('Total in database: ' . Permission::count());
    }
}
