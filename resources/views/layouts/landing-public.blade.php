<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="layout-navbar-fixed layout-wide" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = $storeName ?? config('app.name', 'MStore');
        $pageTitle = $pageTitle ?? ($siteName.' - Multi Layanan');
        $pageDescription = $pageDescription ?? 'MStore menghadirkan Internet Fiber, Wedding & Event, CCTV, GT Wash, dan ATK Store.';
        $pageUrl = $pageUrl ?? url()->current();
        $pageImage = $pageImage ?? asset('img/cctv-monitor.png');
        $logoUrl = asset('img/logo.png');
        $waUrlBase = 'https://wa.me/'.($waNumber ?? '6281234567890');
        $jsonLdOffers = collect($serviceCatalog ?? [])->map(fn ($service) => [
            '@type' => 'Offer',
            'name' => $service['name'],
            'url' => $service['url'],
        ])->values()->all();
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:site_name" content="{{ $siteName }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/landing-lite.css') }}?v={{ filemtime(public_path('css/landing-lite.css')) }}" rel="stylesheet">

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $siteName,
        'url' => $pageUrl,
        'logo' => $logoUrl,
        'image' => $pageImage,
        'telephone' => $storePhone ?? null,
        'email' => $storeEmail ?? null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $storeAddress ?? null,
            'addressCountry' => 'ID',
        ],
        'sameAs' => [$waUrlBase],
        'makesOffer' => $jsonLdOffers,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <script>
        (function () {
            const storedTheme = localStorage.getItem('theme');
            if (storedTheme) {
                document.documentElement.setAttribute('data-bs-theme', storedTheme);
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            }
        })();
    </script>
