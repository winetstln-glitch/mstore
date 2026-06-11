<?php

namespace App\Support\Sidebar;

use App\Models\Role;

class SidebarMenu
{
    public static function tree(): array
    {
        return [
            [
                'type' => 'section',
                'id' => 'dashboard-center',
                'label' => 'Dashboard Center',
                'items' => [
                    self::link('dashboard', 'Dashboard Utama', 'dashboard', permissions: ['dashboard.view']),
                    self::link('dashboard-noc', 'Dashboard NOC', 'noc.dashboard', permissions: ['noc.dashboard.view']),
                    self::link('dashboard-finance', 'Dashboard Finance', 'finance.index', permissions: ['finance.view']),
                    self::link('dashboard-hrd', 'Dashboard HRD', 'admin.dashboard', permissions: ['dashboard.view']),
                    self::link('audit-trail', 'Audit Trail', 'admin.audit-trail', permissions: ['dashboard.view']),
                    self::group('reporting-center', 'Reporting Center', [
                        self::link('report-noc', 'NOC Report', 'reports.noc', permissions: ['report.noc.export']),
                        self::link('report-whatsapp', 'WhatsApp Report', 'reports.whatsapp', permissions: ['report.whatsapp.export']),
                        self::link('report-sla', 'SLA Report', 'reports.sla', permissions: ['report.sla.export']),
                        self::link('report-wedding', 'Wedding Report', 'reports.wedding', permissions: ['report.wedding.export']),
                        self::link('report-cctv', 'CCTV Report', 'reports.cctv', permissions: ['report.cctv.export']),
                    ], permissions: ['report.noc.export', 'report.whatsapp.export', 'report.sla.export', 'report.wedding.export', 'report.cctv.export']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'customer-center',
                'label' => 'Customer Center',
                'items' => [
                    self::link('customers', 'Data Pelanggan', 'customers.index', permissions: ['customer.view']),
                    self::link('installations', 'Instalasi Baru', 'installations.index', permissions: ['installation.view']),
                    self::link('packages', 'Paket Internet', 'packages.index', permissions: ['package.view']),
                    self::link('pppoe-active', 'PPPoE Aktif', 'pppoe.index', permissions: ['pppoe.view']),
                    self::link('hotspot-active', 'Hotspot Aktif', 'hotspot.online', permissions: ['hotspot.view', 'router.view']),
                    self::link('voucher-hotspot', 'Voucher Hotspot', 'vouchers.index', permissions: ['voucher.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'network-operations',
                'label' => 'Network Operations',
                'items' => [
                    self::group('net-monitoring', 'Monitoring', [
                        self::link('net-monitor-jaringan', 'Monitor Jaringan', 'genieacs.index', permissions: ['genieacs.view']),
                        self::link('net-genieacs-servers', 'Server GenieACS', 'genieacs.servers.index', permissions: ['genieacs_server.view']),
                        self::link('net-olt-monitoring', 'OLT Monitoring', 'noc.operational.olt_monitoring', permissions: ['noc.olt_monitoring.view']),
                        self::link('net-map', 'Monitor Peta Jaringan', 'map.connections.index', permissions: ['map.view']),
                        self::link('net-map-analysis', 'Analisis Jaringan', 'network.analyzer', permissions: ['router.view']),
                        self::link('net-kalkulator-pon', 'Kalkulator PON', 'calculator.pon', permissions: ['calculator.view']),
                    ], permissions: ['noc.operational.view', 'genieacs.view', 'map.view']),
                    self::group('net-operational', 'Operasional', [
                        self::link('net-outage', 'Area Outage', 'noc.operational.area_outage', permissions: ['noc.operational.view']),
                        self::link('net-incident', 'Network Incident', 'noc.operational.network_incident', permissions: ['noc.operational.view']),
                        self::link('net-diagnostic', 'Network Diagnostic', 'noc.operational.network_diagnostic', permissions: ['noc.operational.view']),
                        self::link('net-diagnostic-logs', 'Diagnostic Logs', 'noc.operational.diagnostic_logs', permissions: ['noc.diagnostic_logs.view']),
                    ], permissions: ['noc.operational.view']),
                    self::group('net-infra', 'Infrastruktur', [
                        self::link('infra-olt', 'OLT', 'olt.index', permissions: ['olt.view']),
                        self::link('infra-odc', 'ODC', 'odcs.index', permissions: ['odc.view']),
                        self::link('infra-odp', 'ODP', 'odps.index', permissions: ['odp.view']),
                        self::link('infra-closure', 'Closure', 'closures.index', permissions: ['closure.view']),
                        self::link('infra-htb', 'HTB', 'htbs.index', permissions: ['htb.view']),
                    ]),
                    self::group('net-access', 'Akses', [
                        self::link('access-router', 'Router', 'routers.index', permissions: ['router.view']),
                        self::link('access-vpn-bridge', 'VPN Bridge', 'vpn.servers.index', permissions: ['router.view']),
                        self::link('access-vpn-guide', 'VPN Guide', 'vpn.guide', permissions: ['router.view']),
                    ], permissions: ['router.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'ticketing',
                'label' => 'Ticketing',
                'items' => [
                    self::link('tickets', 'Tiket Gangguan', 'tickets.index', permissions: ['ticket.view']),
                    self::link('modem-data', 'Pendataan Modem', 'modem-data.index', permissions: ['modem-data.view']),
                    self::link('sla-monitoring', 'SLA Monitoring', 'sla.monitoring', permissions: ['sla.monitoring.view']),
                    self::link('sla-escalation', 'Escalation Queue', 'sla.escalation-queue', permissions: ['sla.escalation.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'whatsapp-ai',
                'label' => 'WhatsApp & AI',
                'items' => [
                    self::group('wa', 'WhatsApp Gateway', [
                        self::link('wa-dashboard', 'Dashboard WA', 'whatsapp.index', permissions: ['chat.view']),
                        self::link('wa-bot-builder', 'Bot Builder', 'whatsapp.builder.index', permissions: ['chat.manage']),
                        self::link('wa-logs', 'Pesan Terkirim', 'whatsapp.logs', permissions: ['chat.view']),
                        self::link('wa-analytics', 'Analytics', 'whatsapp.analytics', permissions: ['whatsapp.analytics.view']),
                    ]),
                    self::group('ai', 'AI', [
                        self::link('ai-center', 'AI Center', 'ai.index', permissions: ['ai.view']),
                        self::link('ai-kb', 'AI Knowledge Base', 'whatsapp.kb.index', permissions: ['whatsapp.kb.manage']),
                    ]),
                    self::link('internal-chat', 'Messenger Internal', 'chat.index', permissions: ['chat.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'finance-center',
                'label' => 'Finance Center',
                'items' => [
                    self::group('finance-billing', 'Billing', [
                        self::link('finance-dashboard', 'Dasbor Keuangan', 'finance.index', permissions: ['finance.view']),
                        self::link('finance-profit-loss', 'Laporan Laba Rugi', 'finance.profit_loss', permissions: ['finance.view']),
                        self::link('finance-material', 'Laporan Material', 'finance.material_report', permissions: ['finance.view']),
                        self::link('finance-manager', 'Manager Report', 'finance.manager_report', permissions: ['finance.view']),
                    ], permissions: ['finance.view']),
                    self::group('finance-accounting', 'Accounting', [
                        self::link('trial-balance', 'Trial Balance', 'accounting.trial_balance', permissions: ['accounting.view']),
                        self::link('income-statement', 'Income Statement', 'accounting.income_statement', permissions: ['accounting.view']),
                        self::link('balance-sheet', 'Balance Sheet', 'accounting.balance_sheet', permissions: ['accounting.view']),
                        self::link('ledger', 'Ledger', 'accounting.ledger', permissions: ['accounting.view']),
                        self::link('cash-flow', 'Cash Flow', 'accounting.cash_flow', permissions: ['accounting.view']),
                    ], permissions: ['finance.view', 'accounting.view']),
                    self::group('finance-investor', 'Investor', [
                        self::link('finance-investor-report', 'Investor Report', 'finance.investor_report', permissions: ['finance.view', 'investor.view']),
                        self::link('investors', 'Data Investor', 'investors.index', permissions: ['investor.view']),
                    ], permissions: ['investor.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'hr-asset',
                'label' => 'HR & Asset',
                'items' => [
                    self::group('hr', 'HR', [
                        self::link('employees', 'Karyawan', 'employees.index', permissions: ['employee.view']),
                        self::link('attendance', 'Absensi', 'attendance.index', permissions: ['attendance.view']),
                        self::link('attendance-settings', 'Setting Absensi', 'settings.attendance.index', permissions: ['setting.view']),
                        self::link('schedule', 'Jadwal', 'schedules.index', permissions: ['schedule.view']),
                        self::link(
                            'leave-my',
                            'Pengajuan Cuti/Izin Saya',
                            'employee.leave-requests',
                            permissions: ['leave.view']
                        ),
                        self::link('leave-manage', 'Kelola Cuti/Izin', 'admin.leave-requests', permissions: ['leave.manage']),
                        self::link('kasbon', 'Kasbon', 'technicians.kasbon.index', roles: [Role::ADMIN, Role::FINANCE, Role::HRD_MANAGER]),
                        self::link('payslip', 'Slip Gaji', 'attendance.payslip', permissions: ['attendance.view']),
                    ], permissions: ['employee.view', 'attendance.view', 'setting.view', 'schedule.view', 'leave.view', 'leave.manage']),
                    self::group('asset', 'Asset', [
                        self::link('inventory', 'Inventory', 'inventory.index', permissions: ['inventory.view']),
                        self::link('my-assets', 'Aset Saya', 'inventory.my_assets', permissions: ['inventory.view']),
                        self::link('pickup', 'Pengambilan Barang', 'inventory.pickup', permissions: ['inventory.pickup', 'inventory.manage']),
                    ], permissions: ['inventory.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'business-units',
                'label' => 'Business Units',
                'items' => [
                    self::group('bu-atk', 'ATK', [
                        self::link('atk-pos', 'POS ATK', 'atk.pos', permissions: ['atk.pos']),
                        self::link('atk-manage', 'Manajemen ATK', 'atk.products.index', permissions: ['atk.manage']),
                        self::link('atk-report', 'Laporan ATK', 'atk.reports.index', permissions: ['atk.report']),
                    ], permissions: ['atk.pos', 'atk.manage', 'atk.report']),
                    self::group('bu-wash', 'GT Wash', [
                        self::link('wash-dashboard', 'Dashboard', 'wash.dashboard', permissions: ['wash.view']),
                        self::link('wash-pos', 'POS Wash', 'wash.pos', permissions: ['wash.pos']),
                        self::link('wash-transactions', 'Transaksi', 'wash.transactions.index', permissions: ['wash.report']),
                        self::link('wash-expenses', 'Pengeluaran', 'wash.expenses.index', permissions: ['wash.report']),
                        self::link('wash-stock', 'Stok Wash', 'wash.stock.index', permissions: ['wash.view']),
                        self::link('wash-manage', 'Manajemen Layanan', 'wash.services.index', permissions: ['wash.manage']),
                        self::link('wash-members', 'Member', 'wash.members.index', permissions: ['wash.member.view']),
                        self::link('wash-loyalty', 'Loyalty Program', 'wash.loyalty.index', permissions: ['wash.loyalty.view']),
                        self::link('wash-reward-vouchers', 'Reward Voucher', 'wash.loyalty.vouchers', permissions: ['wash.reward.view']),
                        self::link('wash-membership-levels', 'Membership Level', 'wash.members.levels', permissions: ['wash.member.view']),
                        self::link('wash-reward-history', 'Riwayat Reward', 'wash.loyalty.redemptions', permissions: ['wash.reward.view']),
                        self::link('wash-report', 'Laporan Wash', 'wash.reports.index', permissions: ['wash.report']),
                    ], permissions: ['wash.view', 'wash.pos', 'wash.manage', 'wash.report', 'wash.member.view', 'wash.loyalty.view', 'wash.reward.view']),
                    self::group('bu-wedding', 'Wedding & Event', [
                        self::link('wedding-dashboard', 'Dashboard', 'wedding.dashboard', permissions: ['wedding.view']),
                        self::link('wedding-packages', 'Paket', 'wedding.packages.index', permissions: ['wedding.view']),
                        self::link('wedding-gallery', 'Galeri Landing', 'wedding.gallery.index', permissions: ['wedding.view']),
                        self::link('wedding-bookings', 'Booking', 'wedding.bookings.index', permissions: ['wedding.booking']),
                        self::link('wedding-schedule', 'Jadwal Acara', 'wedding.schedule.index', permissions: ['wedding.view']),
                        self::link('wedding-payments', 'Pembayaran', 'wedding.payments.index', permissions: ['wedding.payment']),
                        self::link('wedding-reports', 'Laporan', 'reports.wedding', permissions: ['report.wedding.export']),
                    ], permissions: ['wedding.view']),
                    self::group('bu-cctv', 'CCTV Installation', [
                        self::link('cctv-dashboard', 'Dashboard', 'cctv.dashboard', permissions: ['cctv.view']),
                        self::link('cctv-packages', 'Paket CCTV', 'cctv.packages.index', permissions: ['cctv.view']),
                        self::link('cctv-bookings', 'Booking Instalasi', 'cctv.bookings.index', permissions: ['cctv.booking']),
                        self::link('cctv-schedule', 'Jadwal Teknisi', 'cctv.schedule.index', permissions: ['cctv.view']),
                        self::link('cctv-payments', 'Pembayaran', 'cctv.payments.index', permissions: ['cctv.payment']),
                        self::link('cctv-reports', 'Laporan', 'reports.cctv', permissions: ['report.cctv.export']),
                    ], permissions: ['cctv.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'system-administration',
                'label' => 'System Administration',
                'items' => [
                    self::group('sys-settings', 'General Settings', [
                        self::link('settings', 'Pengaturan Umum', 'settings.index', permissions: ['setting.view']),
                        self::link('regions', 'Wilayah', 'regions.index', permissions: ['region.view']),
                        self::link('coordinators', 'Pengurus', 'coordinators.index', permissions: ['coordinator.view']),
                    ], permissions: ['setting.view', 'region.view', 'coordinator.view']),
                    self::group('sys-payment', 'Payment Gateway', [
                        self::link('payment-dashboard', 'Dashboard Payment', 'payment.dashboard', permissions: ['payment.view']),
                        self::link('payment-duitku', 'Duitku', 'payment.gateway', ['gateway' => 'duitku'], permissions: ['payment.view']),
                        self::link('payment-midtrans', 'Midtrans', 'payment.gateway', ['gateway' => 'midtrans'], permissions: ['payment.view']),
                    ], permissions: ['payment.view']),
                    self::group('sys-users', 'Users & Roles', [
                        self::link('users', 'Users', 'users.index', permissions: ['user.view']),
                        self::link('roles', 'Roles', 'roles.index', permissions: ['role.view']),
                    ], permissions: ['user.view', 'role.view']),
                    self::group('sys-integrations', 'Integrations', [
                        self::link('wa-gateway', 'WhatsApp Gateway', 'whatsapp.index', permissions: ['chat.view']),
                        self::link('telegram', 'Telegram Bot', 'telegram.index', permissions: ['telegram.view']),
                        self::link('api-keys', 'API Keys', 'apikeys.index', permissions: ['apikey.view']),
                    ], permissions: ['chat.view', 'telegram.view', 'apikey.view']),
                    self::group('sys-maintenance', 'Maintenance & Security', [
                        self::link('security-monitor', 'Security Monitoring', 'security.monitoring', permissions: ['security.monitoring.view']),
                    ], permissions: ['security.monitoring.view']),
                ],
            ],
            [
                'type' => 'section',
                'id' => 'customer-portal',
                'label' => 'Portal Pelanggan',
                'roles' => [Role::CUSTOMER],
                'items' => [
                    self::link('portal-dashboard', 'Dashboard', 'client.dashboard', roles: [Role::CUSTOMER]),
                    self::link('portal-invoices', 'Tagihan', 'client.invoices.index', roles: [Role::CUSTOMER]),
                    self::link('portal-credentials', 'Kredensial', 'client.credentials.show', roles: [Role::CUSTOMER]),
                    self::link('portal-profile', 'Profil', 'profile.edit', roles: [Role::CUSTOMER]),
                    self::link('portal-network', 'Status Jaringan', 'client.connection', roles: [Role::CUSTOMER]),
                ],
            ],
        ];
    }

    private static function link(
        string $id,
        string $label,
        string $route,
        array $params = [],
        array $permissions = [],
        array $roles = [],
        array $routePatterns = [],
    ): array {
        return [
            'type' => 'link',
            'id' => $id,
            'label' => $label,
            'route' => $route,
            'route_params' => $params,
            'icon' => self::inferIcon($id, $label, $route, 'link'),
            'route_patterns' => $routePatterns,
            'permissions' => $permissions,
            'roles' => $roles,
        ];
    }

    private static function group(string $id, string $label, array $children, array $permissions = [], array $roles = []): array
    {
        return [
            'type' => 'group',
            'id' => $id,
            'label' => $label,
            'icon' => self::inferIcon($id, $label, null, 'group'),
            'children' => $children,
            'permissions' => $permissions,
            'roles' => $roles,
        ];
    }

    private static function inferIcon(string $id, string $label, ?string $route = null, string $type = 'link'): string
    {
        $haystack = strtolower(trim($id.' '.$label.' '.($route ?? '')));

        $map = [
            'dashboard' => 'fa-solid fa-gauge-high',
            'audit' => 'fa-solid fa-clipboard-list',
            'report' => 'fa-solid fa-chart-column',
            'customer' => 'fa-solid fa-users',
            'pelanggan' => 'fa-solid fa-users',
            'installation' => 'fa-solid fa-screwdriver-wrench',
            'instalasi' => 'fa-solid fa-screwdriver-wrench',
            'package' => 'fa-solid fa-box-open',
            'paket' => 'fa-solid fa-box-open',
            'pppoe' => 'fa-solid fa-network-wired',
            'hotspot' => 'fa-solid fa-wifi',
            'voucher' => 'fa-solid fa-ticket',
            'monitor' => 'fa-solid fa-desktop',
            'outage' => 'fa-solid fa-triangle-exclamation',
            'incident' => 'fa-solid fa-bolt',
            'diagnostic' => 'fa-solid fa-stethoscope',
            'infra' => 'fa-solid fa-diagram-project',
            'olt' => 'fa-solid fa-server',
            'odc' => 'fa-solid fa-vector-square',
            'odp' => 'fa-solid fa-map-location-dot',
            'closure' => 'fa-solid fa-circle-nodes',
            'router' => 'fa-solid fa-router',
            'vpn' => 'fa-solid fa-shield-halved',
            'ticket' => 'fa-solid fa-headset',
            'sla' => 'fa-solid fa-stopwatch',
            'whatsapp' => 'fa-brands fa-whatsapp',
            'chat' => 'fa-solid fa-comments',
            'ai' => 'fa-solid fa-robot',
            'finance' => 'fa-solid fa-wallet',
            'accounting' => 'fa-solid fa-calculator',
            'investor' => 'fa-solid fa-hand-holding-dollar',
            'employee' => 'fa-solid fa-id-card',
            'karyawan' => 'fa-solid fa-id-card',
            'attendance' => 'fa-solid fa-fingerprint',
            'absensi' => 'fa-solid fa-fingerprint',
            'schedule' => 'fa-solid fa-calendar-days',
            'jadwal' => 'fa-solid fa-calendar-days',
            'leave' => 'fa-solid fa-plane-departure',
            'cuti' => 'fa-solid fa-plane-departure',
            'inventory' => 'fa-solid fa-boxes-stacked',
            'asset' => 'fa-solid fa-toolbox',
            'atk' => 'fa-solid fa-pen-ruler',
            'wash' => 'fa-solid fa-car-side',
            'member' => 'fa-solid fa-id-badge',
            'loyalty' => 'fa-solid fa-gift',
            'reward' => 'fa-solid fa-award',
            'wedding' => 'fa-solid fa-ring',
            'gallery' => 'fa-solid fa-images',
            'galeri' => 'fa-solid fa-images',
            'booking' => 'fa-solid fa-calendar-check',
            'payment' => 'fa-solid fa-money-check-dollar',
            'pembayaran' => 'fa-solid fa-money-check-dollar',
            'cctv' => 'fa-solid fa-video',
            'survey' => 'fa-solid fa-clipboard-check',
            'setting' => 'fa-solid fa-gear',
            'settings' => 'fa-solid fa-gear',
            'region' => 'fa-solid fa-map',
            'wilayah' => 'fa-solid fa-map',
            'coordinator' => 'fa-solid fa-user-tie',
            'pengurus' => 'fa-solid fa-user-tie',
            'user' => 'fa-solid fa-user',
            'role' => 'fa-solid fa-user-shield',
            'telegram' => 'fa-brands fa-telegram',
            'api' => 'fa-solid fa-key',
            'security' => 'fa-solid fa-lock',
            'portal' => 'fa-solid fa-globe',
            'invoice' => 'fa-solid fa-file-invoice-dollar',
            'profil' => 'fa-solid fa-address-card',
            'profile' => 'fa-solid fa-address-card',
            'network' => 'fa-solid fa-signal',
        ];

        foreach ($map as $keyword => $icon) {
            if (str_contains($haystack, $keyword)) {
                return $icon;
            }
        }

        return $type === 'group' ? 'fa-solid fa-folder-tree' : 'fa-regular fa-circle';
    }
}
