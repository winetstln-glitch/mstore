<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="layout-navbar-fixed layout-wide" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MStore') }} - Internet, ATK & Services</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Landing Lite CSS -->
    <link href="{{ asset('css/landing-lite.css') }}?v={{ filemtime(public_path('css/landing-lite.css')) }}" rel="stylesheet">
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
<body>

    <!-- Navbar -->
    <nav class="navbar sticky-top">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center">
                    <a class="navbar-brand d-flex align-items-center fw-bold gap-2" href="#">
                        <img class="nav-logo" src="{{ asset('img/logo.png') }}" alt="Logo">
                        <span class="d-none d-sm-inline">{{ config('app.name', 'MStore') }}</span>
                    </a>
                </div>

                <div class="d-none d-lg-flex gap-4" id="navMenu">
                    <a class="nav-link" href="#home">Beranda</a>
                    @if(($canAttendanceFromLanding ?? false) === true)
                        <a class="nav-link" href="#absensi-karyawan">Absensi</a>
                    @endif
                    <a class="nav-link" href="#packages">Internet</a>
                    <a class="nav-link" href="#atk-promo">ATK Store</a>
                    <a class="nav-link" href="#wash-services">Auto Wash</a>
                    <a class="nav-link" href="#cctv">CCTV</a>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-icon rounded-circle bg-dark-subtle" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Masuk</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 fade-up">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 mb-3 rounded-pill">#1 Trusted Digital Partner</span>
                    <h1 class="hero-title">Solusi Digital & Layanan Terlengkap</h1>
                    <p class="hero-desc text-secondary fs-5 mb-4">Mulai dari internet fiber optic super cepat, perlengkapan kantor, hingga perawatan kendaraan profesional. Kami hadir untuk memudahkan hidup Anda.</p>
                    
                    <div class="d-flex flex-wrap gap-3">
                        <a href="https://buymstore.online" class="btn btn-primary">
                            <i class="fas fa-rocket me-2"></i> Client Area
                        </a>
                        <a href="{{ asset('apk/app-mstore.apk') }}" class="btn btn-outline-light" download>
                            <i class="fa-brands fa-android me-2"></i> Get App
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 fade-up d-none d-lg-block text-center">
                    <div class="position-relative">
                        <div class="opacity-10 position-absolute top-50 start-50 translate-middle rounded-circle w-100 h-100 blur-3xl"></div>
                        <img src="{{ asset('img/cctv-monitor.png') }}" class="img-fluid position-relative z-1" alt="Monitoring Center"
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=1000';">
                    </div>
                </div>
            </div>
        </div>
    </section>


    @php
        $cctvPackages = [
            [
                'speed' => \App\Models\Setting::getValue('cctv_package_1_speed', 'Basic'),
                'subtitle' => \App\Models\Setting::getValue('cctv_package_1_subtitle', '1 Kamera HD'),
                'price' => \App\Models\Setting::getValue('cctv_package_1_price', 'Rp 600Rb'),
                'features' => \App\Models\Setting::getValue('cctv_package_1_features', "Camera 1 Channel\nHDD 250GB\nFree Instalasi"),
            ],
            [
                'speed' => \App\Models\Setting::getValue('cctv_package_2_speed', 'Basic'),
                'subtitle' => \App\Models\Setting::getValue('cctv_package_2_subtitle', '2 Kamera HD'),
                'price' => \App\Models\Setting::getValue('cctv_package_2_price', 'Rp 1.1jt'),
                'features' => \App\Models\Setting::getValue('cctv_package_2_features', "Camera 2 Channel\nHDD 125GB\nFree Instalasi"),
            ],
            [
                'speed' => \App\Models\Setting::getValue('cctv_package_3_speed', 'Basic'),
                'subtitle' => \App\Models\Setting::getValue('cctv_package_3_subtitle', '2 Kamera HD'),
                'price' => \App\Models\Setting::getValue('cctv_package_3_price', 'Rp 1.9jt'),
                'features' => \App\Models\Setting::getValue('cctv_package_3_features', "DVR 4 Channel\nHDD 500GB\nFree Instalasi"),
            ],
            [
                'speed' => \App\Models\Setting::getValue('cctv_package_4_speed', 'Basic'),
                'subtitle' => \App\Models\Setting::getValue('cctv_package_4_subtitle', '4 Kamera HD'),
                'price' => \App\Models\Setting::getValue('cctv_package_4_price', 'Rp 1.9jt'),
                'features' => \App\Models\Setting::getValue('cctv_package_4_features', "DVR 4 Channel\nHDD 500GB\nFree Instalasi"),
            ],
        ];
        $landingHolidayStart = \App\Models\Setting::getValue('wash_holiday_pricing_start_date', '');
        $landingHolidayEnd = \App\Models\Setting::getValue('wash_holiday_pricing_end_date', '');
        $landingHolidayActive = !empty($landingHolidayStart) && !empty($landingHolidayEnd)
            && now()->toDateString() >= $landingHolidayStart
            && now()->toDateString() <= $landingHolidayEnd;
    @endphp
     <!-- Wash Services Section -->
    <section id="wash-services" class="py-2 bg-black bg-opacity-25">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up">
                <h6 class="text-primary fw-bold text-uppercase">MSTORE WASH</h6>
                <h2 class="display-6 fw-800">Layanan Cuci Mobil & Motor </h2>
                @if($landingHolidayActive)
                    <div class="landing-holiday-banner mt-2">
                        <i class="fas fa-calendar-check"></i>
                        <span>Harga Hari Raya aktif ({{ \Carbon\Carbon::parse($landingHolidayStart)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($landingHolidayEnd)->translatedFormat('d M Y') }})</span>
                    </div>
                @endif
            </div>
            
            <div class="scroll-container fade-up">
                @forelse($washServices as $service)
                <div class="scroll-item">
                    <div class="card">
                        @php
                            $landingAdjustment = is_null($service->holiday_price) ? null : (float) $service->holiday_price;
                            $landingEffectivePrice = $landingHolidayActive && !is_null($landingAdjustment)
                                ? max(0, ((float) $service->price) + $landingAdjustment)
                                : (float) $service->price;
                        @endphp
                        @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="product-img">
                        @else
                            <div class="product-img d-flex align-items-center justify-content-center bg-secondary bg-opacity-25">
                                <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }} fa-3x text-secondary"></i>
                            </div>
                        @endif
                        <div class="product-body d-flex flex-column h-100">
                            <div class="mb-2">
                                <span class="chip">
                                    <i class="fas {{ $service->vehicle_type == 'car' ? 'fa-car' : 'fa-motorcycle' }} me-1"></i>
                                    {{ ucfirst($service->vehicle_type) }}
                                </span>
                            </div>
                            <h4 class="product-title mb-1">{{ $service->name }}</h4>
                            <div class="product-price text-primary fw-bold mb-1">Rp {{ number_format($landingEffectivePrice, 0, ',', '.') }}</div>
                            @if(!is_null($landingAdjustment))
                                <div class="landing-holiday-chip mb-2">
                                    <i class="fas fa-sparkles"></i>
                                    <span>Hari Raya {{ $landingAdjustment >= 0 ? '+' : '-' }}Rp {{ number_format(abs($landingAdjustment), 0, ',', '.') }}</span>
                                    @if($landingHolidayActive)
                                        <strong class="ms-1">(aktif)</strong>
                                    @else
                                        <span class="ms-1">(jadwal belum aktif)</span>
                                    @endif
                                </div>
                            @endif
                            <p class="small text-muted mb-3">{!! nl2br(e($service->description ?: 'Layanan cuci bersih dan mengkilap.')) !!}</p>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20mau%20booking%20cuci%20{{ $service->vehicle_type }}:%20{{ urlencode($service->name) }}" class="btn btn-primary w-100 mt-auto">
                                <i class="fab fa-whatsapp me-2"></i> Booking
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                 <div class="text-center w-100 py-2">
                    <p class="text-muted">Layanan belum tersedia.</p>
                </div>
                @endforelse
            </div>
        </div>
   
    </section>
     <!-- Internet Section -->
    @php
        $inferInternetPackageType = function ($package) {
            $explicitType = \Illuminate\Support\Str::lower((string) ($package->package_type ?? ''));
            if (in_array($explicitType, ['pppoe', 'hotspot'], true)) {
                return $explicitType;
            }

            $haystack = \Illuminate\Support\Str::lower(trim(
                $package->name.' '.$package->speed.' '.($package->description ?? '')
            ));

            return \Illuminate\Support\Str::contains($haystack, ['hotspot', 'member', 'voucher'])
                ? 'hotspot'
                : 'pppoe';
        };

        $hotspotInternetPackages = $packages->filter(fn ($package) => $inferInternetPackageType($package) === 'hotspot')->values();
        $pppoeInternetPackages = $packages->filter(fn ($package) => $inferInternetPackageType($package) === 'pppoe')->values();
        $internetPromoEnabled = \App\Models\Setting::getValue('landing_internet_promo_enabled', '1') === '1';
        $internetPromoPercent = (int) \App\Models\Setting::getValue('landing_internet_promo_percent', '10');
        $internetPromoPercent = max(0, min($internetPromoPercent, 90));
        $internetPromoLabel = trim((string) \App\Models\Setting::getValue('landing_internet_promo_label', 'Promo Paket Internet'));
        $showInternetPromo = $internetPromoEnabled && $internetPromoPercent > 0;
        $formatInternetSpeed = function ($speedValue) {
            $speedText = trim((string) $speedValue);
            if ($speedText === '') {
                return '-';
            }

            if (preg_match('/^\d+$/', $speedText) === 1) {
                return $speedText.' Mbps';
            }

            return $speedText;
        };
    @endphp
    <section id="packages" class="py-2 bg-black bg-opacity-25 internet-packages-section">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up">
                <h6 class="text-primary fw-bold text-uppercase">Layanan Internet</h6>
                <h2 class="display-6 fw-800">Paket Internet Fiber</h2>
                @if($showInternetPromo)
                <div class="internet-promo-banner mt-3">
                    <i class="fas fa-bolt"></i>
                    <span>{{ $internetPromoLabel }} • Hemat {{ $internetPromoPercent }}%</span>
                </div>
                @endif
            </div>

            <div class="mb-3 fade-up">
                <h5 class="fw-bold mb-2">Paket Rumahan</h5>
                <div class="scroll-container">
                    @forelse($pppoeInternetPackages as $package)
                    @php
                        $packageFeatures = collect(preg_split('/\r\n|\r|\n/', (string) $package->description))
                            ->map(fn ($item) => trim($item))
                            ->filter()
                            ->values();
                        $packageDevicesText = is_null($package->devices_limit) ? 'Unlimited' : ((int) $package->devices_limit.' Devices');
                        $packageSpeedText = $formatInternetSpeed($package->speed);
                        $normalPrice = (int) $package->price;
                        $promoPrice = $showInternetPromo ? (int) round($normalPrice * ((100 - $internetPromoPercent) / 100)) : $normalPrice;
                        if ($packageFeatures->isEmpty()) {
                            $packageFeatures = collect(['100% Fiber Optic', 'Unlimited FUP']);
                        }
                    @endphp
                    <div class="scroll-item">
                        <div class="card">
                            @if($showInternetPromo)
                            <div class="internet-promo-ribbon">PROMO {{ $internetPromoPercent }}%</div>
                            @endif
                            <div class="pricing-header">
                                <div class="speed">{{ $package->name }}</div>
                                <div class="fw-bold">{{ $packageDevicesText }}</div>
                            </div>
                            <div class="pricing-body d-flex flex-column">
                                <div class="price text-primary">
                                    Rp {{ number_format($promoPrice, 0, ',', '.') }}
                                    <span class="fs-6 text-muted">/ bln</span>
                                </div>
                                @if($showInternetPromo)
                                <div class="internet-price-old mb-2">Normal Rp {{ number_format($normalPrice, 0, ',', '.') }}</div>
                                @endif
                                <h5 class="mb-3">{{ $packageSpeedText }}</h5>
                                <ul class="features">
                                    @foreach($packageFeatures as $feature)
                                    <li><i class="fas fa-check-circle text-primary"></i> {{ $feature }}</li>
                                    @endforeach
                                </ul>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20tertarik%20berlangganan%20paket%20{{ urlencode($package->name) }}" class="btn btn-primary w-100 mt-auto">
                                    Ambil Promo
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center w-100 py-2">
                        <p class="text-muted">Paket PPPoE / Rumahan belum tersedia.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="fade-up">
                <h5 class="fw-bold mb-2">Paket Hotspot / Member</h5>
                <div class="scroll-container">
                    @forelse($hotspotInternetPackages as $package)
                    @php
                        $packageFeatures = collect(preg_split('/\r\n|\r|\n/', (string) $package->description))
                            ->map(fn ($item) => trim($item))
                            ->filter()
                            ->values();
                        $packageDevicesText = is_null($package->devices_limit) ? 'Unlimited' : ((int) $package->devices_limit.' Devices');
                        $packageSpeedText = $formatInternetSpeed($package->speed);
                        $normalPrice = (int) $package->price;
                        $promoPrice = $showInternetPromo ? (int) round($normalPrice * ((100 - $internetPromoPercent) / 100)) : $normalPrice;
                        if ($packageFeatures->isEmpty()) {
                            $packageFeatures = collect(['Akses Cepat', 'Cocok untuk Voucher / Member']);
                        }
                    @endphp
                    <div class="scroll-item">
                        <div class="card">
                            @if($showInternetPromo)
                            <div class="internet-promo-ribbon">PROMO {{ $internetPromoPercent }}%</div>
                            @endif
                            <div class="pricing-header">
                                <div class="speed">{{ $packageSpeedText }}</div>
                                <div class="fw-bold">{{ $packageDevicesText }}</div>
                            </div>
                            <div class="pricing-body d-flex flex-column">
                                <div class="price text-primary">
                                    Rp {{ number_format($promoPrice, 0, ',', '.') }}
                                    <span class="fs-6 text-muted">/ bln</span>
                                </div>
                                @if($showInternetPromo)
                                <div class="internet-price-old mb-2">Normal Rp {{ number_format($normalPrice, 0, ',', '.') }}</div>
                                @endif
                                <h5 class="mb-3">{{ $package->name }}</h5>
                                <ul class="features">
                                    @foreach($packageFeatures as $feature)
                                    <li><i class="fas fa-check-circle text-primary"></i> {{ $feature }}</li>
                                    @endforeach
                                </ul>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20tertarik%20berlangganan%20paket%20{{ urlencode($package->name) }}" class="btn btn-primary w-100 mt-auto">
                                    Ambil Promo
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center w-100 py-2">
                        <p class="text-muted">Paket Hotspot / Member belum tersedia.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
    <!--CCTV-->
    <section id="cctv" class="py-2 bg-black bg-opacity-25">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up">
                <h6 class="text-primary fw-bold text-uppercase">{{ \App\Models\Setting::getValue('cctv_section_badge', 'Security Solutions') }}</h6>
                <h2 class="display-6 fw-800">{{ \App\Models\Setting::getValue('cctv_section_title', 'Paket Instalasi CCTV') }}</h2>
                <div class="mx-auto bg-primary mt-2" style="width: 50px; height: 3px;"></div>
            </div>
            
            <div class="scroll-container fade-up">
                @foreach($cctvPackages as $package)
                    @php
                        $features = collect(preg_split('/\r\n|\r|\n/', (string) $package['features']))
                            ->map(fn ($item) => trim($item))
                            ->filter()
                            ->values();
                    @endphp
                    <div class="scroll-item">
                        <div class="card">
                            <div class="pricing-header">
                                <div class="speed">{{ $package['speed'] }}</div>
                                <div class="text-muted">{{ $package['subtitle'] }}</div>
                            </div>
                            <div class="pricing-body d-flex flex-column">
                                <div class="price">{{ $package['price'] }}<small class="fs-6 text-muted">/paket</small></div>
                                <ul class="features">
                                    @foreach($features as $feature)
                                        <li><i class="fas fa-check-circle"></i> {{ $feature }}</li>
                                    @endforeach
                                </ul>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20minat%20paket%20CCTV%20{{ urlencode($package['speed']) }}" class="btn btn-primary mt-auto">Pesan Sekarang</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <!-- ATK Promo Section -->
    <section id="atk-promo" class="py-2 bg-black bg-opacity-25">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up">
                <h6 class="text-primary fw-bold text-uppercase">Stationery Store</h6>
                <h2 class="display-6 fw-800">Promo Alat Tulis Kantor</h2>
            </div>
            
            <div class="scroll-container fade-up">
                @forelse($atkProducts as $product)
                <div class="scroll-item">
                    <div class="card">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-img">
                        @else
                            <div class="product-img d-flex align-items-center justify-content-center bg-secondary bg-opacity-25">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        @endif
                        <div class="product-body d-flex flex-column h-100">
                            <div class="chip mb-2 align-self-start">{{ $product->category->name ?? 'ATK' }}</div>
                            <h5 class="product-title mb-1">{{ $product->name }}</h5>
                            <div class="product-price text-primary fw-bold mb-2">Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</div>
                            <p class="small text-muted mb-3">{{ Str::limit($product->description ?? 'Tersedia di toko kami.', 60) }}</p>
                            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20mau%20pesan%20ATK:%20{{ urlencode($product->name) }}" class="btn btn-primary w-100 mt-auto">
                                <i class="fab fa-whatsapp me-2"></i> Pesan
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center w-100 py-2">
                    <p class="text-muted">Belum ada promo produk saat ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

   

    @php
        $weddingServices = [
            [
                'badge' => \App\Models\Setting::getValue('wedding_service_1_badge', 'Wedding'),
                'name' => \App\Models\Setting::getValue('wedding_service_1_name', 'Hias Pengantin'),
                'description' => \App\Models\Setting::getValue('wedding_service_1_desc', 'Dekorasi pelaminan elegan untuk akad, resepsi, dan acara keluarga.'),
                'image' => \App\Models\Setting::getValue('wedding_service_1_image', 'storage/wash-services/SWCzU7EyNG0o3NCUZRdSxMXEPR19TqlaSxgSP26k.jpg'),
            ],
            [
                'badge' => \App\Models\Setting::getValue('wedding_service_2_badge', 'Photography'),
                'name' => \App\Models\Setting::getValue('wedding_service_2_name', 'Poto Moment'),
                'description' => \App\Models\Setting::getValue('wedding_service_2_desc', 'Dokumentasi foto momen spesial agar setiap detik berharga tetap terabadikan.'),
                'image' => \App\Models\Setting::getValue('wedding_service_2_image', 'storage/wash-services/JNp0g77R9K9equSk3DaVUIvE5GZjsIMqUeb6OEVm.jpg'),
            ],
            [
                'badge' => \App\Models\Setting::getValue('wedding_service_3_badge', 'Event Support'),
                'name' => \App\Models\Setting::getValue('wedding_service_3_name', 'Sewa Auning'),
                'description' => \App\Models\Setting::getValue('wedding_service_3_desc', 'Penyewaan auning untuk area tamu, panggung, dan kebutuhan acara outdoor.'),
                'image' => \App\Models\Setting::getValue('wedding_service_3_image', 'storage/wash-services/fUlfmV40jz1rCp0CC2WTtXnazm1or6ANVVJs9SI8.jpg'),
            ],
        ];
        $weddingOverlayStyles = [
            'background: linear-gradient(160deg, rgba(8, 20, 43, 0.25) 0%, rgba(2, 10, 25, 0.85) 100%);',
            'background: linear-gradient(160deg, rgba(43, 8, 32, 0.28) 0%, rgba(25, 2, 18, 0.86) 100%);',
            'background: linear-gradient(160deg, rgba(9, 42, 40, 0.25) 0%, rgba(2, 24, 22, 0.84) 100%);',
        ];
    @endphp
    <section id="wedding-services" class="py-2">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up">
                <h6 class="text-primary fw-bold text-uppercase">{{ \App\Models\Setting::getValue('wedding_section_badge', 'Event Services') }}</h6>
                <h2 class="display-6 fw-800">{{ \App\Models\Setting::getValue('wedding_section_title', 'Layanan Wedding & Event') }}</h2>
            </div>

            <div class="scroll-container fade-up">
                @foreach($weddingServices as $index => $service)
                    <div class="scroll-item">
                        <div class="card position-relative overflow-hidden border-0">
                            <img src="{{ str_starts_with($service['image'], 'http') ? $service['image'] : asset($service['image']) }}" alt="{{ $service['name'] }}" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="{{ $weddingOverlayStyles[$index] ?? $weddingOverlayStyles[0] }}"></div>
                            <div class="product-body d-flex flex-column h-100 position-relative text-white" style="min-height: 250px;">
                                <div class="chip mb-2 align-self-start" style="background: rgba(255, 255, 255, 0.92); color: #0f172a;">{{ $service['badge'] }}</div>
                                <h4 class="product-title mb-1">{{ $service['name'] }}</h4>
                                <p class="small text-white-50 mb-3">{{ $service['description'] }}</p>
                                <a href="https://wa.me/{{ $waNumber }}?text=Halo%20saya%20minat%20layanan%20{{ urlencode($service['name']) }}" class="btn btn-light text-dark w-100 mt-auto">
                                    <i class="fab fa-whatsapp me-2"></i> Konsultasi
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Coverage Map Section -->
    <section id="monitoring" class="py-2">
        <div class="container py-2">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 fade-up">
                    <div id="coverageMap"></div>
                </div>
                <div class="col-lg-6 fade-up">
                    <h2 class="display-6 fw-800 mb-4">Pantau Jaringan Real-Time</h2>
                    <p class="text-secondary mb-4">Kami mengelola ribuan ODP secara transparan. Anda bisa mengecek ketersediaan jaringan di area Anda melalui peta interaktif kami.</p>
                    
                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary">
                                <i class="fas fa-satellite-dish fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Transparansi ODP</h6>
                                <p class="small text-muted mb-0">Status ketersediaan port secara real-time di tiap titik.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-start">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary">
                                <i class="fas fa-shield-halved fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Respon Cepat 24/7</h6>
                                <p class="small text-muted mb-0">Tim teknisi siaga memantau stabilitas koneksi Anda.</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">Buka Peta Lengkap</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom Navigation Mobile -->
    <div class="bottom-bar fixed-bottom d-lg-none">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center px-2">
                <a href="#home" class="bottom-item active d-flex flex-column align-items-center text-decoration-none">
                    <i class="fas fa-home mb-1"></i>
                    <span>Home</span>
                </a>
                <a href="#packages" class="bottom-item d-flex flex-column align-items-center text-decoration-none">
                    <i class="fas fa-wifi mb-1"></i>
                    <span>Net</span>
                </a>
                <a href="#atk-promo" class="bottom-item d-flex flex-column align-items-center text-decoration-none">
                    <i class="fas fa-shopping-bag mb-1"></i>
                    <span>ATK</span>
                </a>
                <a href="{{ route('login') }}" class="bottom-item d-flex flex-column align-items-center text-decoration-none">
                    <i class="fas fa-user-circle mb-1"></i>
                    <span>User</span>
                </a>
            </div>
        </div>
    </div>

    <!-- AI Chat Widget -->
    <div id="ai-chat-widget" class="fixed-bottom m-4 d-flex justify-content-end" style="z-index: 1050; pointer-events: none;">
        <div class="chat-container d-none" style="pointer-events: auto; width: 350px; height: 500px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div class="chat-header p-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-robot"></i>
                    <span class="fw-bold">MStore AI Assistant</span>
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

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // AI Chat Logic
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

            // Add user message
            addMessage(message, 'end');
            input.value = '';
            
            // Show typing indicator
            const loadingId = addMessage('Sedang mengetik...', 'start', true);

            try {
                const response = await fetch({{ Js::from(route('ai.chat')) }}, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                
                // Remove typing indicator
                document.getElementById(loadingId).remove();
                
                // Add AI response
                addMessage(data.reply || 'Maaf, saya tidak mengerti.', 'start');
            } catch (error) {
                document.getElementById(loadingId).remove();
                addMessage('Maaf, terjadi kesalahan koneksi.', 'start');
            }
        }

        function addMessage(text, align, isLoading = false) {
            const messages = document.getElementById('chat-messages');
            const id = 'msg-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = `p-2 rounded-3 align-self-${align} ${align === 'end' ? 'bg-primary text-white' : 'bg-secondary bg-opacity-25'}`;
            div.style.maxWidth = '80%';
            div.innerHTML = text; // Allow HTML in response
            messages.appendChild(div);
            
            // Wrapper for spacing
            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex flex-column gap-2 mb-2';
            wrapper.appendChild(div);
            messages.appendChild(wrapper);

            messages.scrollTop = messages.scrollHeight;
            return id;
        }

        // Map Initialization
        document.addEventListener('DOMContentLoaded', function() {
            const mapContainer = document.getElementById('coverageMap');
            if (mapContainer) {
                const map = L.map('coverageMap').setView([-6.200000, 106.816666], 13); // Default Jakarta

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Add ODP Markers
                const odps = {{ Js::from($odps ?? []) }};
                const markers = [];

                odps.forEach(odp => {
                    if (odp.latitude && odp.longitude) {
                        const marker = L.marker([odp.latitude, odp.longitude])
                            .bindPopup(`<b>${odp.name}</b><br>Status: ${odp.status}<br>Port Tersedia: ${odp.available_ports ?? 'N/A'}`);
                        marker.addTo(map);
                        markers.push(marker);
                    }
                });

                // Fit bounds if markers exist
                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                }
            }
        });

        // Logika JavaScript asli Anda tetap berfungsi di sini
        // Tambahkan script inisialisasi AOS-like effect
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

        // Logic Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentLandingTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        themeToggle.querySelector('i').className = currentLandingTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeToggle.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });

        const attendanceForms = document.querySelectorAll('.landing-attendance-form');
        attendanceForms.forEach((form) => {
            const photoInput = form.querySelector('.landing-photo-input');
            const preview = form.querySelector('.landing-preview');
            if (photoInput && preview) {
                photoInput.addEventListener('change', (event) => {
                    const [file] = event.target.files;
                    if (!file) {
                        preview.src = '#';
                        preview.classList.add('d-none');
                        return;
                    }
                    const objectUrl = URL.createObjectURL(file);
                    preview.src = objectUrl;
                    preview.classList.remove('d-none');
                });
            }
        });

        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition((position) => {
                document.querySelectorAll('.landing-latitude').forEach((input) => {
                    input.value = position.coords.latitude;
                });
                document.querySelectorAll('.landing-longitude').forEach((input) => {
                    input.value = position.coords.longitude;
                });
            });
        }
    </script>
</body>
</html>
