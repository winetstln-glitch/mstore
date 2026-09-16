<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Mstore Gt Wash'))</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ app()->environment('production') ? secure_asset('favicon.svg') : asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ app()->environment('production') ? secure_asset('favicon.ico') : asset('favicon.ico') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
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
<body class="route-{{ Str::slug(request()->segment(1) ?? 'home') }}">
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
            <button class="btn btn-link position-absolute top-0 end-0 me-2 d-lg-none" id="sidebarClose" style="z-index: 1051;" aria-label="Tutup Sidebar">
                <i class="fa-solid fa-times fa-lg"></i>
            </button>
        </div>
        <div class="px-3 pb-2">
            <input type="text" class="form-control form-control-sm" id="sidebarSearch" placeholder="Cari menu..." autocomplete="off">
        </div>
        <div class="list-group list-group-flush pb-2">
            @php
                $authUser = auth()->user();
                $isAdmin = $authUser && $authUser->isSuperAdmin();
                $permissionMap = $permissionMap ?? [];
                $sidebarMenu = $sidebarMenu ?? [];
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

                $nodeVisible = static function (array $node) use ($hasAnyPermission, $hasRole, &$nodeVisible): bool {
                    $roles = $node['roles'] ?? [];
                    if (is_array($roles) && count($roles) > 0) {
                        $ok = false;
                        foreach ($roles as $r) {
                            if ($hasRole((string) $r)) {
                                $ok = true;
                                break;
                            }
                        }
                        if (! $ok) {
                            return false;
                        }
                    }

                    $permissions = $node['permissions'] ?? [];
                    if (is_array($permissions) && count($permissions) > 0) {
                        if (! $hasAnyPermission($permissions)) {
                            return false;
                        }
                    }

                    if (($node['type'] ?? null) === 'group') {
                        foreach (($node['children'] ?? []) as $child) {
                            if ($nodeVisible($child)) {
                                return true;
                            }
                        }
                        return false;
                    }

                    return true;
                };

                $nodeActive = static function (array $node) use ($routeIs, &$nodeActive): bool {
                    $patterns = $node['route_patterns'] ?? [];
                    if (! is_array($patterns)) {
                        $patterns = [];
                    }

                    $routeName = $node['route'] ?? null;
                    if (is_string($routeName) && $routeName !== '') {
                        $patterns[] = $routeName;
                    }

                    if (($node['type'] ?? null) === 'link') {
                        return count($patterns) > 0 ? $routeIs(...$patterns) : false;
                    }

                    foreach (($node['children'] ?? []) as $child) {
                        if ($nodeActive($child)) {
                            return true;
                        }
                    }

                    return false;
                };

                $renderNodes = static function (array $nodes) use (&$renderNodes, $nodeVisible, $nodeActive): string {
                    $html = '';

                    foreach ($nodes as $node) {
                        if (! is_array($node)) {
                            continue;
                        }
                        if (! $nodeVisible($node)) {
                            continue;
                        }

                        $type = $node['type'] ?? null;
                        $label = (string) ($node['label'] ?? '');
                        $active = $nodeActive($node);
                        $icon = trim((string) ($node['icon'] ?? ''));
                        $iconHtml = $icon !== '' ? '<i class="'.e($icon).'"></i>' : '';

                        if ($type === 'link') {
                            $routeName = (string) ($node['route'] ?? '');
                            $routeParams = $node['route_params'] ?? [];
                            $href = \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName, $routeParams) : '#';
                            $html .= '<a href="'.e($href).'" class="sidebar-item'.($active ? ' active' : '').'">'.$iconHtml.'<span>'.e($label).'</span></a>';
                            continue;
                        }

                        if ($type === 'group') {
                            $id = (string) ($node['id'] ?? uniqid('grp_', false));
                            $collapseId = 'sidebarCollapse_'.$id;
                            $html .= '<a class="sidebar-item'.($active ? ' active' : '').'" data-bs-toggle="collapse" href="#'.e($collapseId).'" role="button" aria-expanded="'.($active ? 'true' : 'false').'" aria-controls="'.e($collapseId).'">'.$iconHtml.'<span>'.e($label).'</span><i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.8em;"></i></a>';
                            $html .= '<div class="collapse'.($active ? ' show' : '').'" id="'.e($collapseId).'"><div class="ps-3">'.$renderNodes($node['children'] ?? []).'</div></div>';
                        }
                    }

                    return $html;
                };
            @endphp

            @foreach($sidebarMenu as $section)
                @php
                    $sectionRoles = $section['roles'] ?? [];
                    $sectionVisible = true;
                    if (is_array($sectionRoles) && count($sectionRoles) > 0) {
                        $sectionVisible = false;
                        foreach ($sectionRoles as $r) {
                            if ($hasRole((string) $r)) {
                                $sectionVisible = true;
                                break;
                            }
                        }
                    }

                    if ($sectionVisible) {
                        $hasAnyItem = false;
                        foreach (($section['items'] ?? []) as $it) {
                            if ($nodeVisible($it)) {
                                $hasAnyItem = true;
                                break;
                            }
                        }
                        $sectionVisible = $sectionVisible && $hasAnyItem;
                    }
                @endphp
                @if($sectionVisible)
                    <div class="sidebar-header mt-2">{{ __($section['label'] ?? '') }}</div>
                    {!! $renderNodes($section['items'] ?? []) !!}
                @endif
            @endforeach

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
                                    <div class="small fw-bold">{{ strip_tags($notification->data['subject'] ?? 'Notifikasi') }}</div>
                                    <div class="small text-muted text-truncate">{{ strip_tags($notification->data['message'] ?? '') }}</div>
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
                        @php
                            $avatarPath = Auth::user()->avatar ?? '';
                            $avatarUrl = $avatarPath && !str_starts_with($avatarPath, 'http') && !str_contains($avatarPath, '..')
                                ? asset('storage/' . $avatarPath)
                                : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&background=3f6ad8&color=fff';
                        @endphp
                        <img src="{{ $avatarUrl }}" alt="Avatar" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
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
        const input = document.getElementById('sidebarSearch');
        if (!input) {
            return;
        }

        const sidebar = document.getElementById('sidebar-wrapper');
        if (!sidebar) {
            return;
        }

        const items = Array.from(sidebar.querySelectorAll('a.sidebar-item'));
        const headers = Array.from(sidebar.querySelectorAll('.sidebar-header'));
        const collapses = Array.from(sidebar.querySelectorAll('.collapse'));
        collapses.forEach(function (el) {
            el.dataset.initialShow = el.classList.contains('show') ? '1' : '0';
        });

        const normalize = function (text) {
            return (text || '')
                .toString()
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
        };

        const collapseInstance = function (el) {
            return bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
        };

        const setCollapseState = function (el, show) {
            const instance = collapseInstance(el);
            if (show) {
                instance.show();
            } else {
                instance.hide();
            }
        };

        const leafMatchesQuery = function (el, q) {
            const label = normalize(el.textContent);
            return label.includes(q);
        };

        const updateHeaders = function () {
            headers.forEach(function (header) {
                let sibling = header.nextElementSibling;
                let hasVisibleItem = false;
                while (sibling && !sibling.classList.contains('sidebar-header')) {
                    const visibleLink = sibling.querySelector && sibling.querySelector('a.sidebar-item:not(.d-none)');
                    if (visibleLink) {
                        hasVisibleItem = true;
                        break;
                    }
                    if (sibling.matches && sibling.matches('a.sidebar-item') && !sibling.classList.contains('d-none')) {
                        hasVisibleItem = true;
                        break;
                    }
                    sibling = sibling.nextElementSibling;
                }
                header.classList.toggle('d-none', !hasVisibleItem);
            });
        };

        const applyFilter = function () {
            const q = normalize(input.value);
            if (q === '') {
                items.forEach(function (el) {
                    el.classList.remove('d-none');
                });
                headers.forEach(function (el) {
                    el.classList.remove('d-none');
                });
                collapses.forEach(function (el) {
                    setCollapseState(el, el.dataset.initialShow === '1');
                });
                return;
            }

            collapses.forEach(function (el) {
                setCollapseState(el, true);
            });

            const leafItems = items.filter(function (el) {
                return !el.hasAttribute('data-bs-toggle');
            });
            const toggleItems = items.filter(function (el) {
                return el.getAttribute('data-bs-toggle') === 'collapse';
            });

            const leafVisible = new Set();
            leafItems.forEach(function (el) {
                const match = leafMatchesQuery(el, q);
                if (match) {
                    leafVisible.add(el);
                }
                el.classList.toggle('d-none', !match);
            });

            toggleItems.forEach(function (toggle) {
                const matchSelf = leafMatchesQuery(toggle, q);
                let matchChild = false;

                const href = toggle.getAttribute('href') || '';
                if (href.startsWith('#')) {
                    const target = sidebar.querySelector(href);
                    if (target) {
                        const descendant = Array.from(target.querySelectorAll('a.sidebar-item')).find(function (a) {
                            return !a.hasAttribute('data-bs-toggle') && !a.classList.contains('d-none');
                        });
                        matchChild = Boolean(descendant);
                    }
                }

                toggle.classList.toggle('d-none', !matchSelf && !matchChild);
            });

            updateHeaders();
        };

        input.addEventListener('input', applyFilter);
        applyFilter();
    });
