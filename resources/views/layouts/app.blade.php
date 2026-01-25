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

    @stack('styles')

    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --primary-color: #3f6ad8; /* ArchitectUI Blue */
        }
        
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #f0f2f5;
            overflow-x: hidden;
        }

        /* Sidebar */
        #sidebar-wrapper {
            height: 100vh;
            width: var(--sidebar-width);
            margin-left: 0;
            transition: margin 0.25s ease-out;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            scrollbar-width: thin;

            background: #fff;
            box-shadow: 7px 0 60px rgba(0,0,0,0.05);
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 0 1.5rem;
            height: var(--header-height);
            display: flex;
            align-items: center;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        #sidebar-wrapper .list-group {
            width: var(--sidebar-width);
        }

        .sidebar-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            color: #343a40;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .sidebar-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }

        .sidebar-item.active {
            background-color: #e0f3ff;
            color: var(--primary-color);
            border-left-color: var(--primary-color);
            font-weight: 600;
        }

        .sidebar-item i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1rem;
            opacity: 0.7;
        }

        .sidebar-header {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            color: #adb5bd;
            padding: 1.5rem 1.5rem 0.5rem;
        }

        /* Content Wrapper */
        #page-content-wrapper {
            margin-left: var(--sidebar-width);
            transition: margin 0.25s ease-out;
        }

        /* Navbar */
        .main-header {
            height: var(--header-height);
            box-shadow: 0 0.46875rem 2.1875rem rgba(4,9,20,0.03), 0 0.9375rem 1.40625rem rgba(4,9,20,0.03), 0 0.25rem 0.53125rem rgba(4,9,20,0.05), 0 0.125rem 0.1875rem rgba(4,9,20,0.03);
            background: #fff;
            padding: 0 1.5rem;
            z-index: 999;
        }

        /* Toggled State */
        body.sb-sidenav-toggled #sidebar-wrapper {
            margin-left: calc(-1 * var(--sidebar-width));
        }
        
        body.sb-sidenav-toggled #page-content-wrapper {
            margin-left: 0;
        }

        /* Dark Mode Overrides - Cyberpunk/Deep Purple Theme */
        [data-bs-theme="dark"] body {
            background-color: #110f24; /* Deep Navy/Purple Background */
            color: #e0e0e0;
        }
        
        [data-bs-theme="dark"] #sidebar-wrapper {
            background: #1f1b36; /* Dark Purple Sidebar */
            box-shadow: 7px 0 60px rgba(0,0,0,0.3);
            border-right: 1px solid #3a3469;
        }

        [data-bs-theme="dark"] #sidebar-wrapper .sidebar-heading {
            border-bottom: 1px solid #3a3469;
            color: #fff;
            background: #1a172e;
        }

        [data-bs-theme="dark"] .sidebar-item {
            color: #b0b8c4;
        }

        [data-bs-theme="dark"] .sidebar-item:hover {
            background-color: #383061;
            color: #fff;
        }

        [data-bs-theme="dark"] .sidebar-item.active {
            background-color: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border-left-color: #818cf8;
        }

        [data-bs-theme="dark"] .main-header {
            background: #1f1b36;
            border-bottom: 1px solid #3a3469;
        }
        
        /* Global component overrides */
        [data-bs-theme="dark"] .bg-white { background-color: #1f1b36 !important; }
        [data-bs-theme="dark"] .bg-light { background-color: #29244a !important; }
        [data-bs-theme="dark"] .text-dark { color: #e0e0e0 !important; }
        [data-bs-theme="dark"] .text-muted { color: #9ca3af !important; }
        
        [data-bs-theme="dark"] .card { 
            background-color: #1f1b36; 
            border-color: #3a3469; 
        }
        [data-bs-theme="dark"] .card-header { 
            background-color: #262145; 
            border-bottom-color: #3a3469; 
            color: #fff;
        }
        
        [data-bs-theme="dark"] .table { 
            color: #e0e0e0; 
            --bs-table-color: #e0e0e0; 
            --bs-table-bg: transparent; 
        }
        [data-bs-theme="dark"] .table thead { background-color: #262145; }
        [data-bs-theme="dark"] .table-hover tbody tr:hover { background-color: #2d2852; }
        [data-bs-theme="dark"] .table td, [data-bs-theme="dark"] .table th { border-color: #3a3469; }

        [data-bs-theme="dark"] .input-group-text.bg-light { 
            background-color: #29244a !important; 
            border-color: #3a3469; 
            color: #b0b8c4; 
        }
        [data-bs-theme="dark"] .dropdown-menu { background-color: #1f1b36; border-color: #3a3469; }
        [data-bs-theme="dark"] .dropdown-item { color: #e0e0e0; }
        [data-bs-theme="dark"] .dropdown-item:hover { background-color: #383061; color: #fff; }
        
        /* Form Controls in Dark Mode */
        [data-bs-theme="dark"] .form-control, 
        [data-bs-theme="dark"] .form-select {
            background-color: #17142b;
            border-color: #3a3469;
            color: #e0e0e0;
        }
        [data-bs-theme="dark"] .form-control:focus, 
        [data-bs-theme="dark"] .form-select:focus {
            background-color: #17142b;
            border-color: #818cf8;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(129, 140, 248, 0.25);
        }
        [data-bs-theme="dark"] .form-control::placeholder {
            color: #6b7280;
        }

        /* Border Overrides */
        [data-bs-theme="dark"] .border { border-color: #3a3469 !important; }
        [data-bs-theme="dark"] .border-top { border-top-color: #3a3469 !important; }
        [data-bs-theme="dark"] .border-bottom { border-bottom-color: #3a3469 !important; }
        [data-bs-theme="dark"] .border-start { border-start-color: #3a3469 !important; }
        [data-bs-theme="dark"] .border-end { border-end-color: #3a3469 !important; }

        /* Leaflet Map Dark Mode Overrides */
        [data-bs-theme="dark"] .leaflet-popup-content-wrapper,
        [data-bs-theme="dark"] .leaflet-popup-tip {
            background-color: #1f1b36;
            color: #e0e0e0;
            box-shadow: 0 3px 14px rgba(0,0,0,0.5);
        }
        [data-bs-theme="dark"] .leaflet-popup-content {
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .leaflet-container a.leaflet-popup-close-button {
            color: #adb5bd;
        }
        [data-bs-theme="dark"] .leaflet-container a.leaflet-popup-close-button:hover {
            color: #fff;
        }
  
          /* Responsive */
        @media (max-width: 992px) {
            #sidebar-wrapper {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            #page-content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            body.sb-sidenav-toggled #sidebar-wrapper {
                margin-left: 0;
            }
            body.sb-sidenav-toggled #page-content-wrapper {
                margin-left: var(--sidebar-width);
            }
            
            /* Mobile Form Improvements */
            .form-control, .form-select, .btn {
                min-height: 46px; /* Larger touch target */
                font-size: 16px; /* Prevent iOS zoom */
            }

            .input-group .btn {
                z-index: 0; /* Fix overlap issues */
            }

            /* Select2 Mobile Fixes */
            .select2-container .select2-selection--single,
            .select2-container .select2-selection--multiple {
                min-height: 46px !important;
                font-size: 16px !important;
                display: flex !important;
                align-items: center !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                line-height: 46px !important;
                padding-left: 12px !important;
                color: #333;
            }

            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
                height: 44px !important;
                top: 1px !important;
            }

            .select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
                padding: 10px 12px; /* Larger tap area for options */
                font-size: 15px;
            }

            /* Stack buttons on mobile */
            form .d-flex.justify-content-end {
                flex-direction: column-reverse;
                gap: 10px;
                width: 100%;
            }

            form .d-flex.justify-content-end .btn {
                width: 100%;
                margin: 0 !important;
            }
            
            /* Dark Mode Select2 Override */
            [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection {
                background-color: #17142b !important;
                border-color: #3a3469 !important;
                color: #e0e0e0 !important;
            }
            [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered {
                color: #e0e0e0 !important;
            }
            [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown {
                background-color: #1f1b36 !important;
                border-color: #3a3469 !important;
            }
            [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option {
                color: #e0e0e0 !important;
            }
            [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--highlighted {
                background-color: #383061 !important;
            }
        }
        }
    </style>
    
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
        <div class="sidebar-heading">
            <img src="{{ asset('img/logo.png') }}" alt="MSTORE.NET" style="max-height: 40px;" class="me-2">
            <span>MSTORE.NET</span>
        </div>
        <div class="list-group list-group-flush pb-4">
            @if(
                Auth::user()->hasPermission('dashboard.view') ||
                Auth::user()->hasPermission('customer.view') ||
                Auth::user()->hasPermission('ticket.view')
            )
            <div class="sidebar-header">
                {{ __('Main Menu') }}
                <span class="visually-hidden">{{ __('Main Menu') }}</span>
            </div>
            @endif
            
            @if(Auth::user()->hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i> {{ __('Dashboard') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('customer.view'))
            <a href="{{ route('customers.index') }}" class="sidebar-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i> {{ __('Customers') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('package.view'))
            <a href="{{ route('packages.index') }}" class="sidebar-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                <i class="fa-solid fa-box-open"></i> {{ __('Packages') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('ticket.view'))
            <a href="{{ route('tickets.index') }}" class="sidebar-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <i class="fa-solid fa-ticket"></i> {{ __('Tickets') }} <span class="visually-hidden">{{ __('Tickets') }}</span>
            </a>
            @endif

            @if(
                Auth::user()->hasPermission('genieacs.view') ||
                Auth::user()->hasPermission('router.view') ||
                Auth::user()->hasPermission('olt.view') ||
                Auth::user()->hasPermission('map.view') ||
                Auth::user()->hasPermission('odp.view') ||
                Auth::user()->hasPermission('odc.view')
            )
            <div class="sidebar-header">
                {{ __('Network Management') }}
                <span class="visually-hidden">{{ __('Network Management') }}</span>
            </div>
            @endif

            @if(Auth::user()->hasPermission('genieacs.view'))
            <a href="{{ route('genieacs.index') }}" class="sidebar-item {{ request()->routeIs('genieacs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-satellite-dish"></i> {{ __('Monitoring Genieacs') }}
            </a>
            @endif
            
            @if(Auth::user()->hasPermission('router.view'))
            <a href="{{ route('routers.index') }}" class="sidebar-item {{ request()->routeIs('routers.*') ? 'active' : '' }}">
                <i class="fa-solid fa-shield-alt"></i> {{ __('Management Router') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('olt.view'))
            <a href="{{ route('olt.index') }}" class="sidebar-item {{ request()->routeIs('olt.*') ? 'active' : '' }}">
                <i class="fa-solid fa-server"></i> {{ __('OLT Management') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('odc.view'))
            <a href="{{ route('odcs.index') }}" class="sidebar-item {{ request()->routeIs('odcs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-box-archive"></i> {{ __('ODC Management') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('odp.view'))
            <a href="{{ route('odps.index') }}" class="sidebar-item {{ request()->routeIs('odps.*') ? 'active' : '' }}">
                <i class="fa-solid fa-network-wired"></i> {{ __('ODP Management') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('htb.view'))
            <a href="{{ route('htbs.index') }}" class="sidebar-item {{ request()->routeIs('htbs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-sitemap"></i> {{ __('HTB Management') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('map.view'))
            <a href="{{ route('map.index') }}" class="sidebar-item {{ request()->routeIs('map.*') ? 'active' : '' }}">
                <i class="fa-solid fa-map-location-dot"></i> {{ __('Network Map') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('calculator.view'))
            <a href="{{ route('calculator.pon') }}" class="sidebar-item {{ request()->routeIs('calculator.pon') ? 'active' : '' }}">
                <i class="fa-solid fa-calculator"></i> {{ __('Kalkulator PON') }}
            </a>
            @endif

            {{-- Installations removed as per request --}}
            {{-- @if(Auth::user()->hasPermission('installation.view'))
            <a href="{{ route('installations.index') }}" class="sidebar-item {{ request()->routeIs('installations.*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-check"></i> {{ __('Installations') }}
            </a>
            @endif --}}

            @if(Auth::user()->hasPermission('technician.view') || Auth::user()->hasPermission('attendance.view') || Auth::user()->hasPermission('setting.view'))
            <div class="sidebar-header">{{ __('Technician Management') }}</div>
            @endif
            
            @if(Auth::user()->hasPermission('technician.view'))
            <a href="{{ route('technicians.index') }}" class="sidebar-item {{ request()->routeIs('technicians.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users-gear"></i> {{ __('Manage Technicians') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('attendance.view') || Auth::user()->hasPermission('setting.view'))
            <a class="sidebar-item {{ (request()->routeIs('attendance.*') || request()->routeIs('schedules.*') || request()->routeIs('leave-requests.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#attendanceCollapse" role="button" aria-expanded="{{ (request()->routeIs('attendance.*') || request()->routeIs('schedules.*') || request()->routeIs('leave-requests.*')) ? 'true' : 'false' }}" aria-controls="attendanceCollapse">
                <i class="fa-solid fa-clock"></i> {{ __('Attendance System') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('attendance.*') || request()->routeIs('schedules.*') || request()->routeIs('leave-requests.*')) ? 'show' : '' }}" id="attendanceCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('attendance.view'))
                    <a href="{{ route('attendance.create') }}" class="sidebar-item {{ request()->routeIs('attendance.create') ? 'active' : '' }}">
                        <i class="fa-solid fa-fingerprint"></i> {{ __('My Attendance') }}
                    </a>
                    <a href="{{ route('attendance.index') }}" class="sidebar-item {{ request()->routeIs('attendance.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-check"></i> {{ __('Recap') }}
                    </a>
                    <a href="{{ route('schedules.index') }}" class="sidebar-item {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-calendar-week"></i> {{ __('Work Schedule') }}
                    </a>
                    <a href="{{ route('leave-requests.index') }}" class="sidebar-item {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-envelope-open-text"></i> {{ __('Leave Requests') }}
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('setting.view'))
                    <a href="{{ route('settings.index') }}" class="sidebar-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders"></i> {{ __('Attendance Settings') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif
            
            @if(
                Auth::user()->hasPermission('coordinator.view') ||
                Auth::user()->hasPermission('investor.view') ||
                Auth::user()->hasPermission('region.view')
            )
            <a class="sidebar-item {{ (request()->routeIs('coordinators.*') || request()->routeIs('regions.*') || request()->routeIs('investors.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#coordinatorCollapse" role="button" aria-expanded="{{ (request()->routeIs('coordinators.*') || request()->routeIs('regions.*') || request()->routeIs('investors.*')) ? 'true' : 'false' }}" aria-controls="coordinatorCollapse">
                <i class="fa-solid fa-user-tie"></i> {{ __('Coordinators') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('coordinators.*') || request()->routeIs('regions.*') || request()->routeIs('investors.*')) ? 'show' : '' }}" id="coordinatorCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('coordinator.view'))
                    <a href="{{ route('coordinators.index') }}" class="sidebar-item {{ request()->routeIs('coordinators.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-tie"></i> {{ __('Manage Coordinators') }}
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('investor.view'))
                    <a href="{{ route('investors.index') }}" class="sidebar-item {{ request()->routeIs('investors.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-trend-up"></i> {{ __('Investors') }}
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('region.view'))
                    <a href="{{ route('regions.index') }}" class="sidebar-item {{ request()->routeIs('regions.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-map"></i> {{ __('Regions') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif
            
            @if(Auth::user()->hasPermission('inventory.view') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <div class="sidebar-header">
                {{ __('Inventory Management') }}
            </div>

            <a href="{{ route('inventory.index', ['type_group' => 'tool']) }}" class="sidebar-item {{ request('type_group') == 'tool' ? 'active' : '' }}">
                <i class="fa-solid fa-screwdriver-wrench"></i> {{ __('Tools & Assets') }}
            </a>

            <a href="{{ route('inventory.index', ['type_group' => 'material']) }}" class="sidebar-item {{ request('type_group') == 'material' ? 'active' : '' }}">
                <i class="fa-solid fa-microchip"></i> {{ __('Devices & Materials') }}
            </a>

            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <a href="{{ route('inventory.index') }}" class="sidebar-item {{ request()->routeIs('inventory.index') && !request()->has('type_group') ? 'active' : '' }}">
                <i class="fa-solid fa-list"></i> {{ __('All Inventory Items') }}
            </a>
            @endif
            @endif

            @if(Auth::user()->hasPermission('inventory.view') || Auth::user()->hasRole('admin'))
            <a href="{{ route('inventory.my_assets') }}" class="sidebar-item {{ request()->routeIs('inventory.my_assets') ? 'active' : '' }}">
                <i class="fa-solid fa-toolbox"></i> {{ __('My Assets & Tools') }}
            </a>
            @endif

            @if(
                Auth::user()->hasPermission('finance.view') ||
                Auth::user()->hasPermission('chat.view') ||
                Auth::user()->hasPermission('setting.view') ||
                Auth::user()->hasPermission('user.view') ||
                Auth::user()->hasPermission('role.view')
            )
            <div class="sidebar-header">
                {{ __('Administration') }} <span class="visually-hidden">{{ __('Administration') }}</span>
            </div>
            @endif

            @if(Auth::user()->hasPermission('finance.view'))
            <a href="{{ route('finance.index') }}" class="sidebar-item {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                <i class="fa-solid fa-wallet"></i> {{ __('Finance') }}
            </a>
            @endif
            


            @if(Auth::user()->hasPermission('setting.view'))
            <a href="{{ route('telegram.index') }}" class="sidebar-item {{ request()->routeIs('telegram.*') ? 'active' : '' }}">
                <i class="fa-brands fa-telegram"></i> {{ __('Telegram') }}
            </a>
            <a href="{{ route('whatsapp.index') }}" class="sidebar-item {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                <i class="fa-brands fa-whatsapp"></i> {{ __('WhatsApp') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('setting.view') || Auth::user()->hasPermission('user.view') || Auth::user()->hasPermission('role.view'))
            <a class="sidebar-item {{ (request()->routeIs('settings.*') || request()->routeIs('apikeys.*') || request()->routeIs('users.*') || request()->routeIs('roles.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#settingsCollapse" role="button" aria-expanded="{{ (request()->routeIs('settings.*') || request()->routeIs('apikeys.*') || request()->routeIs('users.*') || request()->routeIs('roles.*')) ? 'true' : 'false' }}" aria-controls="settingsCollapse">
                <i class="fa-solid fa-gears"></i> {{ __('Settings') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('settings.*') || request()->routeIs('apikeys.*') || request()->routeIs('users.*') || request()->routeIs('roles.*')) ? 'show' : '' }}" id="settingsCollapse">
                <div class="bg-light ps-3">
                    @if(Auth::user()->hasPermission('setting.view'))
                    <a href="{{ route('settings.index') }}" class="sidebar-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-wrench"></i> {{ __('General Settings') }}
                    </a>
                    @endif
                    
                    @if(Auth::user()->hasPermission('apikey.view'))
                    <a href="{{ route('apikeys.index') }}" class="sidebar-item {{ request()->routeIs('apikeys.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-key"></i> {{ __('API Key Management') }}
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('user.view'))
                    <a href="{{ route('users.index') }}" class="sidebar-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear"></i> {{ __('Users') }}
                    </a>
                    @endif

                    @if(Auth::user()->hasPermission('role.view'))
                    <a href="{{ route('roles.index') }}" class="sidebar-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-shield-halved"></i> {{ __('Roles') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
        <div class="sidebar-footer text-center py-2 text-muted small">
            {{ config('app.version') }}
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

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

        <div class="container-fluid px-4 py-4 pb-5 flex-grow-1">
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
    
    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.body.classList.toggle('sb-sidenav-toggled');
    });

    // Close Sidebar when clicking overlay (Mobile)
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            document.body.classList.remove('sb-sidenav-toggled');
        });
    }

    // Theme Toggle Logic
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    function updateThemeIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    }

    // Initialize Icon
    updateThemeIcon(htmlElement.getAttribute('data-bs-theme'));

    themeToggle.addEventListener('click', () => {
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);

        // Dispatch custom event for components to react
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
    });
</script>

@stack('scripts')

</body>
</html>
