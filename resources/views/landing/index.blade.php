<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="layout-navbar-fixed layout-wide" dir="ltr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ config('app.name', 'MStore') }} - Internet, ATK & Services</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Bootstrap 5 (Pastikan ini ada atau tergantikan landing-lite.css) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Landing Lite CSS -->
    <link href="{{ asset('css/landing-lite.css') }}" rel="stylesheet">

    
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-inner">
                <button class="nav-toggle" id="navToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="nav-brand" href="#">
                    <img class="nav-logo" src="{{ asset('img/logo.png') }}" alt="Logo">
                    <span>{{ config('app.name', 'MStore') }}</span>
                </a>
                <div class="nav-primary d-none d-lg-flex">
                    <a class="nav-link" href="#home">Beranda</a>
                    <a class="nav-link" href="#packages">Internet</a>
                    <a class="nav-link" href="#atk-promo">ATK Store</a>
                    <a class="nav-link" href="#wash-services">Auto Wash</a>
                    <a class="nav-link" href="#cctv">CCTV</a>
                </div>
                <div class="nav-actions">
                    <div class="d-none d-lg-flex align-items-center ms-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                                Masuk
                            </a>
                        @endauth
                    </div>
                    <button class="btn-icon" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            <div class="nav-menu" id="navMenu">
                <div class="nav-menu-inner">
                    <div class="nav-menu-panel">
                        <a class="nav-link" href="#home">Beranda</a>
                        <a class="nav-link" href="#packages">Internet</a>
                        <a class="nav-link" href="#atk-promo">ATK Store</a>
                        <a class="nav-link" href="#wash-services">Auto Wash</a>
                        <a class="nav-link" href="#cctv">CCTV</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="mobile-appbar d-lg-none">
        <div class="mobile-appbar-inner">
            <div class="appbar-left">
                <img class="appbar-logo" src="{{ asset('img/logo.png') }}" alt="Logo">
                <span class="appbar-title">{{ config('app.name', 'MStore') }}</span>
            </div>
            <div></div>
            <div class="appbar-actions">
                <button class="btn-icon" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content fade-up">
                    <h1 class="hero-title">Solusi Digital & Layanan Terlengkap</h1>
                    <p class="hero-desc">Dari internet super cepat, perlengkapan kantor berkualitas, hingga perawatan kendaraan Anda. Semua ada di sini.</p>
                    
                    <div class="hero-actions d-flex">
                        <a href="#packages" class="btn btn-primary">
                            <i class="fas fa-wifi"></i> &nbsp; Paket Internet
                        </a>
                        <a href="#atk-promo" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-bag"></i>&nbsp; Belanja ATK
                        </a>
                        <a href="https://cctv.mstore.id/" target="_blank" rel="noopener" class="btn btn-outline-success">
                            <i class="fas fa-video"></i>&nbsp; Monitoring CCTV Online
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 hero-img fade-up">
                    <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072&auto=format&fit=crop" alt="Fiber Optic Network">
                </div>
            </div>
        </div>
    </section>

    <!-- CCTV Packages Section -->
    <section id="cctv" class="section section-alt">
        <div class="container">
            <div class="section-header fade-up">
                <h2 class="section-title">Paket Instalasi & Pemasangan CCTV</h2>
                <p class="text-muted">Keamanan rumah dan bisnis dengan sistem CCTV terpercaya.</p>
            </div>
            <div class="scroll-container fade-up">
                <div class="scroll-item">
                    <div class="card">
                        <div class="pricing-header">
                            <div class="speed">Basic</div>
                            <div class="unit">2 Kamera</div>
                        </div>
                        <div class="pricing-body">
                            <div class="price">1.999.000<span class="price-period"> / paket</span></div>
                            <h4 class="mb-3 mt-2">Rumah Kecil</h4>
                            <ul class="features">
                                <li><i class="fas fa-check-circle"></i> DVR 4 Channel</li>
                                <li><i class="fas fa-check-circle"></i> Kabel & Konektor</li>
                                <li><i class="fas fa-check-circle"></i> Pemasangan & Setting</li>
                            </ul>
                            <div class="d-grid gap-2">
                                <a href="https://cctv.mstore.id/" target="_blank" class="btn btn-outline-success">Lihat Detail</a>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20minat%20paket%20CCTV%20Basic" target="_blank" class="btn btn-primary">Pesan via WhatsApp</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scroll-item">
                    <div class="card">
                        <div class="pricing-header">
                            <div class="speed">Standard</div>
                            <div class="unit">4 Kamera</div>
                        </div>
                        <div class="pricing-body">
                            <div class="price">3.899.000<span class="price-period"> / paket</span></div>
                            <h4 class="mb-3 mt-2">Rumah & Toko</h4>
                            <ul class="features">
                                <li><i class="fas fa-check-circle"></i> DVR 8 Channel</li>
                                <li><i class="fas fa-check-circle"></i> Cloud/Remote View</li>
                                <li><i class="fas fa-check-circle"></i> Garansi 1 Tahun</li>
                            </ul>
                            <div class="d-grid gap-2">
                                <a href="https://cctv.mstore.id/" target="_blank" class="btn btn-outline-success">Lihat Detail</a>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20minat%20paket%20CCTV%20Standard" target="_blank" class="btn btn-primary">Pesan via WhatsApp</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scroll-item">
                    <div class="card">
                        <div class="pricing-header">
                            <div class="speed">Premium</div>
                            <div class="unit">8 Kamera</div>
                        </div>
                        <div class="pricing-body">
                            <div class="price">6.999.000<span class="price-period"> / paket</span></div>
                            <h4 class="mb-3 mt-2">Gudang & Kantor</h4>
                            <ul class="features">
                                <li><i class="fas fa-check-circle"></i> NVR IP Camera</li>
                                <li><i class="fas fa-check-circle"></i> PoE Switch</li>
                                <li><i class="fas fa-check-circle"></i> Maintenance 6 Bulan</li>
                            </ul>
                            <div class="d-grid gap-2">
                                <a href="https://cctv.mstore.id/" target="_blank" class="btn btn-outline-success">Lihat Detail</a>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20minat%20paket%20CCTV%20Premium" target="_blank" class="btn btn-primary">Pesan via WhatsApp</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="container mb-5">
        <div class="stats-grid fade-up">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-globe-asia"></i></div>
                <div class="stat-number">Wide</div>
                <div class="stat-label">Coverage</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number">1000+</div>
                <div class="stat-label">Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-headset"></i></div>
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support</div>
            </div>
        </div>
    </section>

    <!-- Products/Packages Section -->
    <section id="packages" class="section">
        <div class="container">
            <div class="section-header fade-up">
                <h2 class="section-title">Pilihan Paket Internet</h2>
                <p class="text-muted">Sesuaikan dengan kebutuhan digital rumah dan bisnis Anda.</p>
            </div>

            <div class="scroll-container fade-up">
                @forelse($packages as $package)
                <div class="scroll-item">
                    <div class="card">
                        <div class="pricing-header">
                            <div class="speed">{{ $package->speed }}</div>
                            <div class="unit">Mbps</div>
                        </div>
                        <div class="pricing-body">
                            <div class="price">
                                {{ number_format($package->price, 0, ',', '.') }}
                                <span class="price-period">/ bln</span>
                            </div>
                            <h4 class="mb-3 mt-2">{{ $package->name }}</h4>
                            <ul class="features">
                                <li><i class="fas fa-check-circle"></i> Unlimited Quota</li>
                                <li><i class="fas fa-check-circle"></i> Fiber Optic</li>
                                <li><i class="fas fa-check-circle"></i> 24/7 Support</li>
                                @if($package->description)
                                    <li><i class="fas fa-check-circle"></i> {{ $package->description }}</li>
                                @endif
                            </ul>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20tertarik%20berlangganan%20paket%20{{ urlencode($package->name) }}" target="_blank" class="btn btn-primary w-100 mt-auto">
                                Pilih Paket
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center w-100 p-5">
                    <div class="text-muted">Belum ada paket yang tersedia saat ini.</div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ATK Promo Section -->
    <section id="atk-promo" class="section section-alt">
        <div class="container">
            <div class="section-header fade-up">
                <h2 class="section-title">Promo Alat Tulis Kantor</h2>
                <p class="text-muted">Lengkapi kebutuhan kantor dan sekolah Anda.</p>
            </div>
            
            <div class="scroll-container fade-up">
                @forelse($atkProducts as $product)
                <div class="scroll-item">
                    <div class="card">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-img">
                        @else
                            <div class="product-img d-flex align-items-center justify-content-center">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        @endif
                        <div class="product-body">
                            <div class="product-cat">{{ $product->category->name ?? 'ATK' }}</div>
                            <h5 class="product-title">{{ $product->name }}</h5>
                            <div class="product-price">Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</div>
                            <div class="product-desc">
                                {{ Str::limit($product->description ?? 'Tersedia di toko kami.', 60) }}
                            </div>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20mau%20pesan%20ATK:%20{{ urlencode($product->name) }}" target="_blank" class="btn btn-outline-primary w-100 mt-auto">
                                <i class="fab fa-whatsapp"></i> &nbsp; Pesan
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center w-100 p-5">
                    <p class="text-muted">Belum ada promo produk saat ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Wash Services Section -->
    <section id="wash-services" class="section">
        <div class="container">
            <div class="section-header fade-up">
                <h2 class="section-title">Layanan Cuci & Steam</h2>
                <p class="text-muted">Perawatan terbaik untuk kendaraan Anda.</p>
            </div>
            
            <div class="scroll-container fade-up">
                @forelse($washServices as $service)
                <div class="scroll-item">
                    <div class="card">
                        @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="product-img">
                        @else
                            <div class="product-img d-flex align-items-center justify-content-center">
                                <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }} fa-3x text-secondary"></i>
                            </div>
                        @endif
                        <div class="product-body text-center">
                            <div class="mb-2">
                                <span class="chip">
                                    <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }}"></i>
                                    {{ ucfirst($service->vehicle_type) }}
                                </span>
                            </div>
                            <h4 class="product-title">{{ $service->name }}</h4>
                            <div class="product-price">Rp {{ number_format($service->price, 0, ',', '.') }}</div>
                            <p class="product-desc">{{ $service->description ?? 'Layanan cuci bersih dan mengkilap.' }}</p>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20mau%20booking%20cuci%20{{ $service->vehicle_type }}:%20{{ urlencode($service->name) }}" target="_blank" class="btn btn-accent w-100 mt-auto">
                                <i class="fab fa-whatsapp"></i> &nbsp; Booking
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                 <div class="text-center w-100 p-5">
                    <p class="text-muted">Layanan belum tersedia.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Monitoring Showcase Section -->
    <section id="monitoring" class="section">
        <div class="container">
            <div class="monitoring-grid">
                <div class="monitoring-image fade-up">
                    <!-- Live Coverage Map -->
                    <div id="coverageMap"></div>
                </div>
                <div class="monitoring-content fade-up">
                    <h2 class="section-title">Pantau Jaringan Real-Time</h2>
                    <p class="text-muted mb-4">Teknologi monitoring canggih untuk kualitas jaringan prima.</p>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-satellite-dish"></i>
                        </div>
                        <div>
                            <h5>ODP & Closure Mapping</h5>
                            <p class="text-muted">Pemetaan infrastruktur akurat.</p>
                        </div>
                    </div>

                        <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div>
                            <h5>Aplikasi Pelanggan & Teknisi</h5>
                            <p class="text-muted">Sistem terintegrasi untuk respon cepat.</p>
                        </div>
                    </div>

                    <a href="{{ route('login') }}" class="btn btn-outline-primary mt-3">
                        Lihat Area Coverage (Login)
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="#" class="footer-brand">
                        <i class="fas fa-network-wired"></i> {{ config('app.name', 'MStore') }}
                    </a>
                    <p class="text-muted">Penyedia layanan internet fiber optic terpercaya dengan komitmen kualitas dan pelayanan terbaik.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <h5 class="footer-title">Layanan</h5>
                    <div class="footer-links">
                        <a href="#">Home Internet</a>
                        <a href="#">ATK Store</a>
                        <a href="#">Car Wash</a>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <h5 class="footer-title">Support</h5>
                    <div class="footer-links">
                        <a href="#">Cek Tagihan</a>
                        <a href="#">Lapor Gangguan</a>
                        <a href="#">Coverage Area</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5 class="footer-title">Hubungi Kami</h5>
                    <div class="footer-links">
                        <div class="mb-2 text-muted"><i class="fas fa-map-marker-alt" style="width: 20px"></i> Jl. Raya Internet No. 123</div>
                        <div class="mb-2 text-muted"><i class="fas fa-phone" style="width: 20px"></i> +62 877 7736 9687</div>
                        <div class="mb-2 text-muted"><i class="fas fa-envelope" style="width: 20px"></i> support@mstore.com</div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5 pt-4 footer-bottom">
                <small class="text-muted">&copy; {{ date('Y') }} {{ config('app.name', 'MStore') }}. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <div class="bottom-bar d-lg-none">
        <div class="bottom-bar-inner">
            <a href="#home" class="bottom-item active">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="#packages" class="bottom-item">
                <i class="fa-solid fa-wifi"></i>
                <span>Internet</span>
            </a>
            <a href="#atk-promo" class="bottom-item">
                <i class="fa-solid fa-bag-shopping"></i>
                <span>ATK</span>
            </a>
            <a href="#wash-services" class="bottom-item">
                <i class="fa-solid fa-car"></i>
                <span>Wash</span>
            </a>
            <a href="#cctv" class="bottom-item">
                <i class="fa-solid fa-video"></i>
                <span>CCTV</span>
            </a>
            @auth
            <a href="{{ route('dashboard') }}" class="bottom-item">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dash</span>
            </a>
            @else
            <a href="{{ route('login') }}" class="bottom-item">
                <i class="fa-solid fa-user"></i>
                <span>Masuk</span>
            </a>
            @endauth
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // --- Theme Toggle Logic ---
        const themeToggles = document.querySelectorAll('#themeToggle');
        const themeIcons = Array.from(themeToggles).map(t => t.querySelector('i'));
        const html = document.documentElement;

        // Load saved theme or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        applyTheme(savedTheme);

        themeToggles.forEach(btn => {
            btn.addEventListener('click', () => {
                const isDark = html.getAttribute('data-bs-theme') === 'dark';
                const next = isDark ? 'light' : 'dark';
                applyTheme(next);
                localStorage.setItem('theme', next);
            });
        });

        function applyTheme(theme) {
            html.setAttribute('data-bs-theme', theme);
            html.setAttribute('data-theme', theme);
            
            themeIcons.forEach(icon => {
                if (!icon) return;
                if (theme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        }

        // --- Navbar Toggle Logic ---
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = navToggle.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                navToggle.querySelector('i').classList.remove('fa-times');
                navToggle.querySelector('i').classList.add('fa-bars');
            });
        });

        // --- Scroll Animation (Fade Up) ---
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

        // --- Initialize Map ---
        document.addEventListener('DOMContentLoaded', function() {
            var odps = @json($odps ?? []);
            
            // Default Center
            var defaultLat = -6.800278;
            var defaultLng = 105.939159;
            var initialZoom = 13;

            if (odps.length > 0) {
                for(let i=0; i < odps.length; i++) {
                    if(odps[i].latitude && odps[i].longitude) {
                        defaultLat = parseFloat(odps[i].latitude);
                        defaultLng = parseFloat(odps[i].longitude);
                        break;
                    }
                }
            }

            var map = L.map('coverageMap').setView([defaultLat, defaultLng], initialZoom);

            // Use a clean tile layer that supports dark mode styling conceptually or standard OSM
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            // Add ODP Markers
            odps.forEach(function(odp) {
                if (odp.latitude && odp.longitude) {
                    var isFull = (odp.capacity !== null && odp.filled >= odp.capacity);
                    
                    L.circleMarker([odp.latitude, odp.longitude], {
                        radius: 8,
                        fillColor: isFull ? '#ef4444' : '#3b82f6',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.9
                    }).addTo(map)
                    .bindPopup("<b>" + odp.name + "</b><br>Status: " + (isFull ? "Penuh" : "Tersedia"));
                }
            });
        });
    </script>
</body>
</html>
