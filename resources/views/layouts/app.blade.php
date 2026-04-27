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
    <link href="{{ app()->environment('production') ? secure_asset('css/mstore-components.css') : asset('css/mstore-components.css') }}" rel="stylesheet">

    @vite(['resources/js/app.js'])

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
            @php
                $authUser = Auth::user();
                if ($authUser) {
                    $authUser->loadMissing('role.permissions');
                }
                $isAdmin = $authUser ? $authUser->hasRole('admin') : false;
                $permissionMap = $isAdmin || ! $authUser
                    ? []
                    : (($authUser->role?->permissions?->pluck('name')->flip()->all()) ?? []);
                $hasPermission = static function (string $permission) use ($authUser, $isAdmin, $permissionMap): bool {
                    if (! $authUser) {
                        return false;
                    }

                    if ($isAdmin) {
                        return true;
                    }

                    return isset($permissionMap[$permission]);
                };
                $hasAnyPermission = static function (array $permissions) use ($hasPermission): bool {
                    foreach ($permissions as $permission) {
                        if ($hasPermission($permission)) {
                            return true;
                        }
                    }

                    return false;
                };
                $hasRole = static fn (string $role): bool => $authUser ? $authUser->hasRole($role) : false;
                $routeIs = static fn (...$patterns): bool => request()->routeIs(...$patterns);
                $unreadNotificationCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
                $unreadNotifications = $authUser
                    ? $authUser->unreadNotifications()->latest()->limit(10)->get()
                    : collect();
            @endphp

            {{-- User Panel (Simplified) --}}
           
            <div class="sidebar-header mt-2">{{ __('Menu Utama') }}</div>

            {{-- Dashboard --}}
            @if($hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}" class="sidebar-item {{ $routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa fa-tachometer-alt"></i> {{ __('Dasbor') }}
            </a>
            @endif

            {{-- AI Center --}}
            @if($hasPermission('ai.view'))
            <a href="{{ route('ai.index') }}" class="sidebar-item {{ $routeIs('ai.*') ? 'active' : '' }}">
                <i class="fa-solid fa-robot"></i> {{ __('Pusat AI') }} <span class="badge bg-primary ms-auto" style="font-size: 0.6rem;">BETA</span>
            </a>
            @endif

            @if($hasPermission('chat.view'))
            <a href="{{ route('chat.index') }}" class="sidebar-item {{ $routeIs('chat.*') ? 'active' : '' }}">
                <i class="fa-regular fa-comments"></i> {{ __('Messenger Internal') }}
            </a>
            @endif

            {{-- Client Portal (Grouped) --}}
            @if($hasRole('customer'))
            <div class="sidebar-header mt-2">{{ __('Portal Pelanggan') }}</div>
            @php
                $clientRoutesActive = $routeIs('client.*') || $routeIs('profile.edit') || $routeIs('profile.id_card');
            @endphp
            <a class="sidebar-item {{ $clientRoutesActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#clientPortalCollapse" role="button" aria-expanded="{{ $clientRoutesActive ? 'true' : 'false' }}" aria-controls="clientPortalCollapse">
                <i class="fa-solid fa-user-circle"></i> {{ __('Portal Pelanggan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $clientRoutesActive ? 'show' : '' }}" id="clientPortalCollapse">
                <div class="ps-2">
                    <a href="{{ route('client.portal') }}" class="sidebar-item {{ $routeIs('client.portal') ? 'active' : '' }}">
                        <i class="fa-solid fa-house-user"></i> {{ __('Beranda Portal') }}
                    </a>
                    <a href="{{ route('client.connection') }}" class="sidebar-item {{ $routeIs('client.connection') ? 'active' : '' }}">
                        <i class="fa fa-random"></i> {{ __('Info Koneksi') }}
                    </a>
                    <a href="{{ route('client.invoices.index') }}" class="sidebar-item {{ $routeIs('client.invoices.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> {{ __('Tagihan Saya') }}
                    </a>
                    <a href="{{ route('client.credentials.show') }}" class="sidebar-item {{ $routeIs('client.credentials.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-key"></i> {{ __('Kredensial Internet') }}
                    </a>
                    <a href="{{ route('profile.edit') }}" class="sidebar-item {{ $routeIs('profile.edit') ? 'active' : '' }}">
                        <i class="fa-regular fa-user"></i> {{ __('Profil Saya') }}
                    </a>
                    <a href="{{ route('profile.id_card') }}" class="sidebar-item {{ $routeIs('profile.id_card') ? 'active' : '' }}">
                        <i class="fa-regular fa-id-card"></i> {{ __('Kartu Identitas') }}
                    </a>
                    @php $mixUrl = \App\Models\Setting::getValue('mixradius_base_url', env('MIXRADIUS_BASE_URL', '')); @endphp
                    @if(!empty($mixUrl))
                    <a href="{{ route('client.mixradius') }}" class="sidebar-item {{ $routeIs('client.mixradius') ? 'active' : '' }}">
                        <i class="fa-solid fa-up-right-from-square"></i> {{ __('Portal MixRADIUS') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pelanggan & Layanan Group --}}
            @if(
                $hasPermission('customer.view') ||
                $hasPermission('installation.view') ||
                $hasPermission('hotspot.view') ||
                $hasPermission('router.view') ||
                $hasPermission('pppoe.view') ||
                $hasPermission('package.view')
            )
            <div class="sidebar-header mt-2">{{ __('Pelanggan & Layanan') }}</div>
            @php
                $customerDataActive = $routeIs('customers.*') || $routeIs('installations.*');
                $customerServiceActive = $routeIs('hotspot.index') || $routeIs('pppoe.index') || $routeIs('packages.*');
                $customerAnyActive = $customerDataActive || $customerServiceActive;
            @endphp

            <a class="sidebar-item {{ $customerAnyActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#customerCenterCollapse" role="button" aria-expanded="{{ $customerAnyActive ? 'true' : 'false' }}" aria-controls="customerCenterCollapse">
                <i class="fa-solid fa-users"></i> {{ __('Pusat Pelanggan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $customerAnyActive ? 'show' : '' }}" id="customerCenterCollapse">
                <div class="ps-3">
                    @if($hasPermission('customer.view') || $hasPermission('installation.view'))
                    <a class="sidebar-item {{ $customerDataActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#customerDataCollapse" role="button" aria-expanded="{{ $customerDataActive ? 'true' : 'false' }}" aria-controls="customerDataCollapse">
                        <i class="fa-solid fa-address-book"></i> {{ __('Data Master') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $customerDataActive ? 'show' : '' }}" id="customerDataCollapse">
                        <div class="ps-3">
                            @if($hasPermission('customer.view'))
                            <a href="{{ route('customers.index') }}" class="sidebar-item {{ $routeIs('customers.*') ? 'active' : '' }}">
                                <i class="fa fa-users"></i> {{ __('Data Pelanggan') }}
                            </a>
                            @endif
                            @if($hasPermission('installation.view'))
                            <a href="{{ route('installations.index') }}" class="sidebar-item {{ $routeIs('installations.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-screwdriver-wrench"></i> {{ __('Pemasangan Baru') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('hotspot.view') || $hasPermission('router.view') || $hasPermission('pppoe.view') || $hasPermission('package.view'))
                    <a class="sidebar-item {{ $customerServiceActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#customerServiceCollapse" role="button" aria-expanded="{{ $customerServiceActive ? 'true' : 'false' }}" aria-controls="customerServiceCollapse">
                        <i class="fa fa-wifi"></i> {{ __('Layanan Aktif') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $customerServiceActive ? 'show' : '' }}" id="customerServiceCollapse">
                        <div class="ps-3">
                            @if($hasPermission('hotspot.view'))
                            <a href="{{ route('hotspot.index') }}" class="sidebar-item {{ $routeIs('hotspot.index') ? 'active' : '' }}">
                                <i class="fa-solid fa-wifi"></i> {{ __('Hotspot Aktif') }}
                            </a>
                            @endif
                            @if($hasPermission('router.view') || $hasPermission('pppoe.view'))
                            <a href="{{ route('pppoe.index') }}" class="sidebar-item {{ $routeIs('pppoe.index') ? 'active' : '' }}">
                                <i class="fa-solid fa-globe"></i> {{ __('PPPoE Aktif') }}
                            </a>
                            @endif
                            @if($hasPermission('package.view'))
                            <a href="{{ route('packages.index') }}" class="sidebar-item {{ $routeIs('packages.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-cube"></i> {{ __('Paket Internet') }}
                            </a>
                            @endif
                            @if($hasPermission('hotspot.view'))
                            <a href="{{ route('vouchers.index') }}" class="sidebar-item {{ $routeIs('vouchers.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-ticket"></i> {{ __('Voucher Hotspot') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Jaringan Group --}}
            @if(
                $hasPermission('map.view') ||
                $hasPermission('genieacs.view') ||
                $hasPermission('genieacs_server.view') ||
                $hasPermission('calculator.view') ||
                $hasPermission('router.view') ||
                $hasPermission('olt.view') ||
                $hasPermission('odc.view') ||
                $hasPermission('odp.view') ||
                $hasPermission('closure.view') ||
                $hasPermission('htb.view')
            )
            <div class="sidebar-header mt-2">{{ __('Jaringan') }}</div>

            @php
                $networkMonitoringActive = $routeIs('map.*') || $routeIs('genieacs.*') || $routeIs('calculator.*') || $routeIs('network.analyzer');
                $networkAccessActive = $routeIs('routers.*') || $routeIs('vpn.*');
                $networkInfraActive = $routeIs('olt.*') || $routeIs('odcs.*') || $routeIs('odps.*') || $routeIs('closures.*') || $routeIs('htbs.*');
                $networkAnyActive = $networkMonitoringActive || $networkAccessActive || $networkInfraActive;
            @endphp

            <a class="sidebar-item {{ $networkAnyActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkCenterCollapse" role="button" aria-expanded="{{ $networkAnyActive ? 'true' : 'false' }}" aria-controls="networkCenterCollapse">
                <i class="fa-solid fa-diagram-project"></i> {{ __('Pusat Jaringan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $networkAnyActive ? 'show' : '' }}" id="networkCenterCollapse">
                <div class="ps-3">
                    @if($hasPermission('map.view') || $hasPermission('genieacs.view') || $hasPermission('genieacs_server.view') || $hasPermission('calculator.view') || $hasPermission('router.view'))
                    <a class="sidebar-item {{ $networkMonitoringActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkMonitoringCollapse" role="button" aria-expanded="{{ $networkMonitoringActive ? 'true' : 'false' }}" aria-controls="networkMonitoringCollapse">
                        <i class="fa-solid fa-satellite-dish"></i> {{ __('Monitoring & Tools') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $networkMonitoringActive ? 'show' : '' }}" id="networkMonitoringCollapse">
                        <div class="ps-3">
                            @if($hasPermission('map.view'))
                            <a href="{{ route('map.index') }}" class="sidebar-item {{ $routeIs('map.*') ? 'active' : '' }}">
                                <i class="fa fa-map-marked-alt"></i> {{ __('Peta Jaringan') }}
                            </a>
                            @endif
                            @if($hasPermission('genieacs.view') || $hasPermission('genieacs_server.view'))
                            <a href="{{ route('genieacs.index') }}" class="sidebar-item {{ $routeIs('genieacs.index') ? 'active' : '' }}">
                                <i class="fa-solid fa-network-wired"></i> {{ __('Monitor Jaringan') }}
                            </a>
                            @endif
                            @if($hasPermission('genieacs_server.view'))
                            <a href="{{ route('genieacs.servers.index') }}" class="sidebar-item {{ $routeIs('genieacs.servers.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-database"></i> {{ __('Server GenieACS') }}
                            </a>
                            @endif
                            @if($hasPermission('calculator.view'))
                            <a href="{{ route('calculator.pon') }}" class="sidebar-item {{ $routeIs('calculator.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-calculator"></i> {{ __('Kalkulator PON') }}
                            </a>
                            @endif
                            @if($hasPermission('router.view'))
                            <a href="{{ route('network.analyzer') }}" class="sidebar-item {{ $routeIs('network.analyzer') ? 'active' : '' }}">
                                <i class="fa-solid fa-gauge-high"></i> {{ __('Analisis Jaringan') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('router.view'))
                    <a class="sidebar-item {{ $networkAccessActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkAccessCollapse" role="button" aria-expanded="{{ $networkAccessActive ? 'true' : 'false' }}" aria-controls="networkAccessCollapse">
                        <i class="fa-solid fa-server"></i> {{ __('Perangkat & Akses') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $networkAccessActive ? 'show' : '' }}" id="networkAccessCollapse">
                        <div class="ps-3">
                            <a href="{{ route('routers.index') }}" class="sidebar-item {{ ($routeIs('routers.*') && !$routeIs('routers.sessions')) ? 'active' : '' }}">
                                <i class="fa fa-server"></i> {{ __('Router / NAS') }}
                            </a>
                            <a href="{{ route('vpn.servers.index') }}" class="sidebar-item {{ $routeIs('vpn.servers.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-shield-halved"></i> {{ __('VPN Bridge') }}
                            </a>
                            <a href="{{ route('vpn.guide') }}" class="sidebar-item {{ $routeIs('vpn.guide') ? 'active' : '' }}">
                                <i class="fa-regular fa-circle-question"></i> {{ __('Panduan VPN') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('olt.view') || $hasPermission('odc.view') || $hasPermission('odp.view') || $hasPermission('closure.view') || $hasPermission('htb.view'))
                    <a class="sidebar-item {{ $networkInfraActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#networkInfraCollapse" role="button" aria-expanded="{{ $networkInfraActive ? 'true' : 'false' }}" aria-controls="networkInfraCollapse">
                        <i class="fa fa-sitemap"></i> {{ __('Infrastruktur') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $networkInfraActive ? 'show' : '' }}" id="networkInfraCollapse">
                        <div class="ps-3">
                            @if($hasPermission('olt.view'))
                            <a href="{{ route('olt.index') }}" class="sidebar-item {{ $routeIs('olt.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-server"></i> {{ __('OLT') }}
                            </a>
                            @endif
                            @if($hasPermission('odc.view'))
                            <a href="{{ route('odcs.index') }}" class="sidebar-item {{ $routeIs('odcs.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-box-archive"></i> {{ __('ODC') }}
                            </a>
                            @endif
                            @if($hasPermission('odp.view'))
                            <a href="{{ route('odps.index') }}" class="sidebar-item {{ $routeIs('odps.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-box"></i> {{ __('ODP') }}
                            </a>
                            @endif
                            @if($hasPermission('closure.view'))
                            <a href="{{ route('closures.index') }}" class="sidebar-item {{ $routeIs('closures.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-box-open"></i> {{ __('Closure') }}
                            </a>
                            @endif
                            @if($hasPermission('htb.view'))
                            <a href="{{ route('htbs.index') }}" class="sidebar-item {{ $routeIs('htbs.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-network-wired"></i> {{ __('HTB') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @endif

            {{-- Keuangan Group --}}
            @if($hasPermission('finance.view') || $hasPermission('investor.view'))
            <div class="sidebar-header mt-2">{{ __('Keuangan') }}</div>
            @php
                $financeSummaryActive = $routeIs('finance.index') || $routeIs('finance.profit_loss*') || $routeIs('finance.material_report') || $routeIs('finance.manager_report*');
                $financeAccountingActive = $routeIs('accounting.*');
                $financeInvestorActive = $routeIs('finance.investor_report*') || $routeIs('investors.*');
                $financeAnyActive = $financeSummaryActive || $financeAccountingActive || $financeInvestorActive;
            @endphp

            <a class="sidebar-item {{ $financeAnyActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#financeCollapse" role="button" aria-expanded="{{ $financeAnyActive ? 'true' : 'false' }}" aria-controls="financeCollapse">
                <i class="fa fa-wallet"></i> {{ __('Pusat Keuangan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $financeAnyActive ? 'show' : '' }}" id="financeCollapse">
                <div class="ps-3">
                    @if($hasPermission('finance.view'))
                    <a class="sidebar-item {{ $financeSummaryActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#financeSummaryCollapse" role="button" aria-expanded="{{ $financeSummaryActive ? 'true' : 'false' }}" aria-controls="financeSummaryCollapse">
                        <i class="fa-solid fa-chart-line"></i> {{ __('Ringkasan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $financeSummaryActive ? 'show' : '' }}" id="financeSummaryCollapse">
                        <div class="ps-3">
                            <a href="{{ route('finance.index') }}" class="sidebar-item {{ $routeIs('finance.index') ? 'active' : '' }}">
                                <i class="fa-solid fa-chart-pie"></i> {{ __('Dasbor Keuangan') }}
                            </a>
                            <a href="{{ route('finance.profit_loss') }}" class="sidebar-item {{ $routeIs('finance.profit_loss*') ? 'active' : '' }}">
                                <i class="fa-solid fa-chart-line"></i> {{ __('Laba Rugi') }}
                            </a>
                            <a href="{{ route('finance.material_report') }}" class="sidebar-item {{ $routeIs('finance.material_report') ? 'active' : '' }}">
                                <i class="fa-solid fa-boxes-stacked"></i> {{ __('Laporan Material') }}
                            </a>
                            <a href="{{ route('finance.manager_report') }}" class="sidebar-item {{ $routeIs('finance.manager_report*') ? 'active' : '' }}">
                                <i class="fa-solid fa-user-tie"></i> {{ __('Laporan Manajer') }}
                            </a>
                        </div>
                    </div>

                    <a class="sidebar-item {{ $financeAccountingActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#financeAccountingCollapse" role="button" aria-expanded="{{ $financeAccountingActive ? 'true' : 'false' }}" aria-controls="financeAccountingCollapse">
                        <i class="fa-solid fa-book-open"></i> {{ __('Akuntansi') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $financeAccountingActive ? 'show' : '' }}" id="financeAccountingCollapse">
                        <div class="ps-3">
                            <a href="{{ route('accounting.trial_balance') }}" class="sidebar-item {{ $routeIs('accounting.trial_balance') ? 'active' : '' }}">
                                <i class="fa-regular fa-file-lines"></i> {{ __('Neraca Saldo') }}
                            </a>
                            <a href="{{ route('accounting.income_statement') }}" class="sidebar-item {{ $routeIs('accounting.income_statement') ? 'active' : '' }}">
                                <i class="fa-regular fa-file-lines"></i> {{ __('Laba Rugi') }}
                            </a>
                            <a href="{{ route('accounting.balance_sheet') }}" class="sidebar-item {{ $routeIs('accounting.balance_sheet') ? 'active' : '' }}">
                                <i class="fa-regular fa-file-lines"></i> {{ __('Neraca') }}
                            </a>
                            <a href="{{ route('accounting.ledger') }}" class="sidebar-item {{ $routeIs('accounting.ledger') ? 'active' : '' }}">
                                <i class="fa-regular fa-file-lines"></i> {{ __('Buku Besar') }}
                            </a>
                            <a href="{{ route('accounting.cash_flow') }}" class="sidebar-item {{ $routeIs('accounting.cash_flow') ? 'active' : '' }}">
                                <i class="fa-regular fa-file-lines"></i> {{ __('Arus Kas') }}
                            </a>
                            <a href="{{ route('accounting.periods.index') }}" class="sidebar-item {{ $routeIs('accounting.periods.*') ? 'active' : '' }}">
                                <i class="fa-regular fa-calendar-check"></i> {{ __('Periode Akuntansi') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('investor.view'))
                    <a class="sidebar-item {{ $financeInvestorActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#financeInvestorCollapse" role="button" aria-expanded="{{ $financeInvestorActive ? 'true' : 'false' }}" aria-controls="financeInvestorCollapse">
                        <i class="fa-solid fa-hand-holding-dollar"></i> {{ __('Investor') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $financeInvestorActive ? 'show' : '' }}" id="financeInvestorCollapse">
                        <div class="ps-3">
                            <a href="{{ route('finance.investor_report') }}" class="sidebar-item {{ $routeIs('finance.investor_report*') ? 'active' : '' }}">
                                <i class="fa-solid fa-file-invoice-dollar"></i> {{ __('Laporan Investor') }}
                            </a>
                            <a href="{{ route('investors.index') }}" class="sidebar-item {{ $routeIs('investors.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-hand-holding-dollar"></i> {{ __('Data Investor') }}
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Toko ATK Group --}}
            @if($hasPermission('atk.view') || $hasPermission('atk.pos'))
            <div class="sidebar-header mt-2">{{ __('Toko ATK') }}</div>

            @php
                $atkDashboardActive = $routeIs('atk.dashboard');
                $atkMasterActive = $routeIs('atk.products.*');
                $atkTransactionActive = $routeIs('atk.pos') || $routeIs('atk.transactions.*');
                $atkFinanceActive = $routeIs('atk.expenses.*') || $routeIs('atk.reports.*');
                $atkAnyActive = $atkDashboardActive || $atkMasterActive || $atkTransactionActive || $atkFinanceActive;
            @endphp

            <a class="sidebar-item {{ $atkAnyActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#atkCollapse" role="button" aria-expanded="{{ $atkAnyActive ? 'true' : 'false' }}" aria-controls="atkCollapse">
                <i class="fa fa-store"></i> {{ __('Pusat Toko ATK') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $atkAnyActive ? 'show' : '' }}" id="atkCollapse">
                <div class="ps-3">
                    @if($hasPermission('atk.view'))
                    <a href="{{ route('atk.dashboard') }}" class="sidebar-item {{ $atkDashboardActive ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i> {{ __('Dasbor') }}
                    </a>
                    @endif

                    @if($hasPermission('atk.manage'))
                    <a class="sidebar-item {{ $atkMasterActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#atkMasterCollapse" role="button" aria-expanded="{{ $atkMasterActive ? 'true' : 'false' }}" aria-controls="atkMasterCollapse">
                        <i class="fa-solid fa-boxes-stacked"></i> {{ __('Data Master') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $atkMasterActive ? 'show' : '' }}" id="atkMasterCollapse">
                        <div class="ps-3">
                            <a href="{{ route('atk.products.index') }}" class="sidebar-item {{ $routeIs('atk.products.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-boxes-stacked"></i> {{ __('Produk & Stok') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('atk.pos') || $hasPermission('atk.report'))
                    <a class="sidebar-item {{ $atkTransactionActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#atkTransactionCollapse" role="button" aria-expanded="{{ $atkTransactionActive ? 'true' : 'false' }}" aria-controls="atkTransactionCollapse">
                        <i class="fa-solid fa-cash-register"></i> {{ __('Transaksi') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $atkTransactionActive ? 'show' : '' }}" id="atkTransactionCollapse">
                        <div class="ps-3">
                            @if($hasPermission('atk.pos'))
                            <a href="{{ route('atk.pos') }}" class="sidebar-item {{ $routeIs('atk.pos') ? 'active' : '' }}">
                                <i class="fa-solid fa-cash-register"></i> {{ __('Kasir (POS)') }}
                            </a>
                            @endif
                            @if($hasPermission('atk.report'))
                            <a href="{{ route('atk.transactions.index') }}" class="sidebar-item {{ $routeIs('atk.transactions.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-history"></i> {{ __('Riwayat Transaksi') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('atk.manage') || $hasPermission('atk.report'))
                    <a class="sidebar-item {{ $atkFinanceActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#atkFinanceCollapse" role="button" aria-expanded="{{ $atkFinanceActive ? 'true' : 'false' }}" aria-controls="atkFinanceCollapse">
                        <i class="fa-solid fa-chart-line"></i> {{ __('Laporan & Biaya') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $atkFinanceActive ? 'show' : '' }}" id="atkFinanceCollapse">
                        <div class="ps-3">
                            @if($hasPermission('atk.manage'))
                            <a href="{{ route('atk.expenses.index') }}" class="sidebar-item {{ $routeIs('atk.expenses.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-receipt"></i> {{ __('Pengeluaran') }}
                            </a>
                            @endif
                            @if($hasPermission('atk.report'))
                            <a href="{{ route('atk.reports.index') }}" class="sidebar-item {{ $routeIs('atk.reports.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-chart-line"></i> {{ __('Laporan') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Car Wash Group --}}
            @if($hasPermission('wash.view') || $hasPermission('wash.pos') || $hasPermission('wash.manage') || $hasPermission('wash.report'))
            <div class="sidebar-header mt-2">{{ __('Cuci Kendaraan') }}</div>

            @php
                $washDashboardActive = $routeIs('wash.dashboard');
                $washMasterActive = $routeIs('wash.services.*');
                $washTransactionActive = $routeIs('wash.pos') || $routeIs('wash.transactions.*');
                $washFinanceActive = $routeIs('wash.expenses.*') || $routeIs('wash.reports.*');
                $washAnyActive = $washDashboardActive || $washMasterActive || $washTransactionActive || $washFinanceActive;
            @endphp

            <a class="sidebar-item {{ $washAnyActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#washCollapse" role="button" aria-expanded="{{ $washAnyActive ? 'true' : 'false' }}" aria-controls="washCollapse">
                <i class="fa fa-car"></i> {{ __('Pusat Car Wash') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $washAnyActive ? 'show' : '' }}" id="washCollapse">
                <div class="ps-3">
                    @if($hasPermission('wash.view'))
                    <a href="{{ route('wash.dashboard') }}" class="sidebar-item {{ $washDashboardActive ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i> {{ __('Dasbor') }}
                    </a>
                    @endif

                    @if($hasPermission('wash.manage'))
                    <a class="sidebar-item {{ $washMasterActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#washMasterCollapse" role="button" aria-expanded="{{ $washMasterActive ? 'true' : 'false' }}" aria-controls="washMasterCollapse">
                        <i class="fa-solid fa-tags"></i> {{ __('Data Master') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $washMasterActive ? 'show' : '' }}" id="washMasterCollapse">
                        <div class="ps-3">
                            <a href="{{ route('wash.services.index') }}" class="sidebar-item {{ $routeIs('wash.services.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-tags"></i> {{ __('Layanan & Harga') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('wash.pos') || $hasPermission('wash.report'))
                    <a class="sidebar-item {{ $washTransactionActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#washTransactionCollapse" role="button" aria-expanded="{{ $washTransactionActive ? 'true' : 'false' }}" aria-controls="washTransactionCollapse">
                        <i class="fa-solid fa-cash-register"></i> {{ __('Transaksi') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $washTransactionActive ? 'show' : '' }}" id="washTransactionCollapse">
                        <div class="ps-3">
                            @if($hasPermission('wash.pos'))
                            <a href="{{ route('wash.pos') }}" class="sidebar-item {{ $routeIs('wash.pos') ? 'active' : '' }}">
                                <i class="fa-solid fa-cash-register"></i> {{ __('Kasir (POS)') }}
                            </a>
                            @endif
                            @if($hasPermission('wash.report'))
                            <a href="{{ route('wash.transactions.index') }}" class="sidebar-item {{ $routeIs('wash.transactions.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-history"></i> {{ __('Riwayat Transaksi') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($hasPermission('wash.manage') || $hasPermission('wash.report'))
                    <a class="sidebar-item {{ $washFinanceActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#washFinanceCollapse" role="button" aria-expanded="{{ $washFinanceActive ? 'true' : 'false' }}" aria-controls="washFinanceCollapse">
                        <i class="fa-solid fa-chart-line"></i> {{ __('Laporan & Biaya') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $washFinanceActive ? 'show' : '' }}" id="washFinanceCollapse">
                        <div class="ps-3">
                            @if($hasPermission('wash.manage'))
                            <a href="{{ route('wash.expenses.index') }}" class="sidebar-item {{ $routeIs('wash.expenses.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-receipt"></i> {{ __('Pengeluaran') }}
                            </a>
                            @endif
                            @if($hasPermission('wash.report'))
                            <a href="{{ route('wash.reports.index') }}" class="sidebar-item {{ $routeIs('wash.reports.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-chart-line"></i> {{ __('Laporan') }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Operasional Group (guarded by permissions) --}}
            @if(
                $hasPermission('ticket.view') ||
                $hasPermission('inventory.view') ||
                $hasPermission('employee.view') ||
                $hasPermission('attendance.view') ||
                $hasPermission('attendance.report') ||
                $hasPermission('schedule.view') ||
                $hasPermission('leave.view')
            )
                <div class="sidebar-header mt-2">{{ __('Operasional') }}</div>

                @if($hasPermission('ticket.view'))
                <a href="{{ route('tickets.index') }}" class="sidebar-item {{ $routeIs('tickets.*') ? 'active' : '' }}">
                    <i class="fa fa-ticket-alt"></i> {{ __('Tiket & Gangguan') }}
                </a>
                <a href="{{ route('modem-data.index') }}" class="sidebar-item {{ $routeIs('modem-data.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wifi"></i> {{ __('Pendataan Modem') }}
                </a>
                @endif

                <a class="sidebar-item {{ ($routeIs('employees.*') || $routeIs('attendance.*') || $routeIs('schedules.*') || $routeIs('leave-requests.*') || $routeIs('settings.attendance.*')) ? 'active' : '' }}" data-bs-toggle="collapse" href="#opsSdmCollapse" role="button" aria-expanded="{{ ($routeIs('employees.*') || $routeIs('attendance.*') || $routeIs('schedules.*') || $routeIs('leave-requests.*') || $routeIs('settings.attendance.*')) ? 'true' : 'false' }}" aria-controls="opsSdmCollapse">
                    <i class="fa-solid fa-users-gear"></i> {{ __('SDM & Kehadiran') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                </a>
                <div class="collapse {{ ($routeIs('employees.*') || $routeIs('attendance.*') || $routeIs('schedules.*') || $routeIs('leave-requests.*') || $routeIs('settings.attendance.*')) ? 'show' : '' }}" id="opsSdmCollapse">
                    <div class="ps-3">
                        @if($hasPermission('employee.view'))
                        <a href="{{ route('employees.index') }}" class="sidebar-item {{ $routeIs('employees.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i> {{ __('Data Karyawan') }}
                        </a>
                        @endif
                        @if($hasPermission('attendance.view'))
                        <a href="{{ route('attendance.index', ['view_my' => 1]) }}" class="sidebar-item {{ $routeIs('attendance.*') && request('view_my') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-clock"></i> {{ __('Absensi Saya') }}
                        </a>
                        @endif
                        @if($hasPermission('attendance.report'))
                        <a href="{{ route('attendance.index') }}" class="sidebar-item {{ $routeIs('attendance.*') && !request('view_my') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-user"></i> {{ __('Rekap Absensi') }}
                        </a>
                        @endif
                        @if($hasPermission('schedule.view'))
                        <a href="{{ route('schedules.index') }}" class="sidebar-item {{ $routeIs('schedules.*') ? 'active' : '' }}">
                            <i class="fa-regular fa-calendar-alt"></i> {{ __('Jadwal Teknisi') }}
                        </a>
                        @endif
                        @if($hasPermission('setting.view'))
                        <a href="{{ route('settings.attendance.index') }}" class="sidebar-item {{ $routeIs('settings.attendance.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-sliders"></i> {{ __('Pengaturan Absensi') }}
                        </a>
                        @endif
                        @if($hasPermission('leave.view'))
                        <a href="{{ route('leave-requests.index') }}" class="sidebar-item {{ $routeIs('leave-requests.*') ? 'active' : '' }}">
                            <i class="fa-regular fa-envelope-open"></i> {{ __('Cuti / Izin') }}
                        </a>
                        @endif
                    </div>
                </div>

                @if($hasPermission('inventory.view'))
                <a class="sidebar-item {{ $routeIs('inventory.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#opsAssetCollapse" role="button" aria-expanded="{{ $routeIs('inventory.*') ? 'true' : 'false' }}" aria-controls="opsAssetCollapse">
                    <i class="fa fa-tools"></i> {{ __('Aset & Peralatan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                </a>
                <div class="collapse {{ $routeIs('inventory.*') ? 'show' : '' }}" id="opsAssetCollapse">
                    <div class="ps-3">
                        <a href="{{ route('inventory.index') }}" class="sidebar-item {{ $routeIs('inventory.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-toolbox"></i> {{ __('Inventaris / Peralatan') }}
                        </a>
                        <a href="{{ route('inventory.my_assets') }}" class="sidebar-item {{ $routeIs('inventory.my_assets') ? 'active' : '' }}">
                            <i class="fa-solid fa-box-open"></i> {{ __('Aset Saya') }}
                        </a>
                        <a href="{{ route('inventory.pickup') }}" class="sidebar-item {{ $routeIs('inventory.pickup*') ? 'active' : '' }}">
                            <i class="fa-solid fa-hand-holding"></i> {{ __('Pengambilan Barang') }}
                        </a>
                    </div>
                </div>
                @endif
            @endif

            {{-- Sistem Group --}}
            @if($hasPermission('setting.view') || $hasPermission('user.view'))
            <div class="sidebar-header mt-2">{{ __('Sistem') }}</div>

            @php
                $attendanceSettingsRoute = $routeIs('settings.attendance.*');
                $systemActive = ($routeIs('settings.*') && ! $attendanceSettingsRoute) || $routeIs('users.*') || $routeIs('roles.*') || $routeIs('regions.*') || $routeIs('coordinators.*') || $routeIs('whatsapp.*') || $routeIs('telegram.*') || $routeIs('apikeys.*');
                $settingsAreaActive = (($routeIs('settings.*') && ! $attendanceSettingsRoute) || $routeIs('regions.*') || $routeIs('coordinators.*'));
                $userAreaActive = $routeIs('users.*') || $routeIs('roles.*');
                $integrationAreaActive = $routeIs('whatsapp.*') || $routeIs('telegram.*') || $routeIs('apikeys.*');
            @endphp

            <a class="sidebar-item {{ $systemActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#systemCollapse" role="button" aria-expanded="{{ $systemActive ? 'true' : 'false' }}" aria-controls="systemCollapse">
                <i class="fa fa-cogs"></i> {{ __('Konfigurasi Sistem') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
            </a>
            <div class="collapse {{ $systemActive ? 'show' : '' }}" id="systemCollapse">
                <div class="ps-3">
                    <a class="sidebar-item {{ $settingsAreaActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#systemSettingsCollapse" role="button" aria-expanded="{{ $settingsAreaActive ? 'true' : 'false' }}" aria-controls="systemSettingsCollapse">
                        <i class="fa-solid fa-sliders"></i> {{ __('Pengaturan') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $settingsAreaActive ? 'show' : '' }}" id="systemSettingsCollapse">
                        <div class="ps-3">
                            @if($hasPermission('setting.view'))
                            <a href="{{ route('settings.index') }}" class="sidebar-item {{ $routeIs('settings.index') ? 'active' : '' }}">
                                <i class="fa-solid fa-sliders"></i> {{ __('Pengaturan Umum') }}
                            </a>
                            <a href="{{ route('settings.atk.index') }}" class="sidebar-item {{ $routeIs('settings.atk.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-receipt"></i> {{ __('Pengaturan ATK') }}
                            </a>
                            <a href="{{ route('settings.wash.index') }}" class="sidebar-item {{ $routeIs('settings.wash.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-soap"></i> {{ __('Pengaturan Wash') }}
                            </a>
                            @endif
                            @if($hasPermission('region.view'))
                            <a href="{{ route('regions.index') }}" class="sidebar-item {{ $routeIs('regions.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-map-location-dot"></i> {{ __('Wilayah') }}
                            </a>
                            @endif
                            @if($hasPermission('coordinator.view'))
                            <a href="{{ route('coordinators.index') }}" class="sidebar-item {{ $routeIs('coordinators.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-user-tie"></i> {{ __('Data Pengurus') }}
                            </a>
                            @endif
                        </div>
                    </div>

                    <a class="sidebar-item {{ $userAreaActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#systemUserCollapse" role="button" aria-expanded="{{ $userAreaActive ? 'true' : 'false' }}" aria-controls="systemUserCollapse">
                        <i class="fa-solid fa-users-gear"></i> {{ __('Manajemen Pengguna') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $userAreaActive ? 'show' : '' }}" id="systemUserCollapse">
                        <div class="ps-3">
                            @if($hasPermission('user.view'))
                            <a href="{{ route('users.index') }}" class="sidebar-item {{ $routeIs('users.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-users-gear"></i> {{ __('Manajemen User') }}
                            </a>
                            @endif
                            @if($hasPermission('role.view'))
                            <a href="{{ route('roles.index') }}" class="sidebar-item {{ $routeIs('roles.*') ? 'active' : '' }}">
                                <i class="fa-regular fa-id-card"></i> {{ __('Manajemen Peran') }}
                            </a>
                            @endif
                        </div>
                    </div>

                    <a class="sidebar-item {{ $integrationAreaActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#systemIntegrationCollapse" role="button" aria-expanded="{{ $integrationAreaActive ? 'true' : 'false' }}" aria-controls="systemIntegrationCollapse">
                        <i class="fa-solid fa-plug"></i> {{ __('Integrasi') }} <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i>
                    </a>
                    <div class="collapse {{ $integrationAreaActive ? 'show' : '' }}" id="systemIntegrationCollapse">
                        <div class="ps-3">
                            @if($hasPermission('chat.view'))
                            <a href="{{ route('whatsapp.index') }}" class="sidebar-item {{ $routeIs('whatsapp.*') ? 'active' : '' }}">
                                <i class="fa-brands fa-whatsapp"></i> {{ __('API WhatsApp') }}
                            </a>
                            @endif
                            @if($hasPermission('telegram.view'))
                            <a href="{{ route('telegram.index') }}" class="sidebar-item {{ $routeIs('telegram.*') ? 'active' : '' }}">
                                <i class="fa-brands fa-telegram"></i> {{ __('Telegram') }}
                            </a>
                            @endif
                            @if($hasPermission('apikey.view'))
                            <a href="{{ route('apikeys.index') }}" class="sidebar-item {{ $routeIs('apikeys.*') ? 'active' : '' }}">
                                <i class="fa-regular fa-circle"></i> {{ __('API Google Maps') }}
                            </a>
                            @endif
                        </div>
                    </div>
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
                        @if($unreadNotificationCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $unreadNotificationCount }}
                                <span class="visually-hidden">{{ __('pesan belum dibaca') }}</span>
                            </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-0" style="width: 300px; max-height: 400px; overflow-y: auto;">
                        <li><span class="dropdown-header border-bottom py-2 bg-body-tertiary">{{ __('Notifikasi') }}</span></li>
                        @forelse($unreadNotifications as $notification)
                            <li>
                                <a class="dropdown-item py-2 border-bottom" href="{{ route('notifications.redirect', $notification->id) }}">
                                    <div class="small fw-bold">{{ $notification->data['subject'] ?? 'Notifikasi' }}</div>
                                    <div class="small text-muted text-truncate">{{ $notification->data['message'] ?? '' }}</div>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">{{ $notification->created_at->diffForHumans() }}</div>
                                </a>
                            </li>
                        @empty
                            <li class="text-center py-3 text-muted small">{{ __('Tidak ada notifikasi baru') }}</li>
                        @endforelse
                        @if($unreadNotificationCount > 0)
                            <li>
                                <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-center small text-primary py-2 w-100 bg-transparent border-0">
                                        {{ __('Tandai semua sudah dibaca') }}
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
                        <li><span class="dropdown-header text-uppercase small">{{ __('Akun') }}</span></li>
                        <li><a class="dropdown-item" href="{{ route('landing') }}"><i class="fa-solid fa-globe me-2"></i> {{ __('Halaman Landing') }}</a></li>
                        @if($hasPermission('profile.view'))
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fa-regular fa-user me-2"></i> {{ __('Profil') }}</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> {{ __('Keluar') }}
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
                    const loadingDelay = Number(form.dataset.loadingDelay || 300);
                    window.setTimeout(function () {
                        if (form.dataset.isSubmitting !== '1') {
                            return;
                        }
                        window.mstoreNotify.loading(form.dataset.loadingMessage || 'Memproses data...');
                    }, Number.isFinite(loadingDelay) ? loadingDelay : 300);
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
            const looksLikeFileDownload = function (url) {
                if (!url) {
                    return false;
                }
                const normalized = (url || '').toLowerCase();
                if (normalized.indexOf('/export/') !== -1 || normalized.indexOf('-export/') !== -1) {
                    return true;
                }
                if (normalized.indexOf('download=') !== -1) {
                    return true;
                }
                return /\.(csv|xlsx|xls|pdf)(\?|#|$)/i.test(normalized);
            };
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
                    if (looksLikeFileDownload(href)) {
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

<script>
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if ((form.enctype || '').toLowerCase() !== 'multipart/form-data') return;

        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton || submitButton.dataset.uploadLocked === '1') return;

        submitButton.dataset.uploadLocked = '1';
        submitButton.disabled = true;

        if (submitButton.tagName === 'BUTTON') {
            submitButton.dataset.originalText = submitButton.textContent || '';
            submitButton.textContent = 'Mengunggah...';
        } else if (submitButton.tagName === 'INPUT') {
            submitButton.dataset.originalValue = submitButton.value || '';
            submitButton.value = 'Mengunggah...';
        }
    });
</script>

@auth
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const endpoint = @json(route('presence.ping'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (!endpoint || !csrfToken) return;

        const sendPresencePing = () => {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ping: true }),
            }).catch(() => {});
        };

        sendPresencePing();
        setInterval(sendPresencePing, 25000);
    });
</script>
@endauth

@stack('scripts')

</body>
</html>

