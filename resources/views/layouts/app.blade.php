<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'MStore'))</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ app()->environment('production') ? secure_asset('favicon.svg') : asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ app()->environment('production') ? secure_asset('favicon.ico') : asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Custom Dashboard CSS -->
    <link href="{{ app()->environment('production') ? secure_asset('css/dashboard-custom.css') : asset('css/dashboard-custom.css') }}" rel="stylesheet">
    <link href="{{ app()->environment('production') ? secure_asset('css/app-android.css') : asset('css/app-android.css') }}" rel="stylesheet">
    <style>
        .mstore-swal-popup {
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            border-radius: 18px !important;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18) !important;
            backdrop-filter: blur(10px);
        }

        .mstore-swal-title {
            font-weight: 700 !important;
            font-size: 1.03rem !important;
        }

        .mstore-swal-html {
            font-size: 0.92rem !important;
            line-height: 1.45 !important;
            color: inherit !important;
        }

        .mstore-swal-toast {
            border-radius: 14px !important;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.2) !important;
            border: 1px solid rgba(148, 163, 184, 0.22) !important;
            backdrop-filter: blur(8px);
        }

        .mstore-page-loader {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.28);
            backdrop-filter: blur(6px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            z-index: 2000;
        }

        .mstore-page-loader.is-active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .mstore-page-loader-card {
            min-width: 220px;
            max-width: 90vw;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.25);
            padding: 1rem 1.2rem;
            color: #0f172a;
        }

        [data-bs-theme="dark"] .mstore-page-loader-card {
            background: rgba(15, 23, 42, 0.92);
            color: #e2e8f0;
            border-color: rgba(148, 163, 184, 0.35);
        }

        .mstore-page-loader-text {
            font-size: 0.92rem;
            font-weight: 600;
        }
    </style>

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
<body class="route-{{ request()->segment(1) ?? 'home' }} route-name-{{ str_replace('.', '-', request()->route()?->getName() ?? 'unknown') }}">
<div class="mstore-page-loader" id="mstorePageLoader" aria-hidden="true">
    <div class="mstore-page-loader-card d-flex align-items-center gap-3">
        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
        <span class="mstore-page-loader-text" id="mstorePageLoaderText">Membuka halaman...</span>
    </div>