</script>

{{-- ========================================== --}}
{{-- SCRIPT 1: MOBILE MENU TOGGLE --}}
{{-- ========================================== --}}
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
</script>

{{-- ========================================== --}}
{{-- SCRIPT 2: NOTIFIKASI (mstoreNotify) --}}
{{-- ========================================== --}}
<script>
    window.mstoreBuildPopupConfig = function (overrides) {
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

    (function () {
        window.mstoreNotify = {
            success: function (message, options) {
                return Swal.fire(window.mstoreBuildPopupConfig(Object.assign({
                    icon: 'success',
                    title: 'Berhasil',
                    html: message || 'Aksi berhasil diproses',
                    position: 'center',
                    timer: 2600,
                    showConfirmButton: false
                }, options || {})));
            },
            error: function (message, options) {
                return Swal.fire(window.mstoreBuildPopupConfig(Object.assign({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    html: message || 'Terjadi kesalahan saat memproses data',
                    showConfirmButton: true,
                    confirmButtonText: 'Tutup'
                }, options || {})));
            },
            warning: function (message, options) {
                return Swal.fire(window.mstoreBuildPopupConfig(Object.assign({
                    icon: 'warning',
                    title: 'Peringatan',
                    html: message || 'Harap periksa kembali data Anda',
                    showConfirmButton: true,
                    confirmButtonText: 'Mengerti'
                }, options || {})));
            },
            info: function (message, options) {
                return Swal.fire(window.mstoreBuildPopupConfig(Object.assign({
                    icon: 'info',
                    title: 'Informasi',
                    html: message || 'Informasi terbaru',
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }, options || {})));
            },
            loading: function (message, options) {
                return Swal.fire(window.mstoreBuildPopupConfig(Object.assign({
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
    })();
</script>

{{-- ========================================== --}}
{{-- SCRIPT 3: AUTO LOADING FORM & NAVIGASI --}}
{{-- ========================================== --}}
<script>
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
</script>

{{-- ========================================== --}}
{{-- SCRIPT 4: DELETE CONFIRMATION --}}
{{-- ========================================== --}}
<script>
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
                Swal.fire(window.mstoreBuildPopupConfig({
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
</script>

{{-- ========================================== --}}
{{-- SCRIPT 5: PAGE LOADING HELPER --}}
{{-- ========================================== --}}
<script>
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
</script>

{{-- ========================================== --}}
{{-- SCRIPT 6: FLASH MESSAGE NOTIFICATION --}}
{{-- ========================================== --}}
<script>
    (function() {
        @if($errors->any() && !Route::is('technicians.kasbon.*'))
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

{{-- ========================================== --}}
{{-- SCRIPT 7: FEATHER ICONS & RESPONSIVE TABLE --}}
{{-- ========================================== --}}
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

{{-- ========================================== --}}
{{-- SCRIPT 8: PASSWORD TOGGLE --}}
{{-- ========================================== --}}
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

{{-- ========================================== --}}
{{-- SCRIPT 9: UPLOAD LOADING INDICATOR --}}
{{-- ========================================== --}}
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

{{-- ========================================== --}}
{{-- SCRIPT 10: PRESENCE PING (AUTENTIKASI) --}}
{{-- ========================================== --}}
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('a[target="_blank"]').forEach(function (link) {
            const existingRel = (link.getAttribute('rel') || '').trim();
            const relParts = existingRel.length ? existingRel.split(/\s+/) : [];
            if (relParts.indexOf('noopener') === -1) relParts.push('noopener');
            if (relParts.indexOf('noreferrer') === -1) relParts.push('noreferrer');
            link.setAttribute('rel', relParts.join(' ').trim());
        });
    });
</script>

@stack('scripts')

</body>
</html>