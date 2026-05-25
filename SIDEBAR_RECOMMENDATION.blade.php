{{-- Sidebar Baru M-Store - Modern & Ergonomic --}}
<nav class="sidebar" id="sidebar-wrapper">
    <div class="sidebar-content">
        {{-- Logo / Header Sidebar --}}
        <div class="sidebar-logo p-4 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-3 p-2">
                    <i class="fa-solid fa-store fa-xl"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-primary">M-Store</h6>
                    <small class="text-muted">Management System</small>
                </div>
            </div>
        </div>

        <div class="sidebar-menu p-3">
            {{-- 1. DASBOR --}}
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-gauge-high me-2"></i>{{ __('Dasbor') }}
                </div>
                <a href="{{ route('dashboard') }}" class="sidebar-item {{ $routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i> {{ __('Dasbor Utama') }}
                </a>
                @if(Auth::user()->hasAnyRole(['admin', 'manager hrd']))
                <a href="{{ route('admin.dashboard') }}" class="sidebar-item {{ $routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i> {{ __('Dasbor Admin & HRD') }}
                </a>
                @endif
            </div>

            {{-- 2. MANAJEMEN HRD --}}
            @php
                $hrdActive = $routeIs('attendance.*') || $routeIs('employees.*') || $routeIs('schedules.*') || $routeIs('leave-requests.*') || $routeIs('technicians.kasbon.*');
            @endphp
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-users-gear me-2"></i>{{ __('Manajemen HRD') }}
                </div>
                
                <a href="{{ route('attendance.create') }}" class="sidebar-item {{ $routeIs('attendance.create') ? 'active' : '' }}">
                    <i class="fa-solid fa-fingerprint"></i> {{ __('Absen Mandiri') }}
                </a>
                
                <a href="{{ route('attendance.kiosk') }}" class="sidebar-item {{ $routeIs('attendance.kiosk') ? 'active' : '' }}">
                    <i class="fa-solid fa-id-card-clip"></i> {{ __('Kiosk Scan') }}
                </a>

                <a class="sidebar-item {{ $hrdActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#hrdSubmenu" role="button" aria-expanded="{{ $hrdActive ? 'true' : 'false' }}">
                    <i class="fa-solid fa-ellipsis-vertical"></i> {{ __('Lainnya') }}
                    <i class="fa-solid fa-chevron-down ms-auto"></i>
                </a>
                <div class="collapse {{ $hrdActive ? 'show' : '' }}" id="hrdSubmenu">
                    <div class="ps-4 mt-1">
                        @if(Auth::user()->hasPermission('employee.view'))
                        <a href="{{ route('employees.index') }}" class="sidebar-item {{ $routeIs('employees.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i> {{ __('Data Karyawan') }}
                        </a>
                        @endif
                        @if(Auth::user()->hasPermission('attendance.report'))
                        <a href="{{ route('attendance.index') }}" class="sidebar-item {{ $routeIs('attendance.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-list"></i> {{ __('Rekap Absensi') }}
                        </a>
                        @endif
                        @if(Auth::user()->hasPermission('schedule.view'))
                        <a href="{{ route('schedules.index') }}" class="sidebar-item {{ $routeIs('schedules.*') ? 'active' : '' }}">
                            <i class="fa-regular fa-calendar-check"></i> {{ __('Jadwal Karyawan') }}
                        </a>
                        @endif
                        @if(Auth::user()->hasPermission('leave.view'))
                        <a href="{{ Auth::user()->hasPermission('leave.manage') ? route('admin.leave-requests') : route('employee.leave-requests') }}" class="sidebar-item {{ $routeIs('*.leave-requests') ? 'active' : '' }}">
                            <i class="fa-regular fa-file-lines"></i> {{ __('Pengajuan Cuti/Izin') }}
                        </a>
                        @endif
                        <a href="{{ route('attendance.payslip') }}" class="sidebar-item {{ $routeIs('attendance.payslip') ? 'active' : '' }}">
                            <i class="fa-solid fa-file-invoice-dollar"></i> {{ __('Slip Gaji') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- 3. NETWORK TOOLS --}}
            @if(Auth::user()->hasAnyRole(['noc', 'technician', 'admin']))
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-network-wired me-2"></i>{{ __('Network Tools') }}
                </div>
                <a href="{{ route('network-monitor') }}" class="sidebar-item {{ $routeIs('network-monitor') ? 'active' : '' }}">
                    <i class="fa-solid fa-signal"></i> {{ __('Monitoring System') }}
                </a>
                <a href="{{ route('hotspot.index') }}" class="sidebar-item {{ $routeIs('hotspot.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wifi"></i> {{ __('Hotspot & Voucher') }}
                </a>
                <a href="{{ route('invoices.index') }}" class="sidebar-item {{ $routeIs('invoices.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i> {{ __('Tagihan & Invoice') }}
                </a>
            </div>
            @endif

            {{-- 4. POS KASIR --}}
            @if(Auth::user()->hasAnyRole(['kasir wash', 'kasir atk', 'admin']))
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-cash-register me-2"></i>{{ __('POS Kasir') }}
                </div>
                @if(Auth::user()->hasAnyRole(['kasir wash', 'admin']))
                <a href="{{ route('wash.pos') }}" class="sidebar-item {{ $routeIs('wash.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-car-side"></i> {{ __('Transaksi Wash') }}
                </a>
                @endif
                @if(Auth::user()->hasAnyRole(['kasir atk', 'admin']))
                <a href="{{ route('atk.pos') }}" class="sidebar-item {{ $routeIs('atk.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i> {{ __('Transaksi ATK') }}
                </a>
                @endif
            </div>
            @endif

            {{-- 5. KEUANGAN --}}
            @if(Auth::user()->hasAnyRole(['owner', 'direktur', 'finance', 'admin']))
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-wallet me-2"></i>{{ __('Keuangan') }}
                </div>
                @if(Auth::user()->hasAnyRole(['admin', 'finance', 'manager hrd']))
                <a href="{{ route('technicians.kasbon.index') }}" class="sidebar-item {{ $routeIs('technicians.kasbon.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-coins"></i> {{ __('Rincian Kasbon') }}
                </a>
                @endif
                <a href="{{ route('attendance.recap-to-finance') }}" class="sidebar-item">
                    <i class="fa-solid fa-file-export"></i> {{ __('Rekap ke Keuangan') }}
                </a>
                <a href="{{ route('transactions.index') }}" class="sidebar-item {{ $routeIs('transactions.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-transfer"></i> {{ __('Pengeluaran & Pemasukan') }}
                </a>
            </div>
            @endif

            {{-- 6. ASET & INVENTARIS --}}
            @if(Auth::user()->hasPermission('inventory.view'))
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-box-open me-2"></i>{{ __('Aset & Inventaris') }}
                </div>
                <a href="{{ route('inventory.index') }}" class="sidebar-item {{ $routeIs('inventory.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-toolbox"></i> {{ __('Inventaris Peralatan') }}
                </a>
                <a href="{{ route('inventory.my_assets') }}" class="sidebar-item {{ $routeIs('inventory.my_assets') ? 'active' : '' }}">
                    <i class="fa-solid fa-box-open"></i> {{ __('Aset Saya') }}
                </a>
                <a href="{{ route('inventory.pickup') }}" class="sidebar-item {{ $routeIs('inventory.pickup*') ? 'active' : '' }}">
                    <i class="fa-solid fa-hand-holding"></i> {{ __('Pengambilan Barang') }}
                </a>
            </div>
            @endif

            {{-- 7. KONFIGURASI SISTEM --}}
            @if(Auth::user()->hasPermission('setting.view') || Auth::user()->hasPermission('user.view'))
            <div class="mb-4">
                <div class="sidebar-label text-uppercase text-muted small fw-semibold mb-2 px-2">
                    <i class="fa-solid fa-sliders me-2"></i>{{ __('Konfigurasi Sistem') }}
                </div>
                @if(Auth::user()->hasPermission('setting.view'))
                <a href="{{ route('settings.index') }}" class="sidebar-item {{ $routeIs('settings.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i> {{ __('Pengaturan Umum') }}
                </a>
                @endif
                @if(Auth::user()->hasPermission('user.view'))
                <a href="{{ route('users.index') }}" class="sidebar-item {{ $routeIs('users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i> {{ __('Manajemen User') }}
                </a>
                @endif
                @if(Auth::user()->hasPermission('role.view'))
                <a href="{{ route('roles.index') }}" class="sidebar-item {{ $routeIs('roles.*') ? 'active' : '' }}">
                    <i class="fa-regular fa-id-card"></i> {{ __('Manajemen Peran') }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</nav>
