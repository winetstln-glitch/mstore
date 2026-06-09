@extends('layouts.landing-public')

@section('content')
    @php
        $siteName = $storeName ?? config('app.name', 'MStore');
        $waUrlBase = 'https://wa.me/'.$waNumber;
        $secondaryHref = $servicePage['secondary_href'] ?? '#service-lead';
        if (str_starts_with($secondaryHref, 'wa:')) {
            $secondaryHref = $waUrlBase.'?text='.urlencode(substr($secondaryHref, 3));
        }
        $weddingFallbackGallery = collect([
            'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&q=80&w=1200',
            'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&q=80&w=1200',
            'https://images.unsplash.com/photo-1522673607200-164d1b6ce486?auto=format&fit=crop&q=80&w=1200',
        ]);
        $weddingHeroGallery = collect($weddingGallery ?? [])->map(function ($img) {
            return str_starts_with((string) $img, 'http') ? $img : asset($img);
        })->filter()->values();
        if ($weddingHeroGallery->isEmpty()) {
            $weddingHeroGallery = $weddingFallbackGallery;
        }
    @endphp

    @if($servicePage['slug'] === 'wedding-event')
        <div class="wedding-topbar">
            <div class="container text-center">
                Promo Spesial Tahun Ini - Bonus Handbouquet dan Undangan Digital untuk paket wedding tertentu.
            </div>
        </div>

        <section class="wedding-hero">
            <div class="wedding-hero-glow wedding-hero-glow-left"></div>
            <div class="wedding-hero-glow wedding-hero-glow-right"></div>
            <div class="container position-relative">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7 fade-up text-center text-lg-start">
                        <span class="wedding-pill">Premium & Elegant Wedding Organizer</span>
                        <h1 class="wedding-hero-title">Wujudkan Pernikahan Impian Anda dengan Dekorasi Elegan dan Biaya yang Lebih Terarah</h1>
                        <p class="wedding-hero-desc">{{ $servicePage['hero_desc'] }}</p>
                        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-center justify-content-lg-start gap-3">
                            <a href="#wedding-packages" class="btn wedding-btn-dark track-service-action" data-track-service="wedding-event" data-track-action="hero_packages">
                                Lihat Pilihan Paket
                            </a>
                            <a href="#wedding-calculator" class="btn wedding-btn-light track-service-action" data-track-service="wedding-event" data-track-action="hero_calculator">
                                Simulasi & Kalkulator
                            </a>
                        </div>

                        <div class="wedding-stats">
                            <div class="wedding-stat">
                                <strong>{{ ($weddingPackages ?? collect())->count() > 0 ? ($weddingPackages ?? collect())->count() : 4 }}</strong>
                                <span>Pilihan Paket</span>
                            </div>
                            <div class="wedding-stat">
                                <strong>Premium</strong>
                                <span>Dekor Elegan</span>
                            </div>
                            <div class="wedding-stat">
                                <strong>Custom</strong>
                                <span>Konsep Acara</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 fade-up d-none d-lg-block">
                        <div class="wedding-hero-frame">
                            <div class="wedding-hero-main-card">
                                <div class="wedding-hero-media">
                                    <img id="weddingHeroCarousel" src="{{ $weddingHeroGallery->first() }}" alt="Wedding Preview" class="wedding-hero-carousel" data-images='@json($weddingHeroGallery->values())'>
                                    <div class="wedding-hero-overlay">
                                        <div>
                                            <p class="wedding-overlay-kicker">Premium Setup</p>
                                            <h3 class="wedding-overlay-title">{{ $siteName }} Decoration</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="wedding-hero-card-footer">
                                    <div>
                                        <h4>{{ strtoupper($siteName) }}</h4>
                                        <p>Simple • Elegan • Hemat</p>
                                    </div>
                                    <span class="wedding-badge-soft">Promo</span>
                                </div>
                            </div>

                            <div class="wedding-floating-card wedding-floating-card-dark">
                                <div class="wedding-floating-icon"><i class="fas fa-sparkles"></i></div>
                                <div>
                                    <p>Kualitas</p>
                                    <strong>Dekor Premium</strong>
                                </div>
                            </div>

                            <div class="wedding-floating-card wedding-floating-card-green">
                                <div class="wedding-floating-icon"><i class="fab fa-whatsapp"></i></div>
                                <div>
                                    <p>Hubungi Kami</p>
                                    <strong>{{ $waNumber }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="hero service-hero">
            <div class="container">
                <div class="row align-items-center g-5 service-hero-grid">
                    <div class="col-lg-7 fade-up service-hero-copy">
                        <span class="service-hero-kicker">{{ $servicePage['kicker'] }}</span>
                        <h1 class="hero-title">{{ $servicePage['hero_title'] }}</h1>
                        <p class="hero-desc text-secondary fs-5 mb-4 service-hero-desc">{{ $servicePage['hero_desc'] }}</p>
                        <div class="hero-actions service-hero-actions">
                            <a href="#service-lead" class="btn btn-primary track-service-action" data-track-service="{{ $servicePage['slug'] }}" data-track-action="hero_primary_cta">
                                <i class="fas {{ $servicePage['icon'] }} me-2"></i> {{ $servicePage['form']['title'] }}
                            </a>
                            <a href="{{ $secondaryHref }}" class="btn btn-outline-primary track-service-action" data-track-service="{{ $servicePage['slug'] }}" data-track-action="hero_secondary_cta">
                                {{ $servicePage['secondary_label'] }}
                            </a>
                        </div>
                        <div class="landing-trust mt-4 service-hero-trust">
                            @foreach($servicePage['highlights'] as $highlight)
                                <div class="landing-trust-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{ $highlight }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-5 fade-up d-none d-lg-block">
                        <div class="service-hero-visual">
                            <div class="service-hero-art">
                                <img src="{{ $servicePage['image'] }}" class="img-fluid position-relative z-1" alt="{{ $servicePage['name'] }}" loading="lazy" decoding="async">
                            </div>
                            <div class="service-hero-floating service-hero-floating-primary">
                                <div class="service-hero-floating-icon"><i class="fas {{ $servicePage['icon'] }}"></i></div>
                                <div>
                                    <strong>{{ $servicePage['name'] }}</strong>
                                    <span>{{ $servicePage['stat'] ?? 'Layanan siap' }}</span>
                                </div>
                            </div>
                            <div class="service-hero-floating service-hero-floating-green">
                                <div class="service-hero-floating-icon"><i class="fab fa-whatsapp"></i></div>
                                <div>
                                    <strong>Fast Response</strong>
                                    <span>{{ $waNumber }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($servicePage['slug'] === 'internet')
        @php
            $inferInternetPackageType = function ($package) {
                $explicitType = \Illuminate\Support\Str::lower((string) ($package->package_type ?? ''));
                if (in_array($explicitType, ['pppoe', 'hotspot'], true)) {
                    return $explicitType;
                }

                $haystack = \Illuminate\Support\Str::lower(trim($package->name.' '.$package->speed.' '.($package->description ?? '')));

                return \Illuminate\Support\Str::contains($haystack, ['hotspot', 'member', 'voucher']) ? 'hotspot' : 'pppoe';
            };
            $hotspotInternetPackages = $packages->filter(fn ($package) => $inferInternetPackageType($package) === 'hotspot')->values();
            $pppoeInternetPackages = $packages->filter(fn ($package) => $inferInternetPackageType($package) === 'pppoe')->values();
            $voucherProfiles = collect($voucherTemplates ?? [])->values();
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
        <section class="py-2">
            <div class="container py-2">
                <div class="section-header text-center mb-4 fade-up">
                    <h2 class="display-6 fw-800">Paket Internet Fiber</h2>
                    <p class="text-muted mb-0">Pilih paket rumah atau hotspot sesuai kebutuhan Anda.</p>
                </div>

                <div class="mb-4 fade-up">
                    <h5 class="fw-bold mb-2">Paket Rumah / PPPoE</h5>
                    <div class="scroll-container">
                        @forelse($pppoeInternetPackages as $package)
                            @php
                                $packageFeatures = collect(preg_split('/\r\n|\r|\n/', (string) $package->description))
                                    ->map(fn ($item) => trim($item))
                                    ->filter()
                                    ->values();
                                if ($packageFeatures->isEmpty()) {
                                    $packageFeatures = collect(['100% Fiber Optic', 'Cocok untuk rumah dan bisnis']);
                                }
                            @endphp
                            <div class="scroll-item">
                                <div class="card">
                                    <div class="pricing-header">
                                        <div class="speed">{{ $package->name }}</div>
                                        <div class="fw-bold">{{ is_null($package->devices_limit) ? 'Unlimited Devices' : ((int) $package->devices_limit.' Devices') }}</div>
                                    </div>
                                    <div class="pricing-body d-flex flex-column">
                                        <div class="price text-primary">Rp {{ number_format((int) $package->price, 0, ',', '.') }} <span class="fs-6 text-muted">/ bln</span></div>
                                        <h5 class="mb-3">{{ $formatInternetSpeed($package->speed) }}</h5>
                                        <ul class="features">
                                            @foreach($packageFeatures as $feature)
                                                <li><i class="fas fa-check-circle text-primary"></i> {{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                        <a href="#service-lead" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="internet" data-track-action="package_cta" data-track-label="{{ $package->name }}">
                                            Pilih Paket
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center w-100 py-2"><p class="text-muted">Paket internet belum tersedia.</p></div>
                        @endforelse
                    </div>
                </div>

                <div class="fade-up">
                    <h5 class="fw-bold mb-2">Voucher Hotspot</h5>
                    <div class="scroll-container">
                        @forelse($voucherProfiles->take(6) as $profile)
                            <div class="scroll-item">
                                <div class="card">
                                    <div class="pricing-header">
                                        <div class="speed">{{ $profile->name }}</div>
                                        <div class="text-muted">{{ $profile->rate_limit ?: 'Voucher Hotspot' }}</div>
                                    </div>
                                    <div class="pricing-body d-flex flex-column">
                                        <div class="price text-primary">Rp {{ number_format((float) $profile->price, 0, ',', '.') }}</div>
                                        <div class="small text-muted mb-2">Durasi: {{ format_duration($profile->duration_seconds) }}</div>
                                        <div class="small text-muted mb-3">Quota: {{ $profile->quota_mb ? ((int) $profile->quota_mb.' MB') : 'Unlimited' }}</div>
                                        <a href="{{ route('voucher.payment.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="internet" data-track-action="voucher_qris">
                                            Beli Voucher (QRIS)
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center w-100 py-2"><p class="text-muted">Voucher hotspot belum tersedia.</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section id="coverage-area" class="py-2 bg-black bg-opacity-25">
            <div class="container py-2">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6 fade-up">
                        <div id="coverageMap"></div>
                    </div>
                    <div class="col-lg-6 fade-up">
                        <h2 class="display-6 fw-800 mb-4">Coverage Area</h2>
                        <p class="text-secondary mb-4">Cek peta ODP dan ketersediaan port, lalu lanjutkan registrasi dari form di bawah.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="#service-lead" class="btn btn-primary track-service-action" data-track-service="internet" data-track-action="coverage_to_form">Lanjutkan Registrasi</a>
                            <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin cek coverage area internet. Alamat saya: ') }}" class="btn btn-green track-service-action" data-track-service="internet" data-track-action="coverage_whatsapp">
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @elseif($servicePage['slug'] === 'wedding-event')
        @php
            $defaultWeddingPackages = collect([
                [
                    'slug' => 'bronze',
                    'name' => 'Paket Bronze',
                    'price' => 8500000,
                    'normal_price' => 12500000,
                    'capacity' => '50 pax',
                    'description' => 'Paket awal yang tetap elegan dan lengkap untuk akad atau resepsi sederhana.',
                    'facilities' => ['Pelaminan 5 m', 'Tenda serut', 'Standing flowers', 'Meja tamu', 'Lighting dekorasi', 'Rias pengantin', 'Dokumentasi foto', 'Handbouquet'],
                ],
                [
                    'slug' => 'silver',
                    'name' => 'Paket Silver',
                    'price' => 12500000,
                    'normal_price' => 15500000,
                    'capacity' => '70 pax',
                    'description' => 'Lebih lengkap untuk acara keluarga besar dengan dekor dan dokumentasi yang lebih matang.',
                    'facilities' => ['Pelaminan 6 m', 'Set akad', 'Kursi + cover', 'Welcome sign', 'Meja parasmanan', 'Rias pengantin', 'Album foto', 'Undangan digital'],
                ],
                [
                    'slug' => 'gold',
                    'name' => 'Paket Gold',
                    'price' => 15500000,
                    'normal_price' => 20500000,
                    'capacity' => '100 pax',
                    'description' => 'Paket favorit untuk konsep modern-elegan dengan komponen acara yang lebih lengkap.',
                    'facilities' => ['Pelaminan 6 m', 'Welcome gate', 'Sound system standar', 'Kursi + cover 100', 'Lighting dekorasi', 'Rias & busana', 'Album custom', 'Nail art'],
                ],
                [
                    'slug' => 'platinum',
                    'name' => 'Paket Platinum',
                    'price' => 20500000,
                    'normal_price' => 27500000,
                    'capacity' => '120 pax',
                    'description' => 'Paket premium untuk wedding yang lebih megah, dokumentasi lebih lengkap, dan tampilan acara yang lebih mewah.',
                    'facilities' => ['Pelaminan 6 m premium', 'Gazebo', 'Sound system gantung', 'Kursi + cover 120', 'Video cinematic', 'Rias pengantin 3x ganti', 'Album colase', 'Handbouquet premium'],
                ],
            ]);
            $weddingPackageSource = ($weddingPackages ?? collect())->values();
            $weddingPackageCards = ($weddingPackageSource->count() > 0 ? $weddingPackageSource : $defaultWeddingPackages)->values()->take(4)->map(function ($pkg, $index) use ($weddingHeroGallery) {
                $facilities = collect($pkg['facilities'] ?? $pkg->facilities ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->values();
                if ($facilities->isEmpty()) {
                    $description = trim((string) ($pkg['description'] ?? $pkg->description ?? ''));
                    $facilities = collect(preg_split('/\r\n|\r|\n/', $description))->map(fn ($item) => trim((string) $item))->filter()->values();
                }
                if ($facilities->isEmpty()) {
                    $facilities = collect(['Dekorasi elegan', 'Rias pengantin', 'Dokumentasi acara', 'Bisa custom sesuai kebutuhan']);
                }
                $image = $weddingHeroGallery->get($index) ?? $weddingHeroGallery->first();

                return [
                    'slug' => $pkg['slug'] ?? \Illuminate\Support\Str::slug((string) ($pkg['name'] ?? $pkg->name ?? 'paket-'.$index)),
                    'name' => $pkg['name'] ?? $pkg->name ?? 'Paket Wedding',
                    'price' => (int) ($pkg['price'] ?? $pkg->price ?? 0),
                    'normal_price' => (int) ($pkg['normal_price'] ?? max((int) (($pkg['price'] ?? $pkg->price ?? 0) * 1.25), (int) ($pkg['price'] ?? $pkg->price ?? 0))),
                    'capacity' => $pkg['capacity'] ?? ($pkg->capacity ? ((int) $pkg->capacity.' pax') : 'Custom'),
                    'description' => trim((string) ($pkg['description'] ?? $pkg->description ?? 'Paket custom tersedia sesuai kebutuhan acara Anda.')),
                    'facilities' => $facilities->take(16)->values()->all(),
                    'image' => $image,
                ];
            })->values();
            $weddingTestimonials = [
                ['name' => 'Nisa & Fajar', 'package' => 'Paket Silver', 'quote' => 'Harga sangat terjangkau dengan hasil dekorasi yang terlihat premium di foto dan di lokasi acara.'],
                ['name' => 'Dinda & Arif', 'package' => 'Paket Platinum', 'quote' => 'Tim responsif dari konsultasi sampai hari H. Dekor, rias, dan dokumentasi terasa jauh lebih rapi dan profesional.'],
            ];
            $weddingAddonOptions = [
                ['id' => 'piring', 'label' => 'Tambah Piring, Sendok + Garpu (+50 pcs)', 'price' => 500000],
                ['id' => 'sound', 'label' => 'Upgrade Sound System Gantung', 'price' => 1500000],
                ['id' => 'video', 'label' => 'Upgrade Video Cinematic', 'price' => 2000000],
                ['id' => 'henna', 'label' => 'Upgrade Henna Art Eksklusif', 'price' => 300000],
            ];
        @endphp

        <section id="keunggulan" class="wedding-section wedding-section-white">
            <div class="container">
                <div class="wedding-section-heading text-center fade-up">
                    <span class="wedding-section-kicker">Mengapa Memilih Kami</span>
                    <h2 class="wedding-section-title">Keunggulan Layanan Wedding {{ $siteName }}</h2>
                    <p class="wedding-section-subtitle">Tampilan dan alur dibuat lebih premium, konsultatif, dan mudah diarahkan ke booking.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-4 fade-up">
                        <div class="wedding-feature-card">
                            <div class="wedding-feature-icon"><i class="fas fa-award"></i></div>
                            <h3>Kualitas Terbaik</h3>
                            <p>Paket lengkap dengan pendekatan elegan tanpa membuat customer bingung membaca banyak blok informasi campur.</p>
                        </div>
                    </div>
                    <div class="col-md-4 fade-up">
                        <div class="wedding-feature-card">
                            <div class="wedding-feature-icon"><i class="fas fa-spa"></i></div>
                            <h3>Dekor Elegan</h3>
                            <p>Nuansa warna lembut, serif display, dan visual yang lebih emosional membuat wedding tampil lebih premium.</p>
                        </div>
                    </div>
                    <div class="col-md-4 fade-up">
                        <div class="wedding-feature-card">
                            <div class="wedding-feature-icon"><i class="fas fa-users"></i></div>
                            <h3>Pelayanan Profesional</h3>
                            <p>Lead form spesifik untuk tanggal acara, jumlah tamu, dan jenis acara mempermudah tim follow up.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="wedding-packages" class="wedding-section wedding-section-cream">
            <div class="container">
                <div class="wedding-section-heading text-center fade-up">
                    <span class="wedding-section-kicker">Paket Wedding Best Seller</span>
                    <h2 class="wedding-section-title">Temukan Paket Sesuai Kebutuhan Acara</h2>
                    <p class="wedding-section-subtitle">Saya adaptasikan konsep tab paket dari referensi agar lebih fokus dan mudah dibanding scroll banyak kartu.</p>
                </div>

                <div class="wedding-package-tabs fade-up">
                    @foreach($weddingPackageCards as $index => $pkg)
                        <button
                            type="button"
                            class="wedding-tab-btn {{ $index === 0 ? 'active' : '' }}"
                            data-wedding-package-btn="{{ $pkg['slug'] }}"
                            onclick="switchWeddingPackage('{{ $pkg['slug'] }}')">
                            {{ $pkg['name'] }} ({{ number_format($pkg['price'] / 1000000, 1, ',', '.') }}jt)
                        </button>
                    @endforeach
                </div>

                <div id="weddingPackageDisplay" class="wedding-package-display fade-up"></div>
            </div>
        </section>

        <section class="wedding-section wedding-section-white">
            <div class="container">
                <div class="wedding-section-heading text-center fade-up">
                    <span class="wedding-section-kicker">Inspirasi Pengantin</span>
                    <h2 class="wedding-section-title">Gallery Wedding & Event</h2>
                </div>
                <div class="wedding-gallery-grid fade-up">
                    @foreach($weddingHeroGallery->take(5) as $img)
                        <div class="wedding-gallery-card">
                            <img src="{{ $img }}" alt="Gallery Wedding" loading="lazy" decoding="async">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="wedding-calculator" class="wedding-section wedding-section-dark">
            <div class="container">
                <div class="row g-5 align-items-start">
                    <div class="col-lg-5 fade-up">
                        <span class="wedding-section-kicker wedding-section-kicker-light">Kalkulator Budget</span>
                        <h2 class="wedding-dark-title">Simulasikan Anggaran dan Dapatkan Rekomendasi Paket</h2>
                        <p class="wedding-dark-desc">Bagian ini mengikuti ide referensi `weding.html`: calon customer bisa mulai dari paket dasar, tambah kebutuhan tambahan, lalu kirim ringkasan ke WhatsApp.</p>
                        <div class="wedding-dark-checklist">
                            <div><i class="fas fa-check-circle"></i><span>Bebas custom sesuai request acara</span></div>
                            <div><i class="fas fa-check-circle"></i><span>Harga lebih transparan</span></div>
                            <div><i class="fas fa-check-circle"></i><span>Langsung lanjut konsultasi via WhatsApp</span></div>
                        </div>
                    </div>
                    <div class="col-lg-7 fade-up">
                        <div class="wedding-calculator-card">
                            <h3><i class="fas fa-calculator text-warning me-2"></i>Kalkulator Simulasi Paket</h3>
                            <div class="wedding-calc-group">
                                <label>Pilih Dasar Paket</label>
                                <div class="wedding-calc-package-grid">
                                    @foreach($weddingPackageCards as $index => $pkg)
                                        <label class="wedding-calc-radio">
                                            <input type="radio" name="wedding-calc-package" value="{{ $pkg['slug'] }}" {{ $index === 0 ? 'checked' : '' }} onchange="updateWeddingCalculator()">
                                            <span>{{ $pkg['name'] }}</span>
                                            <small>{{ 'Rp '.number_format($pkg['price'], 0, ',', '.') }}</small>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="wedding-calc-group">
                                <label>Tambahan Kebutuhan</label>
                                <div class="row g-3">
                                    @foreach($weddingAddonOptions as $addon)
                                        <div class="col-md-6">
                                            <label class="wedding-addon-card">
                                                <input type="checkbox" data-wedding-addon="{{ $addon['id'] }}" value="{{ $addon['price'] }}" onchange="updateWeddingCalculator()">
                                                <div>
                                                    <strong>{{ $addon['label'] }}</strong>
                                                    <small>+ Rp {{ number_format($addon['price'], 0, ',', '.') }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="wedding-calc-total">
                                <div>
                                    <span>Estimasi Total</span>
                                    <strong id="weddingCalcTotal">Rp 0</strong>
                                    <small id="weddingCalcNormal">Rp 0</small>
                                </div>
                                <button type="button" class="btn btn-green track-service-action" data-track-service="wedding-event" data-track-action="calculator_whatsapp" onclick="shareWeddingSimulation()">
                                    Kirim Rencana ke WhatsApp
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="testimoni" class="wedding-section wedding-section-white">
            <div class="container">
                <div class="wedding-section-heading text-center fade-up">
                    <span class="wedding-section-kicker">Testimoni</span>
                    <h2 class="wedding-section-title">Suara Pasangan Bahagia</h2>
                </div>
                <div class="row g-4">
                    @foreach($weddingTestimonials as $testi)
                        <div class="col-md-6 fade-up">
                            <div class="wedding-testimonial-card">
                                <div class="wedding-stars">
                                    @for($i = 0; $i < 5; $i++)
                                        <i class="fas fa-star"></i>
                                    @endfor
                                </div>
                                <p>"{{ $testi['quote'] }}"</p>
                                <div class="wedding-testi-meta">
                                    <div class="wedding-avatar">{{ strtoupper(substr($testi['name'], 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $testi['name'] }}</strong>
                                        <span>{{ $testi['package'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="wedding-section wedding-section-cream wedding-cta-band">
            <div class="container">
                <div class="wedding-cta-shell fade-up">
                    <div>
                        <span class="wedding-section-kicker">Konsultasi Wedding</span>
                        <h2 class="wedding-section-title mb-2">Sudah Punya Gambaran Konsep Acara?</h2>
                        <p class="wedding-section-subtitle">Kirim detail tanggal, lokasi, dan jumlah tamu. Tim kami bantu arahkan ke paket yang paling pas atau susun penawaran custom.</p>
                    </div>
                    <div class="wedding-cta-actions">
                        <a href="#service-lead" class="btn wedding-btn-dark track-service-action" data-track-service="wedding-event" data-track-action="cta_band_form">
                            Isi Form Wedding
                        </a>
                        <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi wedding dan cek paket yang cocok.') }}" class="btn btn-green track-service-action" data-track-service="wedding-event" data-track-action="cta_band_whatsapp">
                            WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <script>
            const weddingPackageData = @json($weddingPackageCards);
            const weddingAddonOptions = @json($weddingAddonOptions);
            const weddingBrandName = @json($siteName);

            function formatWeddingIDR(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount);
            }

            function switchWeddingPackage(slug) {
                const target = weddingPackageData.find(pkg => pkg.slug === slug) || weddingPackageData[0];
                if (!target) return;

                document.querySelectorAll('[data-wedding-package-btn]').forEach((btn) => {
                    btn.classList.toggle('active', btn.getAttribute('data-wedding-package-btn') === slug);
                });

                const display = document.getElementById('weddingPackageDisplay');
                const leftFacilities = (target.facilities || []).slice(0, 8);
                const rightFacilities = (target.facilities || []).slice(8);

                display.innerHTML = `
                    <div class="wedding-package-shell">
                        <div class="wedding-package-highlight">
                            <span class="wedding-package-chip">Paket Utama</span>
                            <h3>${target.name}</h3>
                            <div class="wedding-package-price">${formatWeddingIDR(target.price)}</div>
                            <div class="wedding-package-normal">Harga normal ${formatWeddingIDR(target.normal_price || target.price)}</div>
                            <p>${target.description || ''}</p>
                            <div class="wedding-package-meta">Kapasitas: ${target.capacity || 'Custom'}</div>
                            <a href="#service-lead" class="btn wedding-btn-gold w-100 mt-3 track-service-action" data-track-service="wedding-event" data-track-action="dynamic_package_cta" data-track-label="${target.name}">
                                Booking Paket Ini
                            </a>
                        </div>
                        <div class="wedding-package-detail">
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-5">
                                    <img src="${target.image}" alt="${target.name}" class="wedding-package-image">
                                </div>
                                <div class="col-lg-7">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="wedding-package-list">
                                                <h4><i class="fas fa-tents me-2"></i>Fasilitas Utama</h4>
                                                <ul>
                                                    ${leftFacilities.map(item => `<li><span>✔</span><span>${item}</span></li>`).join('')}
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wedding-package-list">
                                                <h4><i class="fas fa-gift me-2"></i>Highlight Paket</h4>
                                                <ul>
                                                    ${(rightFacilities.length ? rightFacilities : leftFacilities.slice(0, 6)).map(item => `<li><span>✔</span><span>${item}</span></li>`).join('')}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            function updateWeddingCalculator() {
                const selectedPackage = document.querySelector('input[name="wedding-calc-package"]:checked');
                if (!selectedPackage) return;
                const target = weddingPackageData.find(pkg => pkg.slug === selectedPackage.value) || weddingPackageData[0];
                let total = Number(target.price || 0);
                let normal = Number(target.normal_price || target.price || 0);

                document.querySelectorAll('[data-wedding-addon]').forEach((input) => {
                    if (input.checked) {
                        total += Number(input.value || 0);
                        normal += Number(input.value || 0);
                    }
                });

                document.getElementById('weddingCalcTotal').textContent = formatWeddingIDR(total);
                document.getElementById('weddingCalcNormal').textContent = 'Normal ' + formatWeddingIDR(normal);
            }

            function shareWeddingSimulation() {
                const selectedPackage = document.querySelector('input[name="wedding-calc-package"]:checked');
                const target = weddingPackageData.find(pkg => pkg.slug === selectedPackage.value) || weddingPackageData[0];
                const total = document.getElementById('weddingCalcTotal').textContent;
                const addons = [];
                document.querySelectorAll('[data-wedding-addon]').forEach((input) => {
                    if (input.checked) {
                        const option = weddingAddonOptions.find(item => item.id === input.getAttribute('data-wedding-addon'));
                        if (option) addons.push('- ' + option.label);
                    }
                });
                const addonText = addons.length ? '\n\nTambahan:\n' + addons.join('\n') : '';
                const text = `Halo ${weddingBrandName} Decoration, saya tertarik dengan simulasi wedding berikut:\n\nPaket: ${target.name}\nEstimasi Total: ${total}${addonText}\n\nMohon info ketersediaan jadwal dan konsultasi lebih lanjut.`;
                window.open(`https://wa.me/{{ $waNumber }}?text=${encodeURIComponent(text)}`, '_blank');
            }

            document.addEventListener('DOMContentLoaded', () => {
                if (weddingPackageData.length > 0) {
                    switchWeddingPackage(weddingPackageData[0].slug);
                    updateWeddingCalculator();
                }

                const heroCarousel = document.getElementById('weddingHeroCarousel');
                if (heroCarousel) {
                    const images = JSON.parse(heroCarousel.getAttribute('data-images') || '[]');
                    if (images.length > 1) {
                        let currentIndex = 0;
                        setInterval(() => {
                            currentIndex = (currentIndex + 1) % images.length;
                            heroCarousel.style.opacity = '0.25';
                            setTimeout(() => {
                                heroCarousel.src = images[currentIndex];
                                heroCarousel.style.opacity = '1';
                            }, 250);
                        }, 5000);
                    }
                }
            });
        </script>
    @elseif($servicePage['slug'] === 'cctv')
        <section class="py-2">
            <div class="container py-2">
                <div class="row g-4 mb-4 fade-up">
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-clipboard-check"></i>
                            <div>
                                <div class="fw-bold">Survey Gratis</div>
                                <div class="text-muted small">Cek kebutuhan kabel, DVR, dan titik kamera.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-screwdriver-wrench"></i>
                            <div>
                                <div class="fw-bold">Instalasi Rapi</div>
                                <div class="text-muted small">Cocok untuk rumah, toko, kantor, dan gudang.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-qrcode"></i>
                            <div>
                                <div class="fw-bold">DP via QRIS</div>
                                <div class="text-muted small">Pembayaran awal bisa diproses lebih cepat.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-header text-center mb-4 fade-up">
                    <h2 class="display-6 fw-800">Paket CCTV</h2>
                </div>
                <div class="scroll-container fade-up">
                    @forelse(($cctvPackages ?? collect()) as $pkg)
                        <div class="scroll-item">
                            <div class="card">
                                <div class="pricing-header">
                                    <div class="speed">{{ $pkg->name }}</div>
                                    <div class="text-muted">
                                        {{ $pkg->camera_count ? ((int) $pkg->camera_count.' Kamera') : 'Custom' }}
                                        @if(!empty($pkg->warranty_months))
                                            • Garansi {{ (int) $pkg->warranty_months }} bln
                                        @endif
                                    </div>
                                </div>
                                <div class="pricing-body d-flex flex-column">
                                    <div class="price text-primary">Rp {{ number_format((int) $pkg->price, 0, ',', '.') }} <span class="fs-6 text-muted">/paket</span></div>
                                    <ul class="features">
                                        @if(trim((string) ($pkg->dvr_nvr ?? '')) !== '')
                                            <li><i class="fas fa-check-circle text-primary"></i> {{ $pkg->dvr_nvr }}</li>
                                        @endif
                                        @if(trim((string) ($pkg->hdd ?? '')) !== '')
                                            <li><i class="fas fa-check-circle text-primary"></i> HDD {{ $pkg->hdd }}</li>
                                        @endif
                                        <li><i class="fas fa-check-circle text-primary"></i> Instalasi rapi</li>
                                    </ul>
                                    <a href="#service-lead" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="cctv" data-track-action="package_cta" data-track-label="{{ $pkg->name }}">
                                        Booking Survey
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center w-100 py-2"><p class="text-muted">Paket CCTV belum tersedia.</p></div>
                    @endforelse
                </div>
            </div>
        </section>
    @elseif($servicePage['slug'] === 'gt-wash')
        @php
            $landingHolidayStart = \App\Models\Setting::getValue('wash_holiday_pricing_start_date', '');
            $landingHolidayEnd = \App\Models\Setting::getValue('wash_holiday_pricing_end_date', '');
            $landingHolidayActive = !empty($landingHolidayStart) && !empty($landingHolidayEnd)
                && now()->toDateString() >= $landingHolidayStart
                && now()->toDateString() <= $landingHolidayEnd;
            $landingWashGroups = collect($washServices ?? [])->groupBy(function ($service) {
                $vehicleType = strtolower((string) ($service->vehicle_type ?? ''));
                $category = strtolower((string) ($service->service_category ?? 'main'));
                if (in_array($category, ['addon', 'skincare'], true)) {
                    return 'addon';
                }
                if ($vehicleType === 'car') {
                    return 'mobil';
                }
                if ($vehicleType === 'motor') {
                    return 'motor';
                }
                return 'umum';
            });
            $landingGroupLabels = [
                'mobil' => 'Layanan Mobil',
                'motor' => 'Layanan Motor',
                'addon' => 'Promo / Addon',
                'umum' => 'Kedai Ms GT Wash',
            ];
            $kedaiMenuCatalog = collect([
                [
                    'title' => 'Ice Boba',
                    'items' => [
                        ['name' => 'Brown Sugar Boba Milk', 'price' => 10000],
                        ['name' => 'Taro Boba Latte', 'price' => 10000],
                        ['name' => 'Tiramisu Boba', 'price' => 10000],
                        ['name' => 'Mochacino Boba', 'price' => 10000],
                        ['name' => 'Cappucino Boba', 'price' => 10000],
                        ['name' => 'Avocado Boba', 'price' => 10000],
                        ['name' => 'Red Velvet Boba', 'price' => 10000],
                        ['name' => 'Vanilla Latte Boba', 'price' => 10000],
                    ],
                ],
                [
                    'title' => 'Ice Milkshake',
                    'items' => [
                        ['name' => 'Vanilla Latte Milkshake', 'price' => 8000],
                        ['name' => 'Chocolate Milkshake', 'price' => 8000],
                        ['name' => 'Tiramisu Milkshake', 'price' => 8000],
                        ['name' => 'Mochacino Milkshake', 'price' => 8000],
                        ['name' => 'Taro Milkshake', 'price' => 8000],
                        ['name' => 'Cappucino Milkshake', 'price' => 8000],
                        ['name' => 'Red Velvet Milkshake', 'price' => 8000],
                        ['name' => 'Avocado Milkshake', 'price' => 8000],
                    ],
                ],
                [
                    'title' => 'Hot Coffee',
                    'items' => [
                        ['name' => 'Black Coffee', 'price' => 5000],
                        ['name' => 'Godday', 'price' => 5000],
                        ['name' => 'ABC Coffee', 'price' => 5000],
                        ['name' => 'Indocafe', 'price' => 5000],
                    ],
                ],
                [
                    'title' => 'Ice Coffee',
                    'items' => [
                        ['name' => 'Tiramisu Coffee', 'price' => 7000],
                        ['name' => 'Mochacino Coffee', 'price' => 7000],
                        ['name' => 'Vanilla Latte Coffee', 'price' => 7000],
                        ['name' => 'Cappucino Coffee', 'price' => 7000],
                        ['name' => 'Avocado Coffee', 'price' => 7000],
                    ],
                ],
            ]);
            $kedaiShowcase = [
                'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&q=80&w=900',
                'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=900',
                'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&q=80&w=900',
                'https://images.unsplash.com/photo-1579888944880-d98341245702?auto=format&fit=crop&q=80&w=900',
            ];
        @endphp
        <section class="py-2">
            <div class="container py-2">
                @if($landingHolidayActive)
                    <div class="landing-holiday-banner mt-2 mb-4 fade-up">
                        <i class="fas fa-calendar-check"></i>
                        <span>Harga Hari Raya aktif ({{ \Carbon\Carbon::parse($landingHolidayStart)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($landingHolidayEnd)->translatedFormat('d M Y') }})</span>
                    </div>
                @endif

                <div class="row g-4 mb-4 fade-up">
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-id-card"></i>
                            <div>
                                <div class="fw-bold">Membership Gratis</div>
                                <div class="text-muted small">Nama, WhatsApp, dan plat nomor cukup untuk mulai jadi member.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-gift"></i>
                            <div>
                                <div class="fw-bold">Loyalty 10x Gratis 1x</div>
                                <div class="text-muted small">Reward otomatis untuk transaksi wash berbayar.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-crown"></i>
                            <div>
                                <div class="fw-bold">Priority Booking</div>
                                <div class="text-muted small">Gold dan Platinum diprioritaskan dalam antrean layanan.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-mini-card">
                            <i class="fas fa-mug-hot"></i>
                            <div>
                                <div class="fw-bold">Kedai Ms GT Wash</div>
                                <div class="text-muted small">Sambil menunggu wash selesai, customer bisa pesan boba, milkshake, dan coffee.</div>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach(['mobil', 'motor', 'addon'] as $groupKey)
                    @php $groupItems = $landingWashGroups->get($groupKey, collect()); @endphp
                    @if($groupItems->count() === 0)
                        @continue
                    @endif
                    <div class="section-header text-center mt-4 mb-3 fade-up">
                        <h4 class="fw-bold">{{ $landingGroupLabels[$groupKey] ?? 'Layanan' }}</h4>
                    </div>
                    <div class="scroll-container fade-up">
                        @foreach($groupItems as $serviceIndex => $service)
                            @php
                                $landingAdjustment = is_null($service->holiday_price) ? null : (float) $service->holiday_price;
                                $landingEffectivePrice = $landingHolidayActive && !is_null($landingAdjustment)
                                    ? max(0, ((float) $service->price) + $landingAdjustment)
                                    : (float) $service->price;
                            @endphp
                            <div class="scroll-item">
                                <div class="card">
                                    @if($service->image)
                                        <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="product-img" loading="lazy" decoding="async">
                                    @else
                                        <div class="product-img d-flex align-items-center justify-content-center bg-secondary bg-opacity-25">
                                            <i class="fas {{ $groupKey === 'mobil' ? 'fa-car' : ($groupKey === 'motor' ? 'fa-motorcycle' : 'fa-sparkles') }} fa-3x text-secondary"></i>
                                        </div>
                                    @endif
                                    <div class="product-body d-flex flex-column h-100">
                                        <div class="mb-2">
                                            <span class="chip">
                                                <i class="fas {{ $groupKey === 'mobil' ? 'fa-car' : ($groupKey === 'motor' ? 'fa-motorcycle' : 'fa-sparkles') }} me-1"></i>
                                                {{ ucfirst($groupKey) }}
                                            </span>
                                            @if($serviceIndex < 2)
                                                <span class="chip ms-1 landing-chip-accent">Favorit</span>
                                            @endif
                                        </div>
                                        <h4 class="product-title mb-1">{{ $service->name }}</h4>
                                        @if(($service->priceRules ?? collect())->count() > 0)
                                            <div class="mb-2">
                                                @foreach($service->priceRules->take(4) as $rule)
                                                    @php
                                                        $rulePrice = (float) $rule->price;
                                                        if ($landingHolidayActive && !is_null($landingAdjustment)) {
                                                            $rulePrice = max(0, $rulePrice + (float) $landingAdjustment);
                                                        }
                                                    @endphp
                                                    <div class="d-flex justify-content-between align-items-center small py-1 border-bottom">
                                                        <span>{{ trim((string) $rule->label) }}</span>
                                                        <strong class="text-primary">Rp {{ number_format($rulePrice, 0, ',', '.') }}</strong>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="product-price text-primary fw-bold mb-1">Rp {{ number_format($landingEffectivePrice, 0, ',', '.') }}</div>
                                        @endif
                                        <a href="#service-lead" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="gt-wash" data-track-action="service_cta" data-track-label="{{ $service->name }}">
                                            Booking
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <section class="kedai-section mt-5 fade-up">
                    <div class="kedai-shell">
                        <div class="kedai-hero">
                            <div class="kedai-hero-copy">
                                <span class="kedai-kicker">Kedai Ms GT Wash</span>
                                <h2 class="kedai-title">Menunggu kendaraan selesai sekarang bisa sambil ngopi dan ngemil yang lebih santai</h2>
                                <p class="kedai-desc">Saya ganti blok `Layanan Lainnya` menjadi section kedai yang lebih jelas. Customer bisa lihat menu minuman secara terstruktur dan langsung lanjut order atau tanya stok via WhatsApp.</p>
                                <div class="kedai-hero-actions">
                                    <a href="#service-lead" class="btn btn-primary track-service-action" data-track-service="gt-wash" data-track-action="kedai_form_cta">
                                        Tanya Menu Kedai
                                    </a>
                                    <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin lihat menu Kedai Ms GT Wash dan pesan minuman.') }}" class="btn btn-green track-service-action" data-track-service="gt-wash" data-track-action="kedai_whatsapp_cta">
                                        WhatsApp Kedai
                                    </a>
                                </div>
                            </div>
                            <div class="kedai-showcase">
                                @foreach($kedaiShowcase as $index => $img)
                                    <div class="kedai-showcase-card kedai-showcase-card-{{ $index + 1 }}">
                                        <img src="{{ $img }}" alt="Kedai Ms GT Wash" loading="lazy" decoding="async">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="kedai-board">
                            <div class="kedai-board-header">
                                <div>
                                    <span class="kedai-board-kicker">Menu Minuman</span>
                                    <h3>Kedai Ms GT Wash</h3>
                                </div>
                                <span class="kedai-board-note">Harga ramah sambil menunggu wash</span>
                            </div>

                            <div class="kedai-grid">
                                @foreach($kedaiMenuCatalog as $menuGroup)
                                    <div class="kedai-category">
                                        <h4>{{ $menuGroup['title'] }}</h4>
                                        <ul>
                                            @foreach($menuGroup['items'] as $item)
                                                <li>
                                                    <span>{{ $item['name'] }}</span>
                                                    <strong>Rp {{ number_format($item['price'], 0, ',', '.') }}</strong>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    @elseif($servicePage['slug'] === 'atk-store')
        <section class="py-2">
            <div class="container py-2">
                <div class="section-header text-center mb-4 fade-up">
                    <h2 class="display-6 fw-800">Produk Unggulan & Promo</h2>
                    <p class="text-muted mb-0">Tampilan ATK dibuat lebih katalog-driven dan cepat untuk order.</p>
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
                                    <p class="small text-muted mb-3">{{ \Illuminate\Support\Str::limit($product->description ?? 'Tersedia di toko kami.', 60) }}</p>
                                    <a href="#service-lead" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="atk-store" data-track-action="product_cta" data-track-label="{{ $product->name }}">
                                        Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center w-100 py-2"><p class="text-muted">Belum ada produk unggulan saat ini.</p></div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    <section id="service-lead" class="py-2 {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-lead-section' : 'service-lead-section' }}">
        <div class="container py-2">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5 fade-up">
                    <div class="section-header mb-3 {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-lead-copy' : 'service-lead-copy' }}">
                        <h6 class="{{ $servicePage['slug'] === 'wedding-event' ? 'wedding-section-kicker' : 'service-section-kicker' }}">{{ $servicePage['name'] }}</h6>
                        <h2 class="display-6 fw-800 mb-2 {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-section-title' : 'service-section-title' }}">Lead Form yang Lebih Spesifik</h2>
                        <p class="text-muted mb-0 {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-section-subtitle' : 'service-section-subtitle' }}">Form di halaman ini sudah mengikuti konteks layanan supaya tim lebih cepat membaca kebutuhan Anda.</p>
                    </div>
                    <div class="landing-mini-card {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-side-note' : 'service-side-note' }}">
                        <i class="fas fa-circle-info"></i>
                        <div>
                            <div class="fw-bold">Tracking Per Layanan</div>
                            <div class="text-muted small">Klik CTA dan submit form dari halaman ini dilacak dengan label layanan yang berbeda.</div>
                        </div>
                    </div>
                    @if($servicePage['slug'] === 'wedding-event')
                        <div class="wedding-side-stack">
                            <div class="wedding-side-card">
                                <strong>Respon Lebih Cepat</strong>
                                <span>Field tanggal acara, jumlah tamu, dan lokasi membantu tim langsung memahami konteks wedding Anda.</span>
                            </div>
                            <div class="wedding-side-card">
                                <strong>Flow Lebih Profesional</strong>
                                <span>Section bawah ini saya rapikan agar terasa seperti premium consultation panel, bukan form biasa.</span>
                            </div>
                        </div>
                    @else
                        <div class="service-side-stack">
                            <div class="service-side-card">
                                <strong>Lebih Cepat Dipahami</strong>
                                <span>Field pada form sudah menyesuaikan konteks tiap layanan, jadi tim tidak perlu menebak-nebak kebutuhan utama customer.</span>
                            </div>
                            <div class="service-side-card">
                                <strong>Lebih Mudah Ditindaklanjuti</strong>
                                <span>Setelah submit, customer bisa langsung diteruskan ke WhatsApp untuk mempercepat closing dan konfirmasi detail.</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-lg-7 fade-up">
                    @include('landing.partials.service-lead-form', ['formConfig' => $servicePage['form'], 'servicePage' => $servicePage])
                </div>
            </div>
        </div>
    </section>
@endsection