</div>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading py-3 position-relative d-flex align-items-center justify-content-center">
            <div class="sidebar-brand-icon">
                <img src="{{ app()->environment('production') ? secure_asset('img/logo.png') : asset('img/logo.png') }}" alt="MSTORE.NET" class="img-fluid">
            </div>
            <span class="sidebar-brand-text ms-2"></span>
            <!-- Close Button for Mobile -->
            <button class="btn btn-link position-absolute top-0 end-0 me-2 d-lg-none" id="sidebarClose" style="z-index: 1051;">
                <i class="fa-solid fa-times fa-lg"></i>
            </button>
        </div>
        <div class="list-group list-group-flush pb-2">
            
            {{-- User Panel (Simplified) --}}
           
            @php
                $isKasirWashLimited = Auth::user()->hasRole('kasir-wash') || Auth::user()->hasRole('karyawan-wash');
            @endphp
            <div class="sidebar-header mt-2">{{ __('Main Menu') }}</div>

            {{-- Dashboard --}}
            @if(Auth::user()->hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa fa-tachometer-alt"></i> {{ __('Dashboard') }}
            </a>
            @endif

            {{-- AI Center --}}
            @if(Auth::user()->hasRole('admin'))
            <a href="{{ route('ai.index') }}" class="sidebar-item {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                <i class="fa-solid fa-robot"></i> {{ __('Pusat AI') }} <span class="badge bg-primary ms-auto" style="font-size: 0.6rem;">BETA</span>
            </a>
            @endif

            {{-- Client Portal (Grouped) --}}
            @if(Auth::user()->hasRole('customer'))
            <div class="sidebar-header mt-2">{{ __('Client Portal') }}</div>
            @php
                $clientRoutesActive = request()->routeIs('client.*') || request()->routeIs('profile.edit') || request()->routeIs('profile.id_card');
            @endphp
            <a class="sidebar-item {{ $clientRoutesActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#clientPortalCollapse" role="button" aria-expanded="{{ $clientRoutesActive ? 'true' : 'false' }}" aria-controls="clientPortalCollapse">
                <i class="fa-solid fa-user-circle"></i> {{ __('Portal Pelanggan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $clientRoutesActive ? 'show' : '' }}" id="clientPortalCollapse">
                <div class="ps-2">
                    <a href="{{ route('client.portal') }}" class="sidebar-item {{ request()->routeIs('client.portal') ? 'active' : '' }}">
                        <i class="fa-solid fa-house-user"></i> {{ __('Beranda Portal') }}
                    </a>
                    <a href="{{ route('client.connection') }}" class="sidebar-item {{ request()->routeIs('client.connection') ? 'active' : '' }}">
                        <i class="fa fa-random"></i> {{ __('Info Koneksi') }}
                    </a>
                    <a href="{{ route('client.invoices.index') }}" class="sidebar-item {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> {{ __('Tagihan Saya') }}
                    </a>
                    <a href="{{ route('client.credentials.show') }}" class="sidebar-item {{ request()->routeIs('client.credentials.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-key"></i> {{ __('Kredensial Internet') }}
                    </a>
                    <a href="{{ route('profile.edit') }}" class="sidebar-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                        <i class="fa-regular fa-user"></i> {{ __('Profil Saya') }}
                    </a>
                    <a href="{{ route('profile.id_card') }}" class="sidebar-item {{ request()->routeIs('profile.id_card') ? 'active' : '' }}">
                        <i class="fa-regular fa-id-card"></i> {{ __('Kartu Identitas') }}
                    </a>
                    @php $mixUrl = \App\Models\Setting::getValue('mixradius_base_url', env('MIXRADIUS_BASE_URL', '')); @endphp
                    @if(!empty($mixUrl))
                    <a href="{{ route('client.mixradius') }}" class="sidebar-item {{ request()->routeIs('client.mixradius') ? 'active' : '' }}">
                        <i class="fa-solid fa-up-right-from-square"></i> {{ __('Portal MixRADIUS') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pelanggan & Layanan Group --}}
            @if(! $isKasirWashLimited)
            <div class="sidebar-header mt-2">{{ __('Pelanggan & Layanan') }}</div>

            @if(Auth::user()->hasPermission('customer.view'))
            <a href="{{ route('customers.index') }}" class="sidebar-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fa fa-users"></i> {{ __('Data Pelanggan') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('installation.view'))
            <a href="{{ route('installations.index') }}" class="sidebar-item {{ request()->routeIs('installations.*') ? 'active' : '' }}">
                <i class="fa-solid fa-screwdriver-wrench"></i> {{ __('Pemasangan Baru') }}
            </a>
            @endif

            @if(Auth::user()->hasPermission('hotspot.view') || Auth::user()->hasPermission('router.view') || Auth::user()->hasPermission('pppoe.view') || Auth::user()->hasPermission('package.view'))
            <a class="sidebar-item {{ (request()->routeIs('hotspot.index') || request()->routeIs('pppoe.index') || request()->routeIs('packages.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#servicesCollapse" role="button" aria-expanded="{{ (request()->routeIs('hotspot.index') || request()->routeIs('pppoe.index') || request()->routeIs('packages.*')) ? 'true' : 'false' }}" aria-controls="servicesCollapse">
                <i class="fa fa-wifi"></i> {{ __('Layanan Aktif') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('hotspot.index') || request()->routeIs('pppoe.index') || request()->routeIs('packages.*')) ? 'show' : '' }}" id="servicesCollapse">
                <div class="ps-2">
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
                    @if(Auth::user()->hasPermission('package.view'))
                    <a href="{{ route('packages.index') }}" class="sidebar-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cube"></i> {{ __('Paket Internet') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif
            @endif

            {{-- Jaringan Group --}}
            @if(! $isKasirWashLimited)
            <div class="sidebar-header mt-2">{{ __('Jaringan') }}</div>

            @php
                $networkMonitoringActive = request()->routeIs('map.*') || request()->routeIs('genieacs.*') || request()->routeIs('calculator.*') || request()->routeIs('network.analyzer');
                $networkAccessActive = request()->routeIs('routers.*') || request()->routeIs('vpn.*');
            @endphp

            @if(Auth::user()->hasPermission('map.view') || Auth::user()->hasPermission('genieacs.view') || Auth::user()->hasPermission('genieacs_server.view') || Auth::user()->hasPermission('calculator.view') || Auth::user()->hasPermission('router.view'))
            <a class="sidebar-item {{ $networkMonitoringActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkMonitoringCollapse" role="button" aria-expanded="{{ $networkMonitoringActive ? 'true' : 'false' }}" aria-controls="networkMonitoringCollapse">
                <i class="fa-solid fa-satellite-dish"></i> {{ __('Monitoring & Tools') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $networkMonitoringActive ? 'show' : '' }}" id="networkMonitoringCollapse">
                <div class="ps-3">
                    @if(Auth::user()->hasPermission('map.view'))
                    <a href="{{ route('map.index') }}" class="sidebar-item {{ request()->routeIs('map.*') ? 'active' : '' }}">
                        <i class="fa fa-map-marked-alt"></i> {{ __('Peta Jaringan') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('genieacs.view') || Auth::user()->hasPermission('genieacs_server.view'))
                    <a href="{{ route('genieacs.index') }}" class="sidebar-item {{ request()->routeIs('genieacs.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-network-wired"></i> {{ __('Network Monitor') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('genieacs_server.view'))
                    <a href="{{ route('genieacs.servers.index') }}" class="sidebar-item {{ request()->routeIs('genieacs.servers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-database"></i> {{ __('Server GenieACS') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('calculator.view'))
                    <a href="{{ route('calculator.pon') }}" class="sidebar-item {{ request()->routeIs('calculator.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-calculator"></i> {{ __('Kalkulator PON') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('router.view'))
                    <a href="{{ route('network.analyzer') }}" class="sidebar-item {{ request()->routeIs('network.analyzer') ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge-high"></i> {{ __('Network Analyzer') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if(Auth::user()->hasPermission('router.view'))
            <a class="sidebar-item {{ $networkAccessActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkAccessCollapse" role="button" aria-expanded="{{ $networkAccessActive ? 'true' : 'false' }}" aria-controls="networkAccessCollapse">
                <i class="fa-solid fa-server"></i> {{ __('Perangkat & Akses') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $networkAccessActive ? 'show' : '' }}" id="networkAccessCollapse">
                <div class="ps-3">
                    <a href="{{ route('routers.index') }}" class="sidebar-item {{ (request()->routeIs('routers.*') && !request()->routeIs('routers.sessions')) ? 'active' : '' }}">
                        <i class="fa fa-server"></i> {{ __('Router / NAS') }}
                    </a>
                    <a href="{{ route('vpn.servers.index') }}" class="sidebar-item {{ request()->routeIs('vpn.servers.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-shield-halved"></i> {{ __('VPN Bridge') }}
                    </a>
                    <a href="{{ route('vpn.guide') }}" class="sidebar-item {{ request()->routeIs('vpn.guide') ? 'active' : '' }}">
                        <i class="fa-regular fa-circle-question"></i> {{ __('Panduan VPN') }}
                    </a>
                </div>
            </div>
            @endif

            @if(Auth::user()->hasPermission('olt.view') || Auth::user()->hasPermission('odc.view') || Auth::user()->hasPermission('odp.view') || Auth::user()->hasPermission('closure.view') || Auth::user()->hasPermission('htb.view'))
            <a class="sidebar-item {{ (request()->routeIs('olt.*') || request()->routeIs('odcs.*') || request()->routeIs('odps.*') || request()->routeIs('closures.*') || request()->routeIs('htbs.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkInfraCollapse" role="button" aria-expanded="{{ (request()->routeIs('olt.*') || request()->routeIs('odcs.*') || request()->routeIs('odps.*') || request()->routeIs('closures.*') || request()->routeIs('htbs.*')) ? 'true' : 'false' }}" aria-controls="networkInfraCollapse">
                <i class="fa fa-sitemap"></i> {{ __('Infrastruktur') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('olt.*') || request()->routeIs('odcs.*') || request()->routeIs('odps.*') || request()->routeIs('closures.*') || request()->routeIs('htbs.*')) ? 'show' : '' }}" id="networkInfraCollapse">
                <div class="ps-3">
                    @if(Auth::user()->hasPermission('olt.view'))
                    <a href="{{ route('olt.index') }}" class="sidebar-item {{ request()->routeIs('olt.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-server"></i> {{ __('OLT') }}
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
                    @if(Auth::user()->hasPermission('closure.view'))
                    <a href="{{ route('closures.index') }}" class="sidebar-item {{ request()->routeIs('closures.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-box-open"></i> {{ __('Closure') }}
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

            @endif

            {{-- Keuangan Group --}}
            @if(! $isKasirWashLimited && (Auth::user()->hasPermission('finance.view') || Auth::user()->hasPermission('investor.view')))
            <div class="sidebar-header mt-2">{{ __('Keuangan') }}</div>
            @if(Auth::user()->hasPermission('finance.view'))
            <a href="{{ route('finance.index') }}" class="sidebar-item {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                <i class="fa fa-wallet"></i> {{ __('Dashboard Keuangan') }}
            </a>
            <a href="{{ route('finance.profit_loss') }}" class="sidebar-item {{ request()->routeIs('finance.profit_loss*') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i> {{ __('Profit & Loss') }}
            </a>
            <a href="{{ route('finance.material_report') }}" class="sidebar-item {{ request()->routeIs('finance.material_report') ? 'active' : '' }}">
                <i class="fa-solid fa-boxes-stacked"></i> {{ __('Laporan Material') }}
            </a>
            <a href="{{ route('finance.manager_report') }}" class="sidebar-item {{ request()->routeIs('finance.manager_report*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-tie"></i> {{ __('Laporan Manajer') }}
            </a>
            <a class="sidebar-item {{ (request()->routeIs('accounting.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#accountingCollapse" role="button" aria-expanded="{{ (request()->routeIs('accounting.*')) ? 'true' : 'false' }}" aria-controls="accountingCollapse">
                <i class="fa-solid fa-book-open"></i> {{ __('Akuntansi') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('accounting.*')) ? 'show' : '' }}" id="accountingCollapse">
                <div class="ps-3">
                    <a href="{{ route('accounting.trial_balance') }}" class="sidebar-item {{ request()->routeIs('accounting.trial_balance') ? 'active' : '' }}">
                        <i class="fa-regular fa-file-lines"></i> {{ __('Neraca Saldo') }}
                    </a>
                    <a href="{{ route('accounting.income_statement') }}" class="sidebar-item {{ request()->routeIs('accounting.income_statement') ? 'active' : '' }}">
                        <i class="fa-regular fa-file-lines"></i> {{ __('Laba Rugi') }}
                    </a>
                    <a href="{{ route('accounting.balance_sheet') }}" class="sidebar-item {{ request()->routeIs('accounting.balance_sheet') ? 'active' : '' }}">
                        <i class="fa-regular fa-file-lines"></i> {{ __('Neraca') }}
                    </a>
                    <a href="{{ route('accounting.ledger') }}" class="sidebar-item {{ request()->routeIs('accounting.ledger') ? 'active' : '' }}">
                        <i class="fa-regular fa-file-lines"></i> {{ __('Buku Besar') }}
                    </a>
                    <a href="{{ route('accounting.cash_flow') }}" class="sidebar-item {{ request()->routeIs('accounting.cash_flow') ? 'active' : '' }}">
                        <i class="fa-regular fa-file-lines"></i> {{ __('Arus Kas') }}
                    </a>
                    <a href="{{ route('accounting.periods.index') }}" class="sidebar-item {{ request()->routeIs('accounting.periods.*') ? 'active' : '' }}">
                        <i class="fa-regular fa-calendar-check"></i> {{ __('Periode Akuntansi') }}
                    </a>
                </div>
            </div>
            @endif
            @if(Auth::user()->hasPermission('investor.view'))
            <a href="{{ route('finance.investor_report') }}" class="sidebar-item {{ request()->routeIs('finance.investor_report*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-invoice-dollar"></i> {{ __('Laporan Investor') }}
            </a>
            <a href="{{ route('investors.index') }}" class="sidebar-item {{ request()->routeIs('investors.*') ? 'active' : '' }}">
                <i class="fa-solid fa-hand-holding-dollar"></i> {{ __('Data Investor') }}
            </a>
            @endif
            @endif

            {{-- Toko ATK Group --}}
            @if(! $isKasirWashLimited && (Auth::user()->hasPermission('atk.view') || Auth::user()->hasPermission('atk.pos')))
            <div class="sidebar-header mt-2">{{ __('Toko ATK') }}</div>
            
            <a class="sidebar-item {{ (request()->routeIs('atk.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#atkCollapse" role="button" aria-expanded="{{ (request()->routeIs('atk.*')) ? 'true' : 'false' }}" aria-controls="atkCollapse">
                <i class="fa fa-store"></i> {{ __('Kasir & Produk') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('atk.*')) ? 'show' : '' }}" id="atkCollapse">
                <div class="ps-3">
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
                    @if(Auth::user()->hasPermission('atk.manage'))
                    <a href="{{ route('atk.expenses.index') }}" class="sidebar-item {{ request()->routeIs('atk.expenses.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> {{ __('Pengeluaran') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('atk.report'))
                    <a href="{{ route('atk.transactions.index') }}" class="sidebar-item {{ request()->routeIs('atk.transactions.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-history"></i> {{ __('Riwayat Transaksi') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('atk.report'))
                    <a href="{{ route('atk.reports.index') }}" class="sidebar-item {{ request()->routeIs('atk.reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i> {{ __('Laporan') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Car Wash Group --}}
            @if(Auth::user()->hasPermission('wash.view') || Auth::user()->hasPermission('wash.pos') || Auth::user()->hasPermission('wash.manage') || Auth::user()->hasPermission('wash.report'))
            <div class="sidebar-header mt-2">{{ __('Cuci Kendaraan') }}</div>

            <a class="sidebar-item {{ (request()->routeIs('wash.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#washCollapse" role="button" aria-expanded="{{ (request()->routeIs('wash.*')) ? 'true' : 'false' }}" aria-controls="washCollapse">
                <i class="fa fa-car"></i> {{ __('Kasir & Layanan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('wash.*')) ? 'show' : '' }}" id="washCollapse">
                <div class="ps-3">
                    @if(Auth::user()->hasPermission('wash.view'))
                    <a href="{{ route('wash.dashboard') }}" class="sidebar-item {{ request()->routeIs('wash.dashboard') ? 'active' : '' }}">
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
                    @if(Auth::user()->hasPermission('wash.manage'))
                    <a href="{{ route('wash.expenses.index') }}" class="sidebar-item {{ request()->routeIs('wash.expenses.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> {{ __('Pengeluaran') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('wash.report'))
                    <a href="{{ route('wash.transactions.index') }}" class="sidebar-item {{ request()->routeIs('wash.transactions.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-history"></i> {{ __('Riwayat Transaksi') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('wash.report'))
                    <a href="{{ route('wash.reports.index') }}" class="sidebar-item {{ request()->routeIs('wash.reports.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i> {{ __('Laporan') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Operasional Group (guarded by permissions) --}}
            @if(
                ! $isKasirWashLimited &&
                (
                    Auth::user()->hasPermission('ticket.view') ||
                    Auth::user()->hasPermission('inventory.view') ||
                    Auth::user()->hasPermission('technician.view') ||
                    Auth::user()->hasPermission('attendance.view') ||
                    Auth::user()->hasPermission('attendance.report') ||
                    Auth::user()->hasPermission('schedule.view') ||
                    Auth::user()->hasPermission('leave.view')
                )
            )
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
                    <div class="ps-3">
                        @if(Auth::user()->hasPermission('inventory.view'))
                        <a href="{{ route('inventory.index') }}" class="sidebar-item {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-toolbox"></i> {{ __('Inventory / Tools') }}
                        </a>
                        <a href="{{ route('inventory.my_assets') }}" class="sidebar-item {{ request()->routeIs('inventory.my_assets') ? 'active' : '' }}">
                            <i class="fa-solid fa-box-open"></i> {{ __('Aset Saya') }}
                        </a>
                        <a href="{{ route('inventory.pickup') }}" class="sidebar-item {{ request()->routeIs('inventory.pickup*') ? 'active' : '' }}">
                            <i class="fa-solid fa-hand-holding"></i> {{ __('Pengambilan Barang') }}
                        </a>
                        @endif
                        @if(Auth::user()->hasPermission('technician.view'))
                        <a href="{{ route('technicians.index') }}" class="sidebar-item {{ request()->routeIs('technicians.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-gear"></i> {{ __('Teknisi') }}
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
            @endif

            {{-- Sistem Group --}}
            @if(! $isKasirWashLimited && (Auth::user()->hasPermission('setting.view') || Auth::user()->hasPermission('user.view')))
            <div class="sidebar-header mt-2">{{ __('Sistem') }}</div>

            <a class="sidebar-item {{ (request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('regions.*') || request()->routeIs('coordinators.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#settingsCollapse" role="button" aria-expanded="{{ (request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('regions.*') || request()->routeIs('coordinators.*')) ? 'true' : 'false' }}" aria-controls="settingsCollapse">
                <i class="fa fa-cogs"></i> {{ __('Pengaturan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ (request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('regions.*') || request()->routeIs('coordinators.*')) ? 'show' : '' }}" id="settingsCollapse">
                <div class="ps-3">
                    @if(Auth::user()->hasPermission('setting.view'))
                    <a href="{{ route('settings.index') }}" class="sidebar-item {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders"></i> {{ __('Pengaturan Umum') }}
                    </a>
                    <a href="{{ route('settings.atk.index') }}" class="sidebar-item {{ request()->routeIs('settings.atk.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> {{ __('Pengaturan ATK') }}
                    </a>
                    <a href="{{ route('settings.wash.index') }}" class="sidebar-item {{ request()->routeIs('settings.wash.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-soap"></i> {{ __('Pengaturan Wash') }}
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
                <button class="btn btn-link text-body" id="sidebarToggle">
                    <i class="fa-solid fa-bars fa-lg"></i>
                </button>
            </div>

            <div class="d-flex align-items-center gap-3"> 
                <!-- Language Switcher -->
                <div class="dropdown">
                    <button class="btn btn-link text-body border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-globe"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li><a class="dropdown-item {{ app()->getLocale() == 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">English</a></li>
                        <li><a class="dropdown-item {{ app()->getLocale() == 'id' ? 'active' : '' }}" href="{{ route('locale.switch', 'id') }}">Indonesia</a></li>
                    </ul>
                </div>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-link text-body border-0 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                <button class="btn btn-link text-body border-0" id="themeToggle">
                    <i class="fa-solid fa-moon" id="themeIcon"></i>
                </button>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-body" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&background=3f6ad8&color=fff' }}" alt="Avatar" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
                        <span class="d-none d-md-inline fw-medium small">{{ Auth::user()->name ?? 'User' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="profileDropdown">
                        <li><span class="dropdown-header text-uppercase small">{{ __('Account') }}</span></li>
                        <li><a class="dropdown-item" href="{{ route('landing') }}"><i class="fa-solid fa-globe me-2"></i> {{ __('Landing') }}</a></li>
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

        <div class="container-fluid px-3 py-3 pb-3 flex-grow-1">
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
        <footer class="py-3 mt-auto border-top main-footer" style="z-index: 10; position: relative;">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-center small">
                    <div class="text-muted">Copyright {{ date('Y') }} &copy; mstore.id <span class="mx-1">&middot;</span> {{ config('app.version') }}</div>
                </div>
            </div>
        </footer>
    </div>
    <!-- /#page-content-wrapper -->
</div>
<!-- /#wrapper -->

@include('layouts.partials.mobile-nav')

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- ApexCharts (Charts) -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function (e) {
                e.preventDefault();
                document.body.classList.toggle('sb-sidenav-toggled');
            });
        }
    });

    (function () {
        const buildPopupConfig = function (overrides) {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const popupBase = {
                customClass: {
                    popup: 'mstore-swal-popup',
                    title: 'mstore-swal-title',
                    htmlContainer: 'mstore-swal-html'
                },
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#e2e8f0' : '#1e293b',
                showConfirmButton: false,
                timerProgressBar: true
            };
            return Object.assign({}, popupBase, overrides || {});
        };

        window.mstoreNotify = {
            success: function (message, options) {
                return Swal.fire(buildPopupConfig(Object.assign({
                    icon: 'success',
                    title: 'Berhasil',
                    html: message || 'Aksi berhasil diproses',
                    position: 'center',
                    timer: 2600,
                    showConfirmButton: false
                }, options || {})));
            },
            error: function (message, options) {
                return Swal.fire(buildPopupConfig(Object.assign({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    html: message || 'Terjadi kesalahan saat memproses data',
                    showConfirmButton: true,
                    confirmButtonText: 'Tutup'
                }, options || {})));
            },
            warning: function (message, options) {
                return Swal.fire(buildPopupConfig(Object.assign({
                    icon: 'warning',
                    title: 'Peringatan',
                    html: message || 'Harap periksa kembali data Anda',
                    showConfirmButton: true,
                    confirmButtonText: 'Mengerti'
                }, options || {})));
            },
            info: function (message, options) {
                return Swal.fire(buildPopupConfig(Object.assign({
                    icon: 'info',
                    title: 'Informasi',
                    html: message || 'Informasi terbaru',
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }, options || {})));
            },
            loading: function (message, options) {
                return Swal.fire(buildPopupConfig(Object.assign({
                    title: message || 'Memproses...',
                    html: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                }, options || {})));
            },
            closeLoading: function () {
                Swal.close();
            }
        };

        window.mstoreNotify.bindAutoLoading = function (root) {
            const scope = root || document;
            scope.querySelectorAll('form').forEach(function (form) {
                if (form.dataset.loadingBound === '1') {
                    return;
                }
                form.dataset.loadingBound = '1';
                form.addEventListener('submit', function (event) {
                    if (event.defaultPrevented) {
                        return;
                    }
                    const methodInput = form.querySelector('input[name="_method"]');
                    const isDeleteForm = methodInput && (methodInput.value || '').toUpperCase() === 'DELETE';
                    if (isDeleteForm && form.dataset.deleteConfirmed !== '1') {
                        return;
                    }
                    const method = (form.getAttribute('method') || 'get').toLowerCase();
                    if (method === 'get') {
                        return;
                    }
                    if (form.hasAttribute('data-no-loading') || form.dataset.noLoading === 'true') {
                        return;
                    }
                    if (form.hasAttribute('data-ajax') || form.dataset.ajax === 'true') {
                        return;
                    }
                    const submitter = event.submitter || document.activeElement;
                    if (submitter && (submitter.hasAttribute('data-no-loading') || submitter.dataset.noLoading === 'true')) {
                        return;
                    }
                    if (form.dataset.isSubmitting === '1') {
                        event.preventDefault();
                        return;
                    }
                    if (!form.checkValidity()) {
                        return;
                    }
                    form.dataset.isSubmitting = '1';
                    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
                        button.disabled = true;
                    });
                    window.mstoreNotify.loading(form.dataset.loadingMessage || 'Memproses data...');
                });
            });
        };

        window.mstoreNotify.bindAutoLoading(document);
        window.mstoreNotify.bindDeleteConfirm = function (root) {
            const scope = root || document;
            scope.querySelectorAll('form').forEach(function (form) {
                if (form.dataset.deleteConfirmBound === '1') {
                    return;
                }
                const methodInput = form.querySelector('input[name="_method"]');
                const isDeleteForm = methodInput && (methodInput.value || '').toUpperCase() === 'DELETE';
                if (!isDeleteForm) {
                    return;
                }
                form.dataset.deleteConfirmBound = '1';
                if (form.getAttribute('onsubmit')) {
                    form.removeAttribute('onsubmit');
                }
                form.addEventListener('submit', function (event) {
                    if (event.defaultPrevented) {
                        return;
                    }
                    if (form.hasAttribute('data-no-delete-confirm') || form.dataset.noDeleteConfirm === 'true') {
                        return;
                    }
                    if (form.dataset.deleteConfirmed === '1') {
                        return;
                    }
                    event.preventDefault();
                    Swal.fire(buildPopupConfig({
                        icon: 'warning',
                        title: 'Konfirmasi Hapus',
                        html: form.dataset.confirmMessage || 'Data yang dihapus tidak bisa dikembalikan.',
                        showCancelButton: true,
                        showConfirmButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        focusCancel: true
                    })).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }
                        form.dataset.deleteConfirmed = '1';
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                            return;
                        }
                        form.submit();
                    });
                });
            });
        };

        window.mstoreNotify.bindDeleteConfirm(document);
        window.mstoreNotify.showPageLoading = function (message) {
            const loader = document.getElementById('mstorePageLoader');
            if (!loader) {
                return;
            }
            const textElement = document.getElementById('mstorePageLoaderText');
            if (textElement && message) {
                textElement.textContent = message;
            }
            loader.classList.add('is-active');
            loader.setAttribute('aria-hidden', 'false');
        };

        window.mstoreNotify.hidePageLoading = function () {
            const loader = document.getElementById('mstorePageLoader');
            if (!loader) {
                return;
            }
            loader.classList.remove('is-active');
            loader.setAttribute('aria-hidden', 'true');
        };

        window.mstoreNotify.bindNavigationLoading = function (root) {
            const scope = root || document;
            scope.querySelectorAll('a[href]').forEach(function (link) {
                if (link.dataset.navLoadingBound === '1') {
                    return;
                }
                link.dataset.navLoadingBound = '1';
                link.addEventListener('click', function (event) {
                    if (event.defaultPrevented || event.button !== 0) {
                        return;
                    }
                    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }
                    const href = link.getAttribute('href') || '';
                    if (!href || href.charAt(0) === '#') {
                        return;
                    }
                    if (href.toLowerCase().indexOf('javascript:') === 0) {
                        return;
                    }
                    if (link.hasAttribute('download') || link.target === '_blank') {
                        return;
                    }
                    if (link.hasAttribute('data-no-loading') || link.dataset.noLoading === 'true') {
                        return;
                    }
                    if (link.hasAttribute('data-bs-toggle') || link.getAttribute('role') === 'button') {
                        return;
                    }
                    const targetUrl = link.href;
                    if (!targetUrl) {
                        return;
                    }
                    if (targetUrl.split('#')[0] === window.location.href.split('#')[0]) {
                        return;
                    }
                    event.preventDefault();
                    window.mstoreNotify.showPageLoading(link.dataset.loadingMessage || 'Membuka halaman...');
                    window.setTimeout(function () {
                        window.location.assign(targetUrl);
                    }, 60);
                });
            });
        };

        window.mstoreNotify.bindNavigationLoading(document);
        window.addEventListener('pageshow', function () {
            window.mstoreNotify.hidePageLoading();
        });

        @if($errors->any())
            window.mstoreNotify.error({!! json_encode('<ul class="text-start mb-0 ps-3"><li>' . implode('</li><li>', $errors->all()) . '</li></ul>') !!});
        @elseif(session('error'))
            window.mstoreNotify.error({!! json_encode(session('error')) !!});
        @elseif(session('warning'))
            window.mstoreNotify.warning({!! json_encode(session('warning')) !!});
        @elseif(session('info'))
            window.mstoreNotify.info({!! json_encode(session('info')) !!});
        @elseif(session('success'))
            window.mstoreNotify.success({!! json_encode(session('success')) !!});
        @endif
    })();
</script>

<!-- Custom Dashboard JS -->
<script src="{{ app()->environment('production') ? secure_asset('js/dashboard-custom.js') : asset('js/dashboard-custom.js') }}"></script>
<script src="{{ app()->environment('production') ? secure_asset('js/android-interact.js') : asset('js/android-interact.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.feather) {
            window.feather.replace();
        }

        const isMobileTableRoute = document.body.classList.contains('route-wash')
            || document.body.classList.contains('route-accounting')
            || document.body.classList.contains('route-finance')
            || document.body.classList.contains('route-inventory')
            || document.body.classList.contains('route-investors');

        if (!isMobileTableRoute) {
            return;
        }

        const mobile = window.matchMedia('(max-width: 768px)').matches;
        if (!mobile) {
            return;
        }

        document.querySelectorAll('.table-responsive table, .table-responsive-mobile table').forEach(function (table) {
            const wrapper = table.closest('.table-responsive, .table-responsive-mobile');
            if (wrapper) {
                wrapper.classList.add('table-responsive-mobile');
            }

            const headerCells = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                return (th.textContent || '').trim();
            });

            if (!headerCells.length) {
                return;
            }

            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.querySelectorAll('td').forEach(function (cell, index) {
                    if (!cell.dataset.label && headerCells[index]) {
                        cell.dataset.label = headerCells[index];
                    }
                });
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const showPasswordLabel = @json(__('Tampilkan password'));
        const passwordInputs = document.querySelectorAll('input[type="password"]');

        passwordInputs.forEach(function (input, index) {
            if (input.dataset.toggleReady === '1') {
                return;
            }

            input.dataset.toggleReady = '1';
            const existingGroup = input.closest('.input-group');
            if (existingGroup) {
                existingGroup.querySelectorAll('button[data-toggle-password], button[data-password-toggle-btn], button[onclick*="togglePassword"]').forEach(function (button) {
                    button.remove();
                });
            }

            if (input.parentElement) {
                input.parentElement.querySelectorAll('button[data-toggle-password], button[data-password-toggle-btn], button[onclick*="togglePassword"]').forEach(function (button) {
                    button.remove();
                });
            }

            const inputIdentifier = input.id && input.id.length ? input.id : ('password-field-' + index);
            const checkboxId = 'password-visibility-' + inputIdentifier.replace(/[^a-zA-Z0-9\-_]/g, '-') + '-' + index;
            const formCheck = document.createElement('div');
            formCheck.className = 'form-check mt-2';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.id = checkboxId;
            checkbox.setAttribute('data-password-checkbox-target', inputIdentifier);

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.setAttribute('for', checkboxId);
            label.textContent = showPasswordLabel;

            formCheck.appendChild(checkbox);
            formCheck.appendChild(label);
            const insertAfterElement = existingGroup || input;
            insertAfterElement.insertAdjacentElement('afterend', formCheck);

            checkbox.addEventListener('change', function () {
                input.type = checkbox.checked ? 'text' : 'password';
            });
        });
    });
</script>

@stack('scripts')

</body>
</html>