</head>
<body class="{{ ($currentServiceSlug ?? null) === 'wedding-event' ? 'wedding-page-body' : '' }}">
    @php
        $isWeddingPage = ($currentServiceSlug ?? null) === 'wedding-event';
        $primaryCtaHref = !empty($servicePage) ? '#service-lead' : '#services';
        $primaryCtaLabel = !empty($servicePage) ? ($servicePage['form']['title'] ?? 'Konsultasi') : 'Pilih Layanan';
        $authHref = auth()->check() ? route('dashboard') : route('login');
        $authLabel = auth()->check() ? 'Dashboard' : 'Masuk';
        $navLinks = $isWeddingPage
            ? [
                ['href' => '#beranda', 'label' => 'Beranda'],
                ['href' => '#keunggulan', 'label' => 'Keunggulan'],
                ['href' => '#wedding-packages', 'label' => 'Pilihan Paket'],
                ['href' => '#wedding-calculator', 'label' => 'Kalkulator'],
                ['href' => '#testimoni', 'label' => 'Testimoni'],
                ['href' => '#service-lead', 'label' => 'Konsultasi'],
            ]
            : array_merge(
                [['href' => route('landing'), 'label' => 'Beranda', 'active' => request()->routeIs('landing')]],
                collect($serviceCatalog ?? [])->map(function ($service) use ($currentServiceSlug) {
                    return [
                        'href' => $service['url'],
                        'label' => $service['nav_label'],
                        'active' => ($currentServiceSlug ?? null) === $service['slug'],
                    ];
                })->values()->all(),
                [
                    ['href' => route('landing').'#coverage-area', 'label' => 'Coverage', 'active' => false],
                    ['href' => '#kontak', 'label' => 'Kontak', 'active' => false],
                ]
            );
    @endphp

    <nav class="navbar {{ $isWeddingPage ? 'wedding-navbar' : '' }}" aria-label="Primary Navigation">
        <div class="nav-container">
            <div class="nav-inner">
                <div class="nav-left">
                    <a class="nav-brand" href="{{ route('landing') }}">
                        <img class="nav-logo" src="{{ $logoUrl }}" width="26" height="26" alt="{{ $siteName }}" decoding="async">
                        <span class="d-none d-sm-inline">{{ $isWeddingPage ? $siteName.' Decoration' : $siteName }}</span>
                    </a>
                </div>

                <div class="nav-primary d-none d-lg-flex">
                    <div class="nav-menu-panel">
                        @foreach($navLinks as $link)
                            <a class="nav-link {{ !empty($link['active']) ? 'active' : '' }}" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="nav-actions">
                    <button class="btn-icon" id="themeToggle" type="button" aria-label="Toggle tema">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="{{ $primaryCtaHref }}" class="btn {{ $isWeddingPage ? 'wedding-btn-light' : 'btn-outline-primary' }} btn-full-mobile nav-desktop-action {{ !empty($servicePage) ? 'track-service-action' : '' }}" @if(!empty($servicePage)) data-track-service="{{ $servicePage['slug'] }}" data-track-action="hero_cta" @endif>
                        {{ $primaryCtaLabel }}
                    </a>
                    <a href="{{ $authHref }}" class="btn {{ auth()->check() ? 'btn-primary' : 'btn-outline-primary' }} btn-full-mobile nav-desktop-action">
                        {{ $authLabel }}
                    </a>
                    <button class="btn-icon nav-toggle" type="button" id="navToggle" aria-controls="navMenu" aria-expanded="false" aria-label="Buka menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>

            <div class="nav-menu" id="navMenu">
                <div class="nav-menu-inner">
                    <div class="nav-mobile-sheet">
                        <div class="nav-mobile-head">
                            <div>
                                <span class="nav-mobile-kicker">{{ $isWeddingPage ? 'Wedding Navigation' : 'MStore Navigation' }}</span>
                                <h4>{{ $isWeddingPage ? $siteName.' Decoration' : $siteName }}</h4>
                            </div>
                            <button class="btn-icon nav-close" type="button" id="navClose" aria-label="Tutup menu">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="nav-mobile-section">
                            <div class="nav-mobile-label">Menu Utama</div>
                            <div class="nav-menu-panel">
                                @foreach($navLinks as $link)
                                    <a class="nav-link {{ !empty($link['active']) ? 'active' : '' }}" href="{{ $link['href'] }}">
                                        <span>{{ $link['label'] }}</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="nav-mobile-actions">
                            <a href="{{ $primaryCtaHref }}" class="btn {{ $isWeddingPage ? 'wedding-btn-light' : 'btn-primary' }} {{ !empty($servicePage) ? 'track-service-action' : '' }}" @if(!empty($servicePage)) data-track-service="{{ $servicePage['slug'] }}" data-track-action="hero_cta_mobile" @endif>
                                {{ $primaryCtaLabel }}
                            </a>
                            <a href="{{ $authHref }}" class="btn btn-outline-primary">
                                {{ $authLabel }}
                            </a>
                            <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan.') }}" class="btn btn-green">
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    @if(($currentServiceSlug ?? null) === 'wedding-event')
    <footer id="kontak" class="wedding-footer">
        <div class="container">
            <div class="row g-4 wedding-footer-grid">
                <div class="col-lg-4 fade-up">
                    <div class="wedding-footer-brand">
                        <div class="wedding-footer-brand-mark">M</div>
                        <div>
                            <strong>{{ $siteName }}</strong>
                            <span>Decoration</span>
                        </div>
                    </div>
                    <p class="wedding-footer-copy">Penyedia layanan wedding dan event dengan pendekatan elegan, paket lebih terarah, dan proses konsultasi yang lebih profesional.</p>
                </div>
                <div class="col-lg-4 fade-up">
                    <h5>Kontak Kami</h5>
                    <ul class="wedding-footer-list">
                        <li><i class="fas fa-phone"></i><span>{{ $storePhone }}</span></li>
                        <li><i class="fab fa-whatsapp"></i><span>{{ $waNumber }}</span></li>
                        <li><i class="fas fa-location-dot"></i><span>{{ $storeAddress }}</span></li>
                    </ul>
                </div>
                <div class="col-lg-4 fade-up">
                    <h5>Navigasi Wedding</h5>
                    <ul class="wedding-footer-list">
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#wedding-packages">Pilihan Paket</a></li>
                        <li><a href="#wedding-calculator">Kalkulator</a></li>
                        <li><a href="#service-lead">Konsultasi</a></li>
                    </ul>
                </div>
            </div>
            <div class="wedding-footer-bottom">
                <p>© {{ date('Y') }} {{ $siteName }} Decoration. Seluruh hak cipta dilindungi.</p>
                <div class="wedding-footer-social">
                    <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi wedding.') }}"><i class="fab fa-whatsapp"></i></a>
                    <a href="{{ route('landing.services.wedding') }}"><i class="fas fa-ring"></i></a>
                </div>
            </div>
        </div>
    </footer>
    @else
    <footer id="kontak" class="site-footer">
        <div class="container">
            <div class="row g-4 site-footer-grid">
                <div class="col-lg-4 fade-up">
                    <div class="site-footer-brand">
                        <div class="site-footer-brand-mark">
                            <img class="nav-logo" src="{{ $logoUrl }}" width="34" height="34" alt="{{ $siteName }}" decoding="async">
                        </div>
                        <div>
                            <strong>{{ $siteName }}</strong>
                            <span>Multi Layanan Terintegrasi</span>
                        </div>
                    </div>
                    <p class="site-footer-copy">{{ $pageDescription }}</p>
                    <div class="site-footer-cta">
                        <a class="btn btn-green" href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan.') }}">
                            <i class="fab fa-whatsapp me-2"></i> WhatsApp
                        </a>
                        <a class="btn btn-outline-primary" href="{{ route('landing') }}#services">
                            Lihat Layanan
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 fade-up">
                    <h5 class="site-footer-title">Layanan</h5>
                    <ul class="site-footer-list">
                        @foreach($serviceCatalog ?? [] as $service)
                            <li>
                                <a href="{{ $service['url'] }}">{{ $service['name'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-lg-2 fade-up">
                    <h5 class="site-footer-title">Navigasi</h5>
                    <ul class="site-footer-list">
                        <li><a href="{{ route('landing') }}">Beranda</a></li>
                        <li><a href="{{ route('landing') }}#coverage-area">Coverage</a></li>
                        <li><a href="#kontak">Kontak</a></li>
                        <li><a href="{{ $authHref }}">{{ $authLabel }}</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 fade-up">
                    <h5 class="site-footer-title">Kontak</h5>
                    <ul class="site-footer-list site-footer-contact">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>{{ $storeEmail }}</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>{{ $storePhone }}</span>
                        </li>
                        <li>
                        <i class="fab fa-whatsapp me-2"></i> WhatsApp
                            <span>{{ $waNumber }}</span>
                        </li>
                        <li>
                            <i class="fas fa-location-dot"></i>
                            <span>{{ $storeAddress }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="site-footer-bottom">
                <p>© {{ date('Y') }} {{ $siteName }}. Semua hak cipta dilindungi.</p>
                <div class="site-footer-social">
                    <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan.') }}" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="{{ route('landing') }}" aria-label="MStore"><i class="fas fa-store"></i></a>
                </div>
            </div>
        </div>
    </footer>
    @endif

    <div class="bottom-bar fixed-bottom d-lg-none {{ ($currentServiceSlug ?? null) === 'wedding-event' ? 'd-none' : '' }}">
        <div class="bottom-bar-inner">
            <a href="{{ route('landing') }}" class="bottom-item {{ request()->routeIs('landing') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('landing.services.internet') }}" class="bottom-item {{ ($currentServiceSlug ?? null) === 'internet' ? 'active' : '' }}">
                <i class="fas fa-wifi"></i>
                <span>Internet</span>
            </a>
            <a href="{{ route('landing.services.wash') }}" class="bottom-item {{ ($currentServiceSlug ?? null) === 'gt-wash' ? 'active' : '' }}">
                <i class="fas fa-car-side"></i>
                <span>GT Wash</span>
            </a>
            <a href="#kontak" class="bottom-item">
                <i class="fas fa-address-book"></i>
                <span>Kontak</span>
            </a>
            <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan.') }}" class="bottom-item">
                <i class="fab fa-whatsapp"></i>
                <span>WA</span>
            </a>
        </div>
    </div>

    <a class="floating-wa" href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan.') }}" aria-label="WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <div id="ai-chat-widget" class="fixed-bottom m-4 d-flex justify-content-end" style="z-index: 1050; pointer-events: none;">
        <div class="chat-container d-none" style="pointer-events: auto; width: 350px; height: 500px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div class="chat-header p-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-robot"></i>
                    <span class="fw-bold">Asisten AI MStore</span>
                </div>
                <button class="btn btn-sm text-white" onclick="toggleChat()"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-body p-3 flex-grow-1 overflow-auto" id="chat-messages" style="scroll-behavior: smooth;">
                <div class="d-flex flex-column gap-2">
                    <div class="bg-secondary bg-opacity-25 p-2 rounded-3 align-self-start" style="max-width: 80%;">
                        Halo! Ada yang bisa saya bantu?
                    </div>
                </div>
            </div>
            <div class="chat-footer p-3 border-top border-secondary border-opacity-25">
                <form id="chat-form" class="d-flex gap-2" onsubmit="handleChatSubmit(event)">
                    <input type="text" id="chat-input" class="form-control form-control-sm bg-transparent text-body" placeholder="Tanya sesuatu..." required>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        <button class="btn btn-primary rounded-circle shadow-lg p-3 ms-3" onclick="toggleChat()" style="width: 60px; height: 60px; pointer-events: auto;">
            <i class="fas fa-comment-dots fa-lg"></i>
        </button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        function toggleChat() {
            const container = document.querySelector('.chat-container');
            container.classList.toggle('d-none');
            if (!container.classList.contains('d-none')) {
                document.getElementById('chat-input').focus();
            }
        }

        async function handleChatSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;

            addMessage(message, 'end');
            input.value = '';
            const loadingId = addMessage('Sedang mengetik...', 'start', true);

            try {
                const response = await fetch({{ Js::from(route('ai.public.chat')) }}, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                const loadingNode = document.getElementById(loadingId);
                if (loadingNode) loadingNode.remove();
                const replyText = (data && (data.reply || data.response)) ? (data.reply || data.response) : 'Maaf, saya tidak mengerti.';
                addMessage(replyText, 'start');
            } catch (error) {
                const loadingNode = document.getElementById(loadingId);
                if (loadingNode) loadingNode.remove();
                addMessage('Maaf, terjadi kesalahan koneksi.', 'start');
            }
        }

        function addMessage(text, align) {
            const messages = document.getElementById('chat-messages');
            const id = 'msg-' + Date.now();
            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex flex-column gap-2 mb-2';
            const div = document.createElement('div');
            div.id = id;
            div.className = `p-2 rounded-3 align-self-${align} ${align === 'end' ? 'bg-primary text-white' : 'bg-secondary bg-opacity-25'}`;
            div.style.maxWidth = '80%';
            div.innerHTML = text;
            wrapper.appendChild(div);
            messages.appendChild(wrapper);
            messages.scrollTop = messages.scrollHeight;
            return id;
        }

        function trackLandingEvent(service, action, label) {
            const payload = {
                event: 'landing_interaction',
                service: service || 'umbrella',
                action: action || 'click',
                label: label || '',
                path: window.location.pathname
            };

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(payload);
            document.dispatchEvent(new CustomEvent('landing:interaction', { detail: payload }));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('navToggle');
            const navMenu = document.getElementById('navMenu');
            const navClose = document.getElementById('navClose');
            if (navToggle && navMenu) {
                const syncNavState = (isActive) => {
                    navMenu.classList.toggle('active', isActive);
                    navToggle.setAttribute('aria-expanded', isActive ? 'true' : 'false');
                    document.body.classList.toggle('nav-open', isActive);
                };

                navToggle.addEventListener('click', () => {
                    syncNavState(!navMenu.classList.contains('active'));
                });
                if (navClose) {
                    navClose.addEventListener('click', () => syncNavState(false));
                }
                document.addEventListener('click', (e) => {
                    if (!navMenu.classList.contains('active')) return;
                    if (navMenu.contains(e.target) || navToggle.contains(e.target)) return;
                    syncNavState(false);
                });
                navMenu.querySelectorAll('.nav-link, .btn').forEach((element) => {
                    element.addEventListener('click', () => syncNavState(false));
                });
            }

            document.querySelectorAll('.track-service-action').forEach((element) => {
                element.addEventListener('click', () => {
                    trackLandingEvent(
                        element.getAttribute('data-track-service'),
                        element.getAttribute('data-track-action'),
                        element.getAttribute('data-track-label') || element.textContent.trim()
                    );
                });
            });

            document.querySelectorAll('form[data-track-service-form]').forEach((form) => {
                form.addEventListener('submit', () => {
                    trackLandingEvent(
                        form.getAttribute('data-track-service-form'),
                        'lead_submit',
                        form.getAttribute('data-track-label') || 'lead_form'
                    );
                });
            });

            const mapContainer = document.getElementById('coverageMap');
            if (mapContainer) {
                const map = L.map('coverageMap').setView([-6.200000, 106.816666], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const odps = {{ Js::from($odps ?? []) }};
                const markers = [];
                odps.forEach((odp) => {
                    if (odp.latitude && odp.longitude) {
                        const availablePorts = (odp.available_ports !== null && odp.available_ports !== undefined)
                            ? odp.available_ports
                            : 'N/A';
                        const marker = L.marker([odp.latitude, odp.longitude])
                            .bindPopup(`<b>${odp.name}</b><br>Status: ${odp.status}<br>Port Tersedia: ${availablePorts}`);
                        marker.addTo(map);
                        markers.push(marker);
                    }
                });

                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                }
            }

            if (document.body.classList.contains('wedding-page-body')) {
                const weddingNavLinks = Array.from(document.querySelectorAll('.wedding-navbar .nav-link[href^="#"], #navMenu .nav-link[href^="#"]'));
                const weddingSections = weddingNavLinks
                    .map(link => document.querySelector(link.getAttribute('href')))
                    .filter(Boolean);

                if (weddingSections.length > 0) {
                    const sectionObserver = new IntersectionObserver((entries) => {
                        const visibleEntry = entries
                            .filter(entry => entry.isIntersecting)
                            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                        if (!visibleEntry) return;

                        const targetId = '#'+visibleEntry.target.id;
                        weddingNavLinks.forEach((link) => {
                            link.classList.toggle('active', link.getAttribute('href') === targetId);
                        });
                    }, { rootMargin: '-20% 0px -55% 0px', threshold: [0.15, 0.35, 0.6] });

                    weddingSections.forEach((section) => sectionObserver.observe(section));
                }
            }
        });

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
        } else {
            document.querySelectorAll('.fade-up').forEach(el => el.classList.add('visible'));
        }

        const themeToggle = document.getElementById('themeToggle');
        const currentLandingTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        if (themeToggle) {
            themeToggle.querySelector('i').className = currentLandingTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            themeToggle.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                themeToggle.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            });
        }
    </script>
</body>
</html>
