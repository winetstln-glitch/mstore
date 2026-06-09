@extends('layouts.landing-public')

@section('content')
    @php
        $siteName = $storeName ?? config('app.name', 'MStore');
        $waUrlBase = 'https://wa.me/'.$waNumber;
        $services = collect($serviceCatalog ?? [])->values();
        $featuredServices = $services->take(3);
        $serviceCount = $services->count();
        $coverageCount = collect($odps ?? [])->count();
    @endphp

    <section id="beranda" class="hero home-hero">
        <div class="container">
            <div class="row align-items-center g-5 home-hero-grid">
                <div class="col-lg-6 fade-up">
                    <span class="home-kicker">MStore Multi Service Ecosystem</span>
                    <h1 class="hero-title home-hero-title">Solusi Multi-Layanan dengan Pengalaman yang Lebih Fokus di Setiap Halaman</h1>
                    <p class="hero-desc home-hero-desc">Homepage ini sekarang menjadi gateway premium untuk semua unit usaha. Pengunjung cukup pilih layanan yang relevan, lalu masuk ke halaman khusus yang sudah disusun sesuai kebutuhan Internet, Wedding & Event, CCTV, GT Wash, atau ATK.</p>
                    <div class="home-cta-row">
                        <a href="#services" class="btn btn-primary track-service-action" data-track-service="umbrella" data-track-action="choose_service">
                            <i class="fas fa-layer-group me-2"></i> Jelajahi Layanan
                        </a>
                        <a href="#quick-consult" class="btn btn-outline-primary track-service-action" data-track-service="umbrella" data-track-action="quick_consult">
                            <i class="fas fa-clipboard-list me-2"></i> Konsultasi Cepat
                        </a>
                        <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan MStore.') }}" class="btn btn-green track-service-action" data-track-service="umbrella" data-track-action="whatsapp">
                            <i class="fab fa-whatsapp me-2"></i> WhatsApp
                        </a>
                    </div>

                    <div class="home-metric-grid">
                        <div class="home-metric-card">
                            <strong>{{ $serviceCount }}</strong>
                            <span>Layanan Utama</span>
                        </div>
                        <div class="home-metric-card">
                            <strong>{{ $coverageCount > 0 ? $coverageCount : 'Live' }}</strong>
                            <span>{{ $coverageCount > 0 ? 'Titik Coverage' : 'Coverage Map' }}</span>
                        </div>
                        <div class="home-metric-card">
                            <strong>Smart</strong>
                            <span>Lead Routing</span>
                        </div>
                    </div>

                    <div class="landing-trust mt-4 home-trust-row">
                        <div class="landing-trust-item">
                            <i class="fas fa-bullseye"></i>
                            <span>CTA Lebih Fokus</span>
                        </div>
                        <div class="landing-trust-item">
                            <i class="fas fa-filter-circle-dollar"></i>
                            <span>Lead Lebih Relevan</span>
                        </div>
                        <div class="landing-trust-item">
                            <i class="fas fa-chart-line"></i>
                            <span>SEO Per Layanan</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 fade-up">
                    <div class="home-showcase-shell">
                        <div class="home-showcase-main">
                            <div class="home-showcase-badge">Ecosystem Overview</div>
                            <h3>Satu umbrella brand, dengan halaman layanan yang lebih siap untuk konversi</h3>
                            <p>Setiap unit usaha kini punya fokus visual, CTA, dan form yang lebih relevan. Visitor tidak lagi dipaksa membaca semua katalog dalam satu halaman panjang.</p>
                            <div class="home-service-stack">
                                @foreach($featuredServices as $service)
                                    <a href="{{ $service['url'] }}" class="home-service-pill track-service-action" data-track-service="{{ $service['slug'] }}" data-track-action="hero_service_pill">
                                        <span class="home-service-pill-icon"><i class="fas {{ $service['icon'] }}"></i></span>
                                        <span>
                                            <strong>{{ $service['name'] }}</strong>
                                            <small>{{ $service['stat'] }}</small>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="home-floating-card home-floating-card-dark">
                            <div class="home-floating-icon"><i class="fas fa-diagram-project"></i></div>
                            <div>
                                <strong>UX Lebih Terarah</strong>
                                <span>Setiap service punya flow sendiri</span>
                            </div>
                        </div>

                        <div class="home-floating-card home-floating-card-green">
                            <div class="home-floating-icon"><i class="fab fa-whatsapp"></i></div>
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

    <section id="services" class="py-2 home-section">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up home-section-header">
                <h6 class="text-primary fw-bold text-uppercase">Pilih Layanan</h6>
                <h2 class="display-6 fw-800">Setiap Layanan Punya Halaman, CTA, dan Form yang Lebih Tepat</h2>
                <p class="text-muted mb-0">Homepage cukup menjadi pintu masuk. Detail penawaran, visual, dan lead form diarahkan ke halaman yang memang dibuat khusus per layanan.</p>
            </div>

            <div class="row g-4">
                @foreach($serviceCatalog as $service)
                    <div class="col-lg-4 col-md-6 fade-up">
                        <div class="landing-service-card home-service-card h-100">
                            <div class="home-service-card-top">
                                <div class="landing-service-icon home-service-card-icon">
                                    <i class="fas {{ $service['icon'] }}"></i>
                                </div>
                                <div class="landing-service-stat home-service-stat">{{ $service['stat'] }}</div>
                            </div>
                            <h3 class="landing-service-title">{{ $service['name'] }}</h3>
                            <p class="landing-service-desc">{{ $service['summary'] }}</p>
                            <ul class="landing-service-points">
                                @foreach($service['highlights'] as $highlight)
                                    <li><i class="fas fa-check-circle text-primary"></i> {{ $highlight }}</li>
                                @endforeach
                            </ul>
                            <div class="home-service-card-actions mt-auto">
                                <a href="{{ $service['url'] }}" class="btn btn-primary w-100 track-service-action" data-track-service="{{ $service['slug'] }}" data-track-action="open_service_page" data-track-label="{{ $service['name'] }}">
                                    Buka Halaman
                                </a>
                                <div class="home-service-note">{{ $service['secondary_note'] ?? 'Halaman khusus siap dipakai' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-2 home-benefit-band">
        <div class="container py-2">
            <div class="row g-4">
                <div class="col-lg-4 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <div class="fw-bold">Homepage Lebih Ringkas</div>
                            <div class="text-muted small">Struktur umbrella membuat visitor tidak harus membaca semua detail layanan di satu halaman panjang.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card">
                        <i class="fas fa-clipboard-check"></i>
                        <div>
                            <div class="fw-bold">Form Lebih Spesifik</div>
                            <div class="text-muted small">Setiap halaman layanan punya field yang lebih relevan, sehingga tim lebih cepat follow up.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card">
                        <i class="fas fa-magnifying-glass-chart"></i>
                        <div>
                            <div class="fw-bold">Tracking Lebih Tajam</div>
                            <div class="text-muted small">CTA, klik halaman, dan submit lead dapat dibedakan per layanan untuk analisa konversi yang lebih jelas.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="coverage-area" class="py-2 home-section">
        <div class="container py-2">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 fade-up">
                    <div class="home-map-shell">
                        <div id="coverageMap"></div>
                    </div>
                </div>
                <div class="col-lg-6 fade-up">
                    <div class="home-coverage-copy">
                        <span class="home-kicker">Coverage Internet</span>
                        <h2 class="display-6 fw-800 mb-4">Peta Coverage Tetap Ditampilkan Karena Ini Intent Terkuat untuk Layanan Internet</h2>
                        <p class="text-secondary mb-4">Homepage menyimpan peta coverage sebagai entry point paling penting untuk layanan internet. Setelah user merasa area memungkinkan, mereka diarahkan ke halaman Internet Fiber yang berisi paket, voucher, dan registrasi lebih spesifik.</p>

                        <div class="home-coverage-list mb-4">
                            <div class="home-coverage-item">
                                <div class="home-coverage-icon">
                                    <i class="fas fa-satellite-dish fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Peta ODP</h6>
                                    <p class="small text-muted mb-0">Lihat titik ODP, status port, dan gambaran awal coverage di area Anda.</p>
                                </div>
                            </div>
                            <div class="home-coverage-item">
                                <div class="home-coverage-icon">
                                    <i class="fas fa-location-dot fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Lanjut ke Halaman Internet</h6>
                                    <p class="small text-muted mb-0">Landing internet memuat paket, voucher, dan form registrasi yang lebih spesifik.</p>
                                </div>
                            </div>
                        </div>

                        <div class="home-coverage-actions">
                            <a href="{{ route('landing.services.internet') }}" class="btn btn-primary track-service-action" data-track-service="internet" data-track-action="from_home_coverage">
                                <i class="fas fa-wifi me-2"></i> Buka Halaman Internet
                            </a>
                            <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin cek coverage area internet.') }}" class="btn btn-green track-service-action" data-track-service="internet" data-track-action="coverage_whatsapp">
                                <i class="fab fa-whatsapp me-2"></i> Kirim Alamat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="quick-consult" class="py-2 home-consult-section">
        <div class="container py-2">
            <div class="row g-4 align-items-start home-consult-shell">
                <div class="col-lg-5 fade-up">
                    <div class="section-header mb-3 home-consult-copy">
                        <h6 class="text-primary fw-bold text-uppercase">Quick Consult</h6>
                        <h2 class="display-6 fw-800 mb-2">Belum Tahu Mau Pilih Layanan Yang Mana?</h2>
                        <p class="text-muted mb-0">Kalau visitor masih bingung, form cepat ini tetap tersedia dari homepage untuk memudahkan follow up awal sebelum diarahkan ke halaman layanan yang paling sesuai.</p>
                    </div>

                    <div class="home-consult-points">
                        <div class="home-consult-point">
                            <i class="fas fa-route"></i>
                            <span>Tim akan bantu arahkan ke layanan yang paling cocok</span>
                        </div>
                        <div class="home-consult-point">
                            <i class="fas fa-comments"></i>
                            <span>Bisa lanjut via WhatsApp setelah submit</span>
                        </div>
                        <div class="home-consult-point">
                            <i class="fas fa-layer-group"></i>
                            <span>Context lead tetap tersimpan per sumber halaman</span>
                        </div>
                    </div>

                    <div class="home-consult-service-tags">
                        @foreach($services as $service)
                            <span>{{ $service['name'] }}</span>
                        @endforeach
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">
                            <div class="fw-bold">{{ session('success') }}</div>
                            @if(session('lead_whatsapp_url'))
                                <div class="mt-2">
                                    <a class="btn btn-primary" href="{{ session('lead_whatsapp_url') }}">
                                        <i class="fab fa-whatsapp me-2"></i> Lanjutkan via WhatsApp
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="col-lg-7 fade-up">
                    <div class="lead-card home-lead-card">
                        <form method="POST" action="{{ route('landing.leads.store') }}" class="lead-form" data-track-service-form="umbrella" data-track-label="home quick consult">
                            @csrf
                            <input type="hidden" name="landing_page" value="home">
                            <input type="hidden" name="utm_source" value="{{ request('utm_source') }}">
                            <input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">
                            <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">
                            <input type="hidden" name="utm_term" value="{{ request('utm_term') }}">
                            <input type="hidden" name="utm_content" value="{{ request('utm_content', 'home') }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama</label>
                                    <input name="name" value="{{ old('name') }}" class="form-control home-form-control @error('name') is-invalid @enderror" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input name="phone" value="{{ old('phone') }}" class="form-control home-form-control @error('phone') is-invalid @enderror" required>
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Minat Layanan</label>
                                    <select name="service_interest" class="form-select home-form-control @error('service_interest') is-invalid @enderror">
                                        <option value="">Pilih</option>
                                        @foreach($serviceCatalog as $service)
                                            <option value="{{ $service['form']['interest'] }}" @selected(old('service_interest') === $service['form']['interest'])>{{ $service['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('service_interest')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Area / Lokasi</label>
                                    <input name="coverage_area" value="{{ old('coverage_area') }}" class="form-control home-form-control @error('coverage_area') is-invalid @enderror" placeholder="Contoh: rumah, lokasi acara, cabang wash">
                                    @error('coverage_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Kebutuhan Singkat</label>
                                    <textarea name="message" rows="4" class="form-control home-form-control home-form-textarea @error('message') is-invalid @enderror" placeholder="Contoh: butuh internet rumah, wedding package, survey CCTV, atau booking GT Wash">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <button class="btn btn-primary track-service-action" type="submit" data-track-service="umbrella" data-track-action="quick_consult_submit">
                                        Kirim
                                    </button>
                                    <a class="btn btn-green track-service-action" data-track-service="umbrella" data-track-action="quick_consult_whatsapp" href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi memilih layanan MStore.') }}">
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-2 home-section">
        <div class="container py-2">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6 fade-up">
                    <span class="home-kicker">Tentang {{ $siteName }}</span>
                    <h2 class="display-6 fw-800 mb-3">Ekosistem Multi-Bisnis yang Disusun Lebih Rapi untuk Visitor dan Tim Sales</h2>
                    <p class="text-muted mb-3">Kami mengelola beberapa lini layanan dalam satu ekosistem, tetapi sekarang setiap lini punya halaman yang lebih fokus agar penawaran, katalog, CTA, dan capture lead tidak saling bercampur.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="#services">Lihat Semua Layanan</a>
                        <a class="btn btn-outline-primary" href="#kontak">Lihat Kontak</a>
                    </div>
                </div>
                <div class="col-lg-6 fade-up">
                    <div class="about-card home-about-card">
                        @foreach($serviceCatalog as $service)
                            <div class="about-item home-about-item">
                                <i class="fas {{ $service['icon'] }}"></i>
                                <div>
                                    <div class="fw-bold">{{ $service['name'] }}</div>
                                    <div class="text-muted small">{{ $service['summary'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
