<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'MStore'))</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Custom Dashboard CSS -->
    <link href="{{ asset('css/dashboard-custom.css') }}" rel="stylesheet">

    @stack('styles')

    <script>
        // Check local storage for theme
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        }
    </script>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading py-3 position-relative">
            <div class="sidebar-brand-icon">
                <img src="{{ asset('img/logo.png') }}" alt="MSTORE.NET" class="img-fluid">
            </div>
            <!-- Close Button for Mobile -->
            <button class="btn btn-link text-secondary position-absolute top-0 end-0 me-2 d-lg-none" id="sidebarClose" style="z-index: 1051;">
                <i class="fa-solid fa-times fa-lg"></i>
            </button>
        </div>
        <div class="list-group list-group-flush pb-2">
            
            {{-- User Panel (Simplified) --}}
           
            <div class="sidebar-header mt-2">{{ __('Main Menu') }}</div>

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa fa-tachometer-alt"></i> {{ __('Dashboard') }}
            </a>

            {{-- Pelanggan & Layanan Group --}}
            <div class="sidebar-header mt-2">{{ __('Pelanggan & Layanan') }}</div>

            @if(Auth::user()->hasPermission('customer.view'))
            <a href="{{ route('customers.index') }}" class="sidebar-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fa fa-users"></i> {{ __('Data Pelanggan') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('hotspot.view') || Auth::user()->hasPermission('router.view') || Auth::user()->hasPermission('pppoe.view'))
            <a class="sidebar-item {{ (request()->routeIs('hotspot.index') || request()->routeIs('pppoe.index')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#servicesCollapse" role="button" aria-expanded="{{ (request()->routeIs('hotspot.index') || request()->routeIs('pppoe.index')) ? 'true' : 'false' }}" aria-controls="servicesCollapse">
                <i class="fa fa-wifi"></i> {{ __('Layanan Aktif') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('hotspot.index') || request()->routeIs('pppoe.index')) ? 'show' : '' }}" id="servicesCollapse">
                <div class="bg-light ps-2">
                    @if(Auth::user()->hasPermission('hotspot.view'))
                    <a href="{{ route('hotspot.index') }}" class="sidebar-item {{ request()->routeIs('hotspot.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-wifi"></i> {{ __('Hotspot Active') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('router.view') || Auth::user()->hasPermission('pppoe.view'))
                    <a href="{{ route('pppoe.index') }}" class="sidebar-item {{ request()->routeIs('pppoe.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-globe"></i> {{ __('PPPoE Active') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Jaringan Group --}}
            <div class="sidebar-header mt-2">{{ __('Jaringan') }}</div>

            @if(Auth::user()->hasPermission('map.view'))
            <a href="{{ route('map.index') }}" class="sidebar-item {{ request()->routeIs('map.*') ? 'active' : '' }}">
                <i class="fa fa-map-marked-alt"></i> {{ __('Peta Jaringan') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('genieacs.view'))
            <a href="{{ route('genieacs.index') }}" class="sidebar-item {{ request()->routeIs('genieacs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-network-wired"></i> {{ __('Network Monitor') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('router.view'))
            <a href="{{ route('routers.index') }}" class="sidebar-item {{ (request()->routeIs('routers.*') && !request()->routeIs('routers.sessions')) ? 'active' : '' }}">
                <i class="fa fa-server"></i> {{ __('Router / NAS') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('olt.view') || Auth::user()->hasPermission('odc.view') || Auth::user()->hasPermission('odp.view') || Auth::user()->hasPermission('htb.view') || Auth::user()->hasPermission('closure.view'))
            <a class="sidebar-item {{ (request()->routeIs('olts.*') || request()->routeIs('odcs.*') || request()->routeIs('odps.*') || request()->routeIs('htbs.*') || request()->routeIs('closures.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkInfraCollapse" role="button" aria-expanded="{{ (request()->routeIs('olts.*') || request()->routeIs('odcs.*') || request()->routeIs('odps.*') || request()->routeIs('htbs.*') || request()->routeIs('closures.*')) ? 'true' : 'false' }}" aria-controls="networkInfraCollapse">
                <i class="fa fa-sitemap"></i> {{ __('Infrastruktur') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('olts.*') || request()->routeIs('odcs.*') || request()->routeIs('odps.*') || request()->routeIs('htbs.*') || request()->routeIs('closures.*')) ? 'show' : '' }}" id="networkInfraCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('olt.view'))
                    <a href="{{ route('olt.index') }}" class="sidebar-item {{ request()->routeIs('olt.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-server"></i> {{ __('OLT') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('closure.view'))
                    <a href="{{ route('closures.index') }}" class="sidebar-item {{ request()->routeIs('closures.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-box-open"></i> {{ __('Closure') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('odc.view'))
                    <a href="{{ route('odcs.index') }}" class="sidebar-item {{ request()->routeIs('odcs.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-box-archive"></i> {{ __('ODC') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('odp.view'))
                    <a href="{{ route('odps.index') }}" class="sidebar-item {{ request()->routeIs('odps.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-box"></i> {{ __('ODP') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('htb.view'))
                    <a href="{{ route('htbs.index') }}" class="sidebar-item {{ request()->routeIs('htbs.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-network-wired"></i> {{ __('HTB') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if(Auth::user()->hasPermission('calculator.view'))
            <a href="{{ route('calculator.pon') }}" class="sidebar-item {{ request()->routeIs('calculator.*') ? 'active' : '' }}">
                <i class="fa-solid fa-calculator"></i> {{ __('Kalkulator PON') }}
            </a>
            @endif

            {{-- Keuangan Group --}}
            @if(Auth::user()->hasPermission('finance.view'))
            <div class="sidebar-header mt-2">{{ __('Keuangan') }}</div>
            <a href="{{ route('finance.index') }}" class="sidebar-item {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                <i class="fa fa-wallet"></i> {{ __('Dashboard Keuangan') }}
            </a>
            @endif

            {{-- Toko ATK Group --}}
            @if(Auth::user()->hasPermission('atk.view') || Auth::user()->hasPermission('atk.pos'))
            <div class="sidebar-header mt-2">{{ __('Toko ATK') }}</div>
            
            <a class="sidebar-item {{ (request()->routeIs('atk.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#atkCollapse" role="button" aria-expanded="{{ (request()->routeIs('atk.*')) ? 'true' : 'false' }}" aria-controls="atkCollapse">
                <i class="fa fa-store"></i> {{ __('Kasir & Produk') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('atk.*')) ? 'show' : '' }}" id="atkCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('atk.view'))
                    <a href="{{ route('atk.dashboard') }}" class="sidebar-item {{ request()->routeIs('atk.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i> {{ __('Dashboard Toko') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('atk.pos'))
                    <a href="{{ route('atk.pos') }}" class="sidebar-item {{ request()->routeIs('atk.pos') ? 'active' : '' }}">
                        <i class="fa-solid fa-cash-register"></i> {{ __('Kasir (POS)') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('atk.manage'))
                    <a href="{{ route('atk.products.index') }}" class="sidebar-item {{ request()->routeIs('atk.products.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-boxes-stacked"></i> {{ __('Produk & Stok') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('atk.report'))
                    <a href="{{ route('atk.transactions.index') }}" class="sidebar-item {{ request()->routeIs('atk.transactions.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-history"></i> {{ __('Riwayat Transaksi') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Car Wash Group --}}
            @if(Auth::user()->hasPermission('wash.view') || Auth::user()->hasPermission('wash.pos'))
            <div class="sidebar-header mt-2">{{ __('Cuci Kendaraan') }}</div>
            
            <a class="sidebar-item {{ (request()->routeIs('wash.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#washCollapse" role="button" aria-expanded="{{ (request()->routeIs('wash.*')) ? 'true' : 'false' }}" aria-controls="washCollapse">
                <i class="fa fa-car"></i> {{ __('Kasir & Layanan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('wash.*')) ? 'show' : '' }}" id="washCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('wash.view'))
                    <a href="{{ route('wash.index') }}" class="sidebar-item {{ request()->routeIs('wash.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i> {{ __('Dashboard Cuci') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('wash.pos'))
                    <a href="{{ route('wash.pos') }}" class="sidebar-item {{ request()->routeIs('wash.pos') ? 'active' : '' }}">
                        <i class="fa-solid fa-cash-register"></i> {{ __('Kasir (POS)') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('wash.manage'))
                    <a href="{{ route('wash.services.index') }}" class="sidebar-item {{ request()->routeIs('wash.services.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-tags"></i> {{ __('Layanan & Harga') }}
                    </a>
                    <a href="{{ route('wash.employees.index') }}" class="sidebar-item {{ request()->routeIs('wash.employees.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users"></i> {{ __('Karyawan Steam') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Operasional Group --}}
            <div class="sidebar-header mt-2">{{ __('Operasional') }}</div>

            @if(Auth::user()->hasPermission('ticket.view'))
            <a href="{{ route('tickets.index') }}" class="sidebar-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <i class="fa fa-ticket-alt"></i> {{ __('Tiket & Gangguan') }}
            </a>
            @endif

            <a class="sidebar-item {{ (request()->routeIs('inventory.*') || request()->routeIs('attendance.*') || request()->routeIs('schedules.*') || request()->routeIs('leave-requests.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#opsCollapse" role="button" aria-expanded="{{ (request()->routeIs('inventory.*') || request()->routeIs('attendance.*') || request()->routeIs('schedules.*') || request()->routeIs('leave-requests.*')) ? 'true' : 'false' }}" aria-controls="opsCollapse">
                <i class="fa fa-tools"></i> {{ __('Tools & SDM') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('inventory.*') || request()->routeIs('attendance.*') || request()->routeIs('schedules.*') || request()->routeIs('leave-requests.*')) ? 'show' : '' }}" id="opsCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('inventory.view'))
                    <a href="{{ route('inventory.index') }}" class="sidebar-item {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-toolbox"></i> {{ __('Inventory / Tools') }}
                    </a>
                    <a href="{{ route('inventory.my_assets') }}" class="sidebar-item {{ request()->routeIs('inventory.my_assets') ? 'active' : '' }}">
                        <i class="fa-solid fa-box-open"></i> {{ __('Aset Saya') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('attendance.view'))
                    <a href="{{ route('attendance.index', ['view_my' => 1]) }}" class="sidebar-item {{ request()->routeIs('attendance.*') && request('view_my') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-clock"></i> {{ __('Absensi Saya') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('attendance.report'))
                    <a href="{{ route('attendance.index') }}" class="sidebar-item {{ request()->routeIs('attendance.*') && !request('view_my') ? 'active' : '' }}">
                        <i class="fa-solid fa-clipboard-user"></i> {{ __('Rekap Absensi') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('schedule.view'))
                    <a href="{{ route('schedules.index') }}" class="sidebar-item {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                        <i class="fa-regular fa-calendar-alt"></i> {{ __('Jadwal Teknisi') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('leave.view'))
                    <a href="{{ route('leave-requests.index') }}" class="sidebar-item {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                        <i class="fa-regular fa-envelope-open"></i> {{ __('Cuti / Izin') }}
                    </a>
                    @endif
                </div>
            </div>

            {{-- Sistem Group --}}
            @if(Auth::user()->hasPermission('setting.view') || Auth::user()->hasPermission('user.view'))
            <div class="sidebar-header mt-2">{{ __('Sistem') }}</div>

            <a class="sidebar-item {{ (request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('regions.*') || request()->routeIs('coordinators.*') || request()->routeIs('packages.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#settingsCollapse" role="button" aria-expanded="{{ (request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('regions.*') || request()->routeIs('coordinators.*') || request()->routeIs('packages.*')) ? 'true' : 'false' }}" aria-controls="settingsCollapse">
                <i class="fa fa-cogs"></i> {{ __('Pengaturan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('regions.*') || request()->routeIs('coordinators.*') || request()->routeIs('packages.*')) ? 'show' : '' }}" id="settingsCollapse">
                <div class="bg-light ps-3">
                    <a href="{{ route('settings.index') }}" class="sidebar-item {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders"></i> {{ __('Pengaturan Umum') }}
                    </a>
                    @if(Auth::user()->hasPermission('setting.view'))
                    <a href="{{ route('categories.index') }}" class="sidebar-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-list"></i> {{ __('Manajemen Kategori') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('region.view'))
                    <a href="{{ route('regions.index') }}" class="sidebar-item {{ request()->routeIs('regions.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-map-location-dot"></i> {{ __('Wilayah') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('coordinator.view'))
                    <a href="{{ route('coordinators.index') }}" class="sidebar-item {{ request()->routeIs('coordinators.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-tie"></i> {{ __('Data Pengurus') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('user.view'))
                    <a href="{{ route('users.index') }}" class="sidebar-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users-gear"></i> {{ __('Manajemen User') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('role.view'))
                    <a href="{{ route('roles.index') }}" class="sidebar-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <i class="fa-regular fa-id-card"></i> {{ __('Manajemen Role') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('package.view'))
                    <a href="{{ route('packages.index') }}" class="sidebar-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cube"></i> {{ __('Paket Internet') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('chat.view'))
                    <a href="{{ route('whatsapp.index') }}" class="sidebar-item {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                        <i class="fa-brands fa-whatsapp"></i> {{ __('Whatsapp API') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('telegram.view'))
                    <a href="{{ route('telegram.index') }}" class="sidebar-item {{ request()->routeIs('telegram.*') ? 'active' : '' }}">
                        <i class="fa-brands fa-telegram"></i> {{ __('Telegram') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('apikey.view'))
                    <a href="{{ route('apikeys.index') }}" class="sidebar-item {{ request()->routeIs('apikeys.*') ? 'active' : '' }}">
                        <i class="fa-regular fa-circle"></i> {{ __('Google Map API') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebar-overlay"></div>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-lg main-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-secondary" id="sidebarToggle">
                    <i class="fa-solid fa-bars fa-lg"></i>
                </button>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Language Switcher -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-globe"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li><a class="dropdown-item {{ app()->getLocale() == 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">English</a></li>
                        <li><a class="dropdown-item {{ app()->getLocale() == 'id' ? 'active' : '' }}" href="{{ route('locale.switch', 'id') }}">Indonesia</a></li>
                    </ul>
                </div>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary border-0 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-regular fa-bell"></i>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ Auth::user()->unreadNotifications->count() }}
                                <span class="visually-hidden">{{ __('unread messages') }}</span>
                            </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-0" style="width: 300px; max-height: 400px; overflow-y: auto;">
                        <li><span class="dropdown-header border-bottom py-2 bg-body-tertiary">{{ __('Notifications') }}</span></li>
                        @forelse(Auth::user()->unreadNotifications as $notification)
                            <li>
                                <a class="dropdown-item py-2 border-bottom" href="{{ route('notifications.redirect', $notification->id) }}">
                                    <div class="small fw-bold">{{ $notification->data['subject'] ?? 'Notification' }}</div>
                                    <div class="small text-muted text-truncate">{{ $notification->data['message'] ?? '' }}</div>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">{{ $notification->created_at->diffForHumans() }}</div>
                                </a>
                            </li>
                        @empty
                            <li class="text-center py-3 text-muted small">{{ __('No new notifications') }}</li>
                        @endforelse
                        @if(Auth::user()->unreadNotifications->count() > 0)
                            <li>
                                <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-center small text-primary py-2 w-100 bg-transparent border-0">
                                        {{ __('Mark all as read') }}
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>

                <!-- Theme Toggle -->
                <button class="btn btn-outline-secondary border-0" id="themeToggle">
                    <i class="fa-solid fa-moon" id="themeIcon"></i>
                </button>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&background=3f6ad8&color=fff' }}" alt="Avatar" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
                        <span class="d-none d-md-inline text-body-emphasis fw-medium small">{{ Auth::user()->name ?? 'User' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="profileDropdown">
                        <li><span class="dropdown-header text-uppercase small">{{ __('Account') }}</span></li>
                        @if(Auth::user()->hasPermission('profile.view'))
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fa-regular fa-user me-2"></i> {{ __('Profile') }}</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> {{ __('Logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-2 py-3 pb-3 flex-grow-1">
            <!-- Flash Messages (Handled by SweetAlert2 now) -->
            {{-- 
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            --}}

            @yield('content')
        </div>
        <footer class="py-3 bg-light mt-auto border-top" style="z-index: 10; position: relative;">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-center small">
                    <div class="text-muted">Copyright {{ date('Y') }} &copy; ds-winets.id <span class="mx-1">&middot;</span> {{ config('app.version') }}</div>
                </div>
            </div>
        </footer>
    </div>
    <!-- /#page-content-wrapper -->
</div>
<!-- /#wrapper -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // SweetAlert2 Flash Messages
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: "{{ __('Success!') }}",
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: "{{ __('Error!') }}",
            text: "{{ session('error') }}",
        });
    @endif

    @if(session('warning'))
        Swal.fire({
            icon: 'warning',
            title: "{{ __('Warning!') }}",
            text: "{{ session('warning') }}",
        });
    @endif
    
    // Sidebar Toggle, Theme Toggle, etc. are moved to public/js/dashboard-custom.js
</script>

<!-- Custom Dashboard JS -->
<script src="{{ asset('js/dashboard-custom.js') }}"></script>

@stack('scripts')

</body>
</html>