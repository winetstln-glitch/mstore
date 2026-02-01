<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MStore') }} - Internet, ATK & Services</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Custom CSS (Light & Mobile First) -->
    <link href="{{ asset('css/landing-lite.css') }}" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a class="nav-brand" href="#">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height: 35px;">
                {{ config('app.name', 'MStore') }}
            </a>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button id="themeToggle" class="btn btn-outline-primary" style="padding: 0.5rem; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-moon"></i>
                </button>

                <button class="nav-toggle" id="navToggle" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="nav-menu" id="navMenu">
                <a class="nav-link" href="#home">Beranda</a>
                <a class="nav-link" href="#packages">Internet</a>
                <a class="nav-link" href="#atk-promo">ATK Store</a>
                <a class="nav-link" href="#wash-services">Auto Wash</a>
                
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-full-mobile">
                        <i class="fas fa-tachometer-alt"></i> &nbsp; Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-full-mobile">
                        <i class="fas fa-sign-in-alt"></i> &nbsp; Masuk
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Solusi Digital & Layanan Terlengkap</h1>
                <p class="hero-desc">Dari internet super cepat, perlengkapan kantor berkualitas, hingga perawatan kendaraan Anda. Semua ada di sini.</p>
                
                <div class="hero-actions">
                    <a href="#packages" class="btn btn-outline-light">
                        <i class="fas fa-wifi"></i> &nbsp; Pasang Internet
                    </a>
                    <a href="#atk-promo" class="btn btn-outline-light">
                        <i class="fas fa-shopping-bag"></i> &nbsp; Belanja ATK
                    </a>
                </div>
            </div>
            <div class="hero-img">
                <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072&auto=format&fit=crop" alt="Fiber Optic Network" style="border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container">
        <div class="stats-grid">
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
            <div class="section-header">
                <h2 class="section-title">Pilihan Paket Internet</h2>
                <p class="text-muted">Sesuaikan dengan kebutuhan digital rumah dan bisnis Anda.</p>
            </div>

            <div class="scroll-container">
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
                                <span style="font-size: 0.8rem; font-weight: 400; color: #888;">/ bln</span>
                            </div>
                            <h4 class="mb-3">{{ $package->name }}</h4>
                            <ul class="features">
                                <li><i class="fas fa-check-circle"></i> Unlimited Quota</li>
                                <li><i class="fas fa-check-circle"></i> Fiber Optic</li>
                                <li><i class="fas fa-check-circle"></i> 24/7 Support</li>
                                @if($package->description)
                                    <li><i class="fas fa-check-circle"></i> {{ $package->description }}</li>
                                @endif
                            </ul>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20tertarik%20berlangganan%20paket%20{{ urlencode($package->name) }}" target="_blank" class="btn btn-primary btn-full-mobile mt-auto">
                                Pilih Paket
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center" style="width: 100%;">
                    <div class="text-muted">Belum ada paket yang tersedia saat ini.</div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ATK Promo Section -->
    <section id="atk-promo" class="section" style="background-color: #f1f3f9;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Promo Alat Tulis Kantor</h2>
                <p class="text-muted">Lengkapi kebutuhan kantor dan sekolah Anda.</p>
            </div>
            
            <div class="scroll-container">
                @forelse($atkProducts as $product)
                <div class="scroll-item">
                    <div class="card">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-img">
                        @else
                            <div class="product-img" style="display: flex; align-items: center; justify-content: center; color: #ccc;">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        @endif
                        <div class="product-body">
                            <div class="product-cat">{{ $product->category->name ?? 'ATK' }}</div>
                            <h5 class="product-title">{{ $product->name }}</h5>
                            <div class="product-price">Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</div>
                            <div class="product-desc">
                                {{ Str::limit($product->description ?? 'Tersedia di toko kami.', 60) }}
                            </div>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20mau%20pesan%20ATK:%20{{ urlencode($product->name) }}" target="_blank" class="btn btn-outline-primary btn-full-mobile mt-auto">
                                <i class="fab fa-whatsapp"></i> &nbsp; Pesan
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center" style="width: 100%;">
                    <p class="text-muted">Belum ada promo produk saat ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Wash Services Section -->
    <section id="wash-services" class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Layanan Cuci & Steam</h2>
                <p class="text-muted">Perawatan terbaik untuk kendaraan Anda.</p>
            </div>
            
            <div class="scroll-container">
                @forelse($washServices as $service)
                <div class="scroll-item">
                    <div class="card">
                        @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="product-img">
                        @else
                            <div class="product-img" style="display: flex; align-items: center; justify-content: center; color: #ccc;">
                                <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }} fa-3x"></i>
                            </div>
                        @endif
                        <div class="product-body text-center">
                            <div class="mb-2">
                                <span style="background: #36b9cc; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;">
                                    <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }}"></i>
                                    {{ ucfirst($service->vehicle_type) }}
                                </span>
                            </div>
                            <h4 class="product-title">{{ $service->name }}</h4>
                            <div class="product-price">Rp {{ number_format($service->price, 0, ',', '.') }}</div>
                            <p class="product-desc">{{ $service->description ?? 'Layanan cuci bersih dan mengkilap.' }}</p>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20mau%20booking%20cuci%20{{ $service->vehicle_type }}:%20{{ urlencode($service->name) }}" target="_blank" class="btn btn-primary btn-full-mobile mt-auto" style="background-color: #1cc88a; border-color: #1cc88a;">
                                <i class="fab fa-whatsapp"></i> &nbsp; Booking
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                 <div class="text-center" style="width: 100%;">
                    <p class="text-muted">Layanan belum tersedia.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Monitoring Showcase Section -->
    <section id="monitoring" class="monitoring">
        <div class="container">
            <div class="monitoring-grid">
                <div class="monitoring-image">
                    <!-- Live Coverage Map -->
                    <div id="coverageMap"></div>
                </div>
                <div class="monitoring-content">
                    <h2 class="section-title">Pantau Jaringan Real-Time</h2>
                    <p class="text-muted mb-4">Teknologi monitoring canggih untuk kualitas jaringan prima.</p>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-satellite-dish"></i>
                        </div>
                        <div>
                            <h5>ODP & Closure Mapping</h5>
                            <p class="text-muted" style="font-size: 0.9rem;">Pemetaan infrastruktur akurat.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon" style="background-color: #1cc88a;">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div>
                            <h5>Aplikasi Pelanggan & Teknisi</h5>
                            <p class="text-muted" style="font-size: 0.9rem;">Sistem terintegrasi untuk respon cepat.</p>
                        </div>
                    </div>

                    <a href="{{ route('login') }}" class="btn btn-outline-primary mt-2">
                        Lihat Area Coverage (Login)
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="#" class="footer-brand">
                        <i class="fas fa-network-wired"></i> {{ config('app.name', 'MStore') }}
                    </a>
                    <p>Penyedia layanan internet fiber optic terpercaya dengan komitmen kualitas dan pelayanan terbaik.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div>
                    <h5 class="text-white mb-2">Layanan</h5>
                    <div class="footer-links">
                        <a href="#">Home Internet</a>
                        <a href="#">ATK Store</a>
                        <a href="#">Car Wash</a>
                    </div>
                </div>
                <div>
                    <h5 class="text-white mb-2">Support</h5>
                    <div class="footer-links">
                        <a href="#">Cek Tagihan</a>
                        <a href="#">Lapor Gangguan</a>
                        <a href="#">Coverage Area</a>
                    </div>
                </div>
                <div>
                    <h5 class="text-white mb-2">Hubungi Kami</h5>
                    <div class="footer-links">
                        <div class="mb-1"><i class="fas fa-map-marker-alt"></i> Jl. Raya Internet No. 123</div>
                        <div class="mb-1"><i class="fas fa-phone"></i> +62 877 7736 9687</div>
                        <div class="mb-1"><i class="fas fa-envelope"></i> support@mstore.com</div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 pt-4" style="border-top: 1px solid #2d3748;">
                <small>&copy; {{ date('Y') }} {{ config('app.name', 'MStore') }}. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        const html = document.documentElement;

        // Check local storage
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme) {
            html.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'dark') {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        }

        themeToggle.addEventListener('click', () => {
            if (html.getAttribute('data-theme') === 'dark') {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        });

        // Navbar Toggle
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Close menu when clicking a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
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
                // Try to find a valid ODP to center on
                for(let i=0; i < odps.length; i++) {
                    if(odps[i].latitude && odps[i].longitude) {
                        defaultLat = parseFloat(odps[i].latitude);
                        defaultLng = parseFloat(odps[i].longitude);
                        break;
                    }
                }
            }

            var map = L.map('coverageMap').setView([defaultLat, defaultLng], initialZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Add ODP Markers
            odps.forEach(function(odp) {
                if (odp.latitude && odp.longitude) {
                    var isFull = (odp.capacity !== null && odp.filled >= odp.capacity);
                    
                    L.circleMarker([odp.latitude, odp.longitude], {
                        radius: 8,
                        fillColor: isFull ? '#e74a3b' : '#4e73df',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map)
                    .bindPopup("<b>" + odp.name + "</b><br>Status: " + (isFull ? "Penuh" : "Tersedia"));
                }
            });
        });
    </script>
</body>
</html>
