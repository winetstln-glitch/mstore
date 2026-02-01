<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MStore') }} - Internet, ATK & Services</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/landing.css') }}" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-network-wired me-2"></i>{{ config('app.name', 'MStore') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#packages">Internet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#atk-promo">ATK Store</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#wash-services">Auto Wash</a>
                    </li>
                    <li class="nav-item me-2 ms-2">
                        <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item ms-lg-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-login">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Masuk
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right">
                    <h1>Solusi Digital & Layanan Terlengkap</h1>
                    <p>Dari internet super cepat, perlengkapan kantor berkualitas, hingga perawatan kendaraan Anda. Semua ada di sini.</p>
                    <div class="d-flex gap-2">
                        <a href="#packages" class="btn btn-outline-light rounded-pill px-4 py-3 fw-bold">
                            <i class="fas fa-wifi me-2"></i>Pasang Internet
                        </a>
                        <a href="#atk-promo" class="btn btn-outline-light rounded-pill px-4 py-3 fw-bold">
                            <i class="fas fa-shopping-bag me-2"></i>Belanja ATK
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block text-center" data-aos="fade-left">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/high-speed-internet-connection-illustration-download-in-svg-png-gif-file-formats--broadband-fiber-optic-wifi-network-digital-communication-pack-technology-illustrations-4357288.png?f=webp" alt="Internet Illustration" class="img-fluid" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mt-n5" style="position: relative; z-index: 2;">
        <div class="row justify-content-center">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-globe-asia"></i>
                    </div>
                    <div class="stat-number">Wide</div>
                    <div class="stat-label">Coverage Area</div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Technical Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products/Packages Section -->
    <section id="packages" class="py-5">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Pilihan Paket Internet</h2>
                <p>Sesuaikan dengan kebutuhan digital rumah dan bisnis Anda.</p>
            </div>

            <div class="row justify-content-center g-4">
                @forelse($packages as $package)
                <div class="col-md-6 col-lg-4" data-aos="flip-left" data-aos-delay="{{ 100 * $loop->iteration }}">
                    <div class="pricing-card h-100">
                        <div class="pricing-header">
                            <div class="pricing-speed">{{ $package->speed }}</div>
                            <div class="pricing-unit">Mbps</div>
                        </div>
                        <div class="pricing-body">
                            <div class="pricing-price">
                                <span class="price-amount">{{ number_format($package->price, 0, ',', '.') }}</span>
                                <span class="price-period">/ bulan</span>
                            </div>
                            <h4 class="text-center mb-3">{{ $package->name }}</h4>
                            <ul class="pricing-features">
                                <li><i class="fas fa-check-circle"></i> Unlimited Quota</li>
                                <li><i class="fas fa-check-circle"></i> Fiber Optic Connection</li>
                                <li><i class="fas fa-check-circle"></i> 24/7 Support</li>
                                @if($package->description)
                                    <li><i class="fas fa-check-circle"></i> {{ $package->description }}</li>
                                @endif
                            </ul>
                            <a href="https://api.whatsapp.com/send?phone={{ $waNumber }}&text=Halo%20saya%20tertarik%20berlangganan%20paket%20{{ urlencode($package->name) }}" target="_blank" class="btn btn-primary btn-select-plan">
                                Pilih Paket
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center">
                    <div class="alert alert-info">Belum ada paket yang tersedia saat ini. Silakan hubungi kami.</div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ATK Promo Section -->
    <section id="atk-promo" class="py-5 bg-light">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Promo Alat Tulis Kantor</h2>
                <p>Lengkapi kebutuhan kantor dan sekolah Anda dengan harga terbaik.</p>
            </div>
            
            <div class="row g-4">
                @forelse($atkProducts as $product)
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ 100 * $loop->iteration }}">
                    <div class="product-card h-100">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-image">
                        @else
                            <div class="product-image d-flex align-items-center justify-content-center bg-light text-secondary">
                                <i class="fas fa-image fa-2x"></i>
                            </div>
                        @endif
                        <div class="product-body">
                            <div class="product-category">{{ $product->category->name ?? 'ATK' }}</div>
                            <h5 class="product-title text-truncate">{{ $product->name }}</h5>
                            <div class="product-price">Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</div>
                            <div class="product-description small">
                                {{ Str::limit($product->description ?? 'Tersedia di toko kami.', 60) }}
                            </div>
                            <a href="https://api.whatsapp.com/send?phone={{ $waNumber }}&text=Halo%20saya%20mau%20pesan%20ATK:%20{{ urlencode($product->name) }}" target="_blank" class="btn btn-outline-primary rounded-pill w-100 mt-auto">
                                <i class="fab fa-whatsapp me-2"></i>Pesan
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center">
                    <p class="text-muted">Belum ada promo produk saat ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Wash Services Section -->
    <section id="wash-services" class="py-5">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Layanan Cuci & Steam</h2>
                <p>Perawatan terbaik untuk kendaraan kesayangan Anda.</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                @forelse($washServices as $service)
                <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="{{ 100 * $loop->iteration }}">
                    <div class="product-card h-100 border-0 shadow-sm">
                        @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="product-image">
                        @else
                            <div class="product-image d-flex align-items-center justify-content-center bg-light text-secondary">
                                <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }} fa-2x"></i>
                            </div>
                        @endif
                        <div class="product-body text-center">
                            <div class="mb-3">
                                <span class="badge bg-info rounded-pill px-3 py-2">
                                    <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }} me-2"></i>
                                    {{ ucfirst($service->vehicle_type) }}
                                </span>
                            </div>
                            <h4 class="product-title mb-3">{{ $service->name }}</h4>
                            <div class="product-price display-6 mb-3">Rp {{ number_format($service->price, 0, ',', '.') }}</div>
                            <p class="text-muted mb-4">{{ $service->description ?? 'Layanan cuci bersih dan mengkilap.' }}</p>
                            <a href="https://api.whatsapp.com/send?phone={{ $waNumber }}&text=Halo%20saya%20mau%20booking%20cuci%20{{ $service->vehicle_type }}:%20{{ urlencode($service->name) }}" target="_blank" class="btn btn-success rounded-pill w-100">
                                <i class="fab fa-whatsapp me-2"></i>Booking Sekarang
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                 <div class="col-12 text-center">
                    <p class="text-muted">Layanan belum tersedia.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Monitoring Showcase Section -->
    <section id="monitoring" class="monitoring-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 monitoring-image" data-aos="fade-right">
                    <!-- Live Coverage Map -->
                    <div id="coverageMap" class="shadow-lg" style="height: 400px; width: 100%; border-radius: 1rem; z-index: 1;"></div>
                </div>
                <div class="col-lg-6 monitoring-content ps-lg-5" data-aos="fade-left">
                    <h2 class="mb-4 fw-bold">Pantau Jaringan Secara Real-Time</h2>
                    <p class="lead text-muted mb-4">Kami menggunakan teknologi monitoring canggih untuk memastikan kualitas jaringan tetap prima.</p>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="feature-icon-circle bg-primary text-white" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                <i class="fas fa-satellite-dish"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5>ODP & Closure Mapping</h5>
                            <p class="mb-0 text-muted">Pemetaan infrastruktur yang akurat memudahkan teknisi dalam pemeliharaan dan instalasi baru.</p>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="feature-icon-circle bg-success text-white" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5>Aplikasi Pelanggan & Teknisi</h5>
                            <p class="mb-0 text-muted">Sistem terintegrasi dari pelanggan hingga teknisi lapangan untuk respon cepat.</p>
                        </div>
                    </div>

                    <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4">
                        Lihat Area Coverage (Login)
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a href="#" class="footer-brand">
                        <i class="fas fa-network-wired me-2"></i>{{ config('app.name', 'MStore') }}
                    </a>
                    <p class="text-white-50">Penyedia layanan internet fiber optic terpercaya dengan komitmen kualitas dan pelayanan terbaik untuk masyarakat.</p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="https://api.whatsapp.com/send?phone={{ $waNumber }}" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h5 class="text-white mb-3">Layanan</h5>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Home Internet</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">ATK Store</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Car Wash</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6 mb-4">
                    <h5 class="text-white mb-3">Support</h5>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Cek Tagihan</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Lapor Gangguan</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Coverage Area</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="text-white mb-3">Hubungi Kami</h5>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Jl. Raya Internet No. 123, Kota Digital</li>
                        <li class="mb-2">
                            <a href="https://api.whatsapp.com/send?phone={{ $waNumber }}" target="_blank" class="text-white-50 text-decoration-none">
                                <i class="fas fa-phone me-2"></i> +62 877 7736 9687
                            </a>
                        </li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> support@mstore.com</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="text-center text-white-50">
                <small>&copy; {{ date('Y') }} {{ config('app.name', 'MStore') }}. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        // Initialize Map
        document.addEventListener('DOMContentLoaded', function() {
            var odps = @json($odps ?? []);
            
            // Default Center (Server Location or generic Indonesia coords if needed)
            var defaultLat = -6.800278;
            var defaultLng = 105.939159;
            var initialZoom = 13;

            // If we have ODPs, center on the first one
            if (odps.length > 0) {
                defaultLat = parseFloat(odps[0].latitude);
                defaultLng = parseFloat(odps[0].longitude);
                initialZoom = 15;
            }

            var map = L.map('coverageMap').setView([defaultLat, defaultLng], initialZoom);

            // Tile Layers
            var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            });

            var darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap &copy; CARTO'
            });

            // Theme Handling
            function updateMapTheme() {
                var currentTheme = document.documentElement.getAttribute('data-bs-theme');
                if (currentTheme === 'dark') {
                    if (map.hasLayer(osm)) map.removeLayer(osm);
                    darkLayer.addTo(map);
                } else {
                    if (map.hasLayer(darkLayer)) map.removeLayer(darkLayer);
                    osm.addTo(map);
                }
            }

            // Initial Theme Check
            updateMapTheme();

            // ODP Icon
            var odpIcon = L.divIcon({
                className: 'custom-div-icon',
                html: "<div style='background-color: #4e73df; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);'></div>",
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            });

            // Add Markers
            var bounds = [];
            odps.forEach(function(odp) {
                if(odp.latitude && odp.longitude) {
                    var marker = L.marker([odp.latitude, odp.longitude], {icon: odpIcon})
                        .bindPopup("<b>" + odp.name + "</b><br>Status: Available")
                        .addTo(map);
                    bounds.push([odp.latitude, odp.longitude]);
                }
            });

            // Fit bounds if multiple markers exist
            if (bounds.length > 0) {
                map.fitBounds(bounds, {padding: [50, 50]});
            }

            // Listen for theme changes (from theme toggle script)
            const themeToggleBtn = document.getElementById('themeToggle');
            if(themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function() {
                    setTimeout(updateMapTheme, 100); // Small delay to wait for attribute change
                });
            }
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.querySelector('.navbar').classList.add('shadow-sm');
                document.querySelector('.navbar').style.padding = '0.5rem 0';
            } else {
                document.querySelector('.navbar').classList.remove('shadow-sm');
                document.querySelector('.navbar').style.padding = '1rem 0';
            }
        });

        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        const html = document.documentElement;
        
        // Check saved preference
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);
        updateIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });

        function updateIcon(theme) {
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
    </script>
</body>
</html>