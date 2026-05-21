<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $correctPermissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'label' => 'Lihat Dashboard', 'group' => 'Dashboard'],

            // Customer Management
            ['name' => 'customer.view', 'label' => 'Lihat Pelanggan', 'group' => 'Customer Management'],
            ['name' => 'customer.create', 'label' => 'Tambah Pelanggan', 'group' => 'Customer Management'],
            ['name' => 'customer.edit', 'label' => 'Edit Pelanggan', 'group' => 'Customer Management'],
            ['name' => 'customer.delete', 'label' => 'Hapus Pelanggan', 'group' => 'Customer Management'],
            ['name' => 'customer.import', 'label' => 'Import Pelanggan', 'group' => 'Customer Management'],
            ['name' => 'customer.export', 'label' => 'Export Pelanggan', 'group' => 'Customer Management'],

            // Ticket Management
            ['name' => 'ticket.view', 'label' => 'Lihat Tiket', 'group' => 'Ticket Management'],
            ['name' => 'ticket.create', 'label' => 'Tambah Tiket', 'group' => 'Ticket Management'],
            ['name' => 'ticket.edit', 'label' => 'Edit Tiket', 'group' => 'Ticket Management'],
            ['name' => 'ticket.delete', 'label' => 'Hapus Tiket', 'group' => 'Ticket Management'],
            ['name' => 'ticket.complete', 'label' => 'Selesaikan Tiket', 'group' => 'Ticket Management'],

            // Installation Management
            ['name' => 'installation.view', 'label' => 'Lihat Instalasi', 'group' => 'Installation Management'],
            ['name' => 'installation.create', 'label' => 'Tambah Instalasi', 'group' => 'Installation Management'],
            ['name' => 'installation.edit', 'label' => 'Edit Instalasi', 'group' => 'Installation Management'],
            ['name' => 'installation.delete', 'label' => 'Hapus Instalasi', 'group' => 'Installation Management'],

            // OLT Management
            ['name' => 'olt.view', 'label' => 'Lihat OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.create', 'label' => 'Tambah OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.edit', 'label' => 'Edit OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.delete', 'label' => 'Hapus OLT', 'group' => 'OLT Management'],
            ['name' => 'olt.poll', 'label' => 'Polling OLT', 'group' => 'OLT Management'],
            ['name' => 'ont.view', 'label' => 'Lihat ONU', 'group' => 'OLT Management'],
            ['name' => 'ont.edit', 'label' => 'Edit ONU', 'group' => 'OLT Management'],
            ['name' => 'ont.delete', 'label' => 'Hapus ONU', 'group' => 'OLT Management'],
            ['name' => 'ont.reboot', 'label' => 'Reboot ONU', 'group' => 'OLT Management'],

            // Router Management
            ['name' => 'router.view', 'label' => 'Lihat Router', 'group' => 'Router Management'],
            ['name' => 'router.create', 'label' => 'Tambah Router', 'group' => 'Router Management'],
            ['name' => 'router.edit', 'label' => 'Edit Router', 'group' => 'Router Management'],
            ['name' => 'router.delete', 'label' => 'Hapus Router', 'group' => 'Router Management'],

            // ODC Management
            ['name' => 'odc.view', 'label' => 'Lihat ODC', 'group' => 'ODC Management'],
            ['name' => 'odc.create', 'label' => 'Tambah ODC', 'group' => 'ODC Management'],
            ['name' => 'odc.edit', 'label' => 'Edit ODC', 'group' => 'ODC Management'],
            ['name' => 'odc.delete', 'label' => 'Hapus ODC', 'group' => 'ODC Management'],
            ['name' => 'odc.export', 'label' => 'Export ODC', 'group' => 'ODC Management'],

            // ODP Management
            ['name' => 'odp.view', 'label' => 'Lihat ODP', 'group' => 'ODP Management'],
            ['name' => 'odp.create', 'label' => 'Tambah ODP', 'group' => 'ODP Management'],
            ['name' => 'odp.edit', 'label' => 'Edit ODP', 'group' => 'ODP Management'],
            ['name' => 'odp.delete', 'label' => 'Hapus ODP', 'group' => 'ODP Management'],
            ['name' => 'odp.export', 'label' => 'Export ODP', 'group' => 'ODP Management'],

            // Closure Management
            ['name' => 'closure.view', 'label' => 'Lihat Closure', 'group' => 'Closure Management'],
            ['name' => 'closure.create', 'label' => 'Tambah Closure', 'group' => 'Closure Management'],
            ['name' => 'closure.edit', 'label' => 'Edit Closure', 'group' => 'Closure Management'],
            ['name' => 'closure.delete', 'label' => 'Hapus Closure', 'group' => 'Closure Management'],

            // HTB Management
            ['name' => 'htb.view', 'label' => 'Lihat HTB', 'group' => 'HTB Management'],
            ['name' => 'htb.create', 'label' => 'Tambah HTB', 'group' => 'HTB Management'],
            ['name' => 'htb.edit', 'label' => 'Edit HTB', 'group' => 'HTB Management'],
            ['name' => 'htb.delete', 'label' => 'Hapus HTB', 'group' => 'HTB Management'],

            // PPPoE Management
            ['name' => 'pppoe.view', 'label' => 'Lihat PPPoE', 'group' => 'PPPoE Management'],
            ['name' => 'pppoe.create', 'label' => 'Tambah PPPoE', 'group' => 'PPPoE Management'],
            ['name' => 'pppoe.edit', 'label' => 'Edit PPPoE', 'group' => 'PPPoE Management'],
            ['name' => 'pppoe.delete', 'label' => 'Hapus PPPoE', 'group' => 'PPPoE Management'],

            // Hotspot Management
            ['name' => 'hotspot.view', 'label' => 'Lihat Hotspot', 'group' => 'Hotspot Management'],
            ['name' => 'hotspot.create', 'label' => 'Tambah Hotspot', 'group' => 'Hotspot Management'],
            ['name' => 'hotspot.edit', 'label' => 'Edit Hotspot', 'group' => 'Hotspot Management'],
            ['name' => 'hotspot.delete', 'label' => 'Hapus Hotspot', 'group' => 'Hotspot Management'],

            // Radius
            ['name' => 'radius.view', 'label' => 'Lihat Radius', 'group' => 'Radius'],
            ['name' => 'radius.create', 'label' => 'Tambah Radius', 'group' => 'Radius'],
            ['name' => 'radius.edit', 'label' => 'Edit Radius', 'group' => 'Radius'],
            ['name' => 'radius.delete', 'label' => 'Hapus Radius', 'group' => 'Radius'],

            // Map
            ['name' => 'map.view', 'label' => 'Lihat Peta', 'group' => 'Map'],
            ['name' => 'map.edit', 'label' => 'Edit Peta', 'group' => 'Map'],

            // Network Monitor
            ['name' => 'network-monitor.view', 'label' => 'Lihat Monitor Jaringan', 'group' => 'Network Monitor'],

            // Finance
            ['name' => 'finance.view', 'label' => 'Lihat Keuangan', 'group' => 'Finance'],
            ['name' => 'finance.create', 'label' => 'Tambah Transaksi Keuangan', 'group' => 'Finance'],
            ['name' => 'finance.edit', 'label' => 'Edit Transaksi Keuangan', 'group' => 'Finance'],
            ['name' => 'finance.delete', 'label' => 'Hapus Transaksi Keuangan', 'group' => 'Finance'],
            ['name' => 'finance.report', 'label' => 'Laporan Keuangan', 'group' => 'Finance'],

            // Investor Management
            ['name' => 'investor.view', 'label' => 'Lihat Investor', 'group' => 'Investor Management'],
            ['name' => 'investor.create', 'label' => 'Tambah Investor', 'group' => 'Investor Management'],
            ['name' => 'investor.edit', 'label' => 'Edit Investor', 'group' => 'Investor Management'],
            ['name' => 'investor.delete', 'label' => 'Hapus Investor', 'group' => 'Investor Management'],

            // Technician Management
            ['name' => 'technician.view', 'label' => 'Lihat Teknisi', 'group' => 'Technician Management'],
            ['name' => 'technician.create', 'label' => 'Tambah Teknisi', 'group' => 'Technician Management'],
            ['name' => 'technician.edit', 'label' => 'Edit Teknisi', 'group' => 'Technician Management'],
            ['name' => 'technician.delete', 'label' => 'Hapus Teknisi', 'group' => 'Technician Management'],

            // Attendance
            ['name' => 'attendance.view', 'label' => 'Lihat Absensi', 'group' => 'Attendance'],
            ['name' => 'attendance.create', 'label' => 'Tambah Absensi', 'group' => 'Attendance'],
            ['name' => 'attendance.edit', 'label' => 'Edit Absensi', 'group' => 'Attendance'],
            ['name' => 'attendance.delete', 'label' => 'Hapus Absensi', 'group' => 'Attendance'],
            ['name' => 'attendance.report', 'label' => 'Laporan Absensi', 'group' => 'Attendance'],

            // Leave Management
            ['name' => 'leave.view', 'label' => 'Lihat Cuti', 'group' => 'Leave Management'],
            ['name' => 'leave.create', 'label' => 'Tambah Cuti', 'group' => 'Leave Management'],
            ['name' => 'leave.edit', 'label' => 'Edit Cuti', 'group' => 'Leave Management'],
            ['name' => 'leave.delete', 'label' => 'Hapus Cuti', 'group' => 'Leave Management'],

            // Schedule Management
            ['name' => 'schedule.view', 'label' => 'Lihat Jadwal', 'group' => 'Schedule Management'],
            ['name' => 'schedule.create', 'label' => 'Tambah Jadwal', 'group' => 'Schedule Management'],
            ['name' => 'schedule.edit', 'label' => 'Edit Jadwal', 'group' => 'Schedule Management'],
            ['name' => 'schedule.delete', 'label' => 'Hapus Jadwal', 'group' => 'Schedule Management'],

            // Inventory (Alat & Material)
            ['name' => 'inventory.view', 'label' => 'Lihat Inventaris', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.create', 'label' => 'Tambah Inventaris', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.edit', 'label' => 'Edit Inventaris', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.delete', 'label' => 'Hapus Inventaris', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.manage', 'label' => 'Kelola Inventaris', 'group' => 'Inventory (Alat & Material)'],
            ['name' => 'inventory.pickup', 'label' => 'Pengambilan Inventaris', 'group' => 'Inventory (Alat & Material)'],

            // ATK Store
            ['name' => 'atk.view', 'label' => 'Lihat Toko ATK', 'group' => 'ATK Store'],
            ['name' => 'atk.pos', 'label' => 'Kasir ATK', 'group' => 'ATK Store'],
            ['name' => 'atk.manage', 'label' => 'Kelola Toko ATK', 'group' => 'ATK Store'],
            ['name' => 'atk.report', 'label' => 'Laporan ATK', 'group' => 'ATK Store'],

            // Car Wash
            ['name' => 'wash.view', 'label' => 'Lihat Cuci Kendaraan', 'group' => 'Car Wash'],
            ['name' => 'wash.pos', 'label' => 'Kasir Cuci Kendaraan', 'group' => 'Car Wash'],
            ['name' => 'wash.manage', 'label' => 'Kelola Cuci Kendaraan', 'group' => 'Car Wash'],
            ['name' => 'wash.report', 'label' => 'Laporan Cuci Kendaraan', 'group' => 'Car Wash'],

            // User Management
            ['name' => 'user.view', 'label' => 'Lihat Pengguna', 'group' => 'User Management'],
            ['name' => 'user.create', 'label' => 'Tambah Pengguna', 'group' => 'User Management'],
            ['name' => 'user.edit', 'label' => 'Edit Pengguna', 'group' => 'User Management'],
            ['name' => 'user.delete', 'label' => 'Hapus Pengguna', 'group' => 'User Management'],

            // Role Management
            ['name' => 'role.view', 'label' => 'Lihat Peran', 'group' => 'Role Management'],
            ['name' => 'role.create', 'label' => 'Tambah Peran', 'group' => 'Role Management'],
            ['name' => 'role.edit', 'label' => 'Edit Peran', 'group' => 'Role Management'],
            ['name' => 'role.delete', 'label' => 'Hapus Peran', 'group' => 'Role Management'],

            // Settings
            ['name' => 'setting.view', 'label' => 'Lihat Pengaturan', 'group' => 'Settings'],
            ['name' => 'setting.edit', 'label' => 'Edit Pengaturan', 'group' => 'Settings'],

            // Coordinator Management
            ['name' => 'coordinator.view', 'label' => 'Lihat Koordinator', 'group' => 'Coordinator Management'],
            ['name' => 'coordinator.create', 'label' => 'Tambah Koordinator', 'group' => 'Coordinator Management'],
            ['name' => 'coordinator.edit', 'label' => 'Edit Koordinator', 'group' => 'Coordinator Management'],
            ['name' => 'coordinator.delete', 'label' => 'Hapus Koordinator', 'group' => 'Coordinator Management'],

            // Region Management
            ['name' => 'region.view', 'label' => 'Lihat Wilayah', 'group' => 'Region Management'],
            ['name' => 'region.create', 'label' => 'Tambah Wilayah', 'group' => 'Region Management'],
            ['name' => 'region.edit', 'label' => 'Edit Wilayah', 'group' => 'Region Management'],
            ['name' => 'region.delete', 'label' => 'Hapus Wilayah', 'group' => 'Region Management'],

            // Package Management
            ['name' => 'package.view', 'label' => 'Lihat Paket', 'group' => 'Package Management'],
            ['name' => 'package.create', 'label' => 'Tambah Paket', 'group' => 'Package Management'],
            ['name' => 'package.edit', 'label' => 'Edit Paket', 'group' => 'Package Management'],
            ['name' => 'package.delete', 'label' => 'Hapus Paket', 'group' => 'Package Management'],

            // WhatsApp
            ['name' => 'whatsapp.view', 'label' => 'Lihat WhatsApp', 'group' => 'WhatsApp'],
            ['name' => 'whatsapp.edit', 'label' => 'Edit WhatsApp', 'group' => 'WhatsApp'],

            // Telegram
            ['name' => 'telegram.view', 'label' => 'Lihat Telegram', 'group' => 'Telegram'],
            ['name' => 'telegram.edit', 'label' => 'Edit Telegram', 'group' => 'Telegram'],

            // Notification
            ['name' => 'notification.view', 'label' => 'Lihat Notifikasi', 'group' => 'Notification'],
            ['name' => 'notification.manage', 'label' => 'Kelola Notifikasi', 'group' => 'Notification'],

            // Profile
            ['name' => 'profile.view', 'label' => 'Lihat Profil', 'group' => 'Profile'],
            ['name' => 'profile.update', 'label' => 'Update Profil', 'group' => 'Profile'],

            // Modem Data
            ['name' => 'modem-data.view', 'label' => 'Lihat Data Modem', 'group' => 'Utilities'],
            ['name' => 'modem-data.create', 'label' => 'Tambah Data Modem', 'group' => 'Utilities'],

            // Voucher
            ['name' => 'voucher.view', 'label' => 'Lihat Voucher', 'group' => 'Utilities'],
            ['name' => 'voucher.create', 'label' => 'Tambah Voucher', 'group' => 'Utilities'],
            ['name' => 'voucher.edit', 'label' => 'Edit Voucher', 'group' => 'Utilities'],
            ['name' => 'voucher.delete', 'label' => 'Hapus Voucher', 'group' => 'Utilities'],

            // VPN
            ['name' => 'vpn.view', 'label' => 'Lihat VPN', 'group' => 'Utilities'],
            ['name' => 'vpn.create', 'label' => 'Tambah VPN', 'group' => 'Utilities'],
            ['name' => 'vpn.edit', 'label' => 'Edit VPN', 'group' => 'Utilities'],
            ['name' => 'vpn.delete', 'label' => 'Hapus VPN', 'group' => 'Utilities'],

            // GenieACS
            ['name' => 'genieacs.view', 'label' => 'Lihat GenieACS', 'group' => 'Utilities'],
            ['name' => 'genieacs.edit', 'label' => 'Edit GenieACS', 'group' => 'Utilities'],

            // Employee Management
            ['name' => 'employee.view', 'label' => 'Lihat Karyawan', 'group' => 'Employee Management'],
            ['name' => 'employee.create', 'label' => 'Tambah Karyawan', 'group' => 'Employee Management'],
            ['name' => 'employee.edit', 'label' => 'Edit Karyawan', 'group' => 'Employee Management'],
            ['name' => 'employee.delete', 'label' => 'Hapus Karyawan', 'group' => 'Employee Management'],

            // Accounting
            ['name' => 'accounting.view', 'label' => 'Lihat Akuntansi', 'group' => 'Accounting'],
            ['name' => 'accounting.manage', 'label' => 'Kelola Akuntansi', 'group' => 'Accounting'],
        ];

        // Step 1: Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // Step 2: Get existing data before truncating
        $oldPermissions = DB::table('permissions')->get()->keyBy('id');
        $oldRolePerms = DB::table('permission_role')->get();

        // Step 3: Truncate both tables
        DB::table('permission_role')->truncate();
        DB::table('permissions')->truncate();

        // Step 4: Insert new permissions and build name -> id map
        $nameToNewId = [];
        foreach ($correctPermissions as $perm) {
            $newId = DB::table('permissions')->insertGetId($perm);
            $nameToNewId[$perm['name']] = $newId;
        }

        // Step 5: Re-attach old role permissions using name map
        foreach ($oldRolePerms as $rp) {
            if (isset($oldPermissions[$rp->permission_id])) {
                $oldName = $oldPermissions[$rp->permission_id]->name;
                if (isset($nameToNewId[$oldName])) {
                    DB::table('permission_role')->insert([
                        'role_id' => $rp->role_id,
                        'permission_id' => $nameToNewId[$oldName],
                        'created_at' => $rp->created_at,
                        'updated_at' => $rp->updated_at,
                    ]);
                }
            }
        }

        // Step 6: Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        // No rollback
    }
};
