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
                    <span class="home-kicker">{{ $siteName }} • Multi Layanan Terintegrasi</span>
                    <h1 class="hero-title home-hero-title">Solusi Internet, CCTV, Event, Car Wash, dan Retail dalam Satu Layanan Terpercaya</h1>
                    <p class="hero-desc home-hero-desc">Kami menyediakan berbagai solusi untuk kebutuhan rumah, bisnis, dan event. Dapatkan informasi lengkap, konsultasi gratis, dan pelayanan profesional dalam satu platform terintegrasi.</p>
                    <div class="home-cta-row">
                        <a href="#services" class="btn btn-primary track-service-action" data-track-service="umbrella" data-track-action="choose_service">
                            <i class="fas fa-layer-group me-2"></i> Lihat Semua Layanan
                        </a>
                        <a href="#quick-consult" class="btn btn-outline-primary track-service-action" data-track-service="umbrella" data-track-action="quick_consult">
                            <i class="fas fa-clipboard-list me-2"></i> Konsultasi Gratis
                        </a>
                        <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan MStore.') }}" class="btn btn-green track-service-action" data-track-service="umbrella" data-track-action="whatsapp">
                            <i class="fab fa-whatsapp me-2"></i> Chat WhatsApp Sekarang
                        </a>
                    </div>

                    <div class="home-metric-grid">
                        <div class="home-metric-card">
                            <strong>{{ $serviceCount }}</strong>
                            <span>Unit Layanan</span>
                        </div>
                        <div class="home-metric-card">
                            <strong>{{ $coverageCount > 0 ? $coverageCount : 'Live' }}</strong>
                            <span>{{ $coverageCount > 0 ? 'Titik Coverage' : 'Peta Coverage' }}</span>
                        </div>
                        <div class="home-metric-card">
                            <strong>Cepat</strong>
                            <span>Respon Cepat</span>
                        </div>
                    </div>

                    <div class="landing-trust mt-4 home-trust-row">
                        <div class="landing-trust-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Pelayanan Profesional</span>
                        </div>
                        <div class="landing-trust-item">
                            <i class="fas fa-clock"></i>
                            <span>Respon Cepat WhatsApp</span>
                        </div>
                        <div class="landing-trust-item">
                            <i class="fas fa-target"></i>
                            <span>Solusi Sesuai Kebutuhan</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 fade-up">
                    <div class="home-showcase-shell">
                        <div class="home-showcase-main">
                            <div class="home-showcase-badge">Ringkasan Ekosistem</div>
                            <h3>Lebih mudah memilih, lebih cepat diproses</h3>
                            <p>Setiap unit layanan kami susun agar mudah dipahami: informasi ringkas, highlight benefit, dan jalur konsultasi yang jelas.</p>
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
                                <strong>Alur Lebih Rapi</strong>
                                <span>Setiap layanan punya flow yang jelas</span>
                            </div>
                        </div>

                        <div class="home-floating-card home-floating-card-green">
                            <div class="home-floating-icon"><i class="fab fa-whatsapp"></i></div>
                            <div>
                                <strong>Respon Cepat</strong>
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
                <h2 class="display-6 fw-800">Pilih Solusi yang Sesuai dengan Kebutuhan Anda</h2>
                <p class="text-muted mb-0">Temukan layanan terbaik untuk rumah, bisnis, dan event Anda. Setiap layanan dilengkapi informasi lengkap dan konsultasi langsung dengan tim kami.</p>
            </div>

            <div class="row g-4">
                @foreach($serviceCatalog as $index => $service)
                    <div class="col-lg-4 col-md-6 fade-up">
                        <div class="landing-service-card home-service-card h-100">
                            <div class="home-service-card-top">
                                @if($index === 0)
                                    <span class="badge bg-success text-white position-absolute top-0 start-0 m-2">Best Seller</span>
                                @elseif($index === 1)
                                    <span class="badge bg-primary text-white position-absolute top-0 start-0 m-2">Recommended</span>
                                @elseif($index === 2)
                                    <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Popular</span>
                                @endif
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
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <div class="fw-bold">Informasi Lengkap</div>
                            <div class="text-muted small">Semua informasi layanan disajikan secara jelas dan mudah dipahami.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card">
                        <i class="fas fa-comments"></i>
                        <div>
                            <div class="fw-bold">Konsultasi Gratis</div>
                            <div class="text-muted small">Tim kami siap membantu memberikan rekomendasi terbaik sesuai kebutuhan Anda.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card">
                        <i class="fas fa-briefcase"></i>
                        <div>
                            <div class="fw-bold">Layanan Profesional</div>
                            <div class="text-muted small">Didukung proses kerja yang terstruktur untuk memastikan pelayanan yang cepat dan tepat.</div>
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
                        <h2 class="display-6 fw-800 mb-4">Cek Ketersediaan Jaringan di Lokasi Anda</h2>
                        <p class="text-secondary mb-4">Periksa ketersediaan jaringan fiber optik di area Anda sebelum memilih paket internet yang sesuai.</p>

                        <div class="home-coverage-list mb-4">
                            <div class="home-coverage-item">
                                <div class="home-coverage-icon">
                                    <i class="fas fa-check-circle fa-lg text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Coverage Aktif</h6>
                                </div>
                            </div>
                            <div class="home-coverage-item">
                                <div class="home-coverage-icon">
                                    <i class="fas fa-check-circle fa-lg text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Survey Lokasi Gratis</h6>
                                </div>
                            </div>
                            <div class="home-coverage-item">
                                <div class="home-coverage-icon">
                                    <i class="fas fa-check-circle fa-lg text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Dukungan Teknisi</h6>
                                </div>
                            </div>
                        </div>

                        <div class="home-coverage-actions">
                            <a href="{{ route('landing.services.internet') }}" class="btn btn-primary track-service-action" data-track-service="internet" data-track-action="from_home_coverage">
                                <i class="fas fa-wifi me-2"></i> Lihat Paket Internet
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
                        <h2 class="display-6 fw-800 mb-2">Belum Yakin Layanan Mana yang Cocok?</h2>
                        <p class="text-muted mb-0">Sampaikan kebutuhan Anda melalui form berikut. Tim kami akan menghubungi Anda dan memberikan solusi yang paling sesuai.</p>
                    </div>

                    <div class="home-consult-points">
                        <div class="home-consult-point">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Respon Cepat via WhatsApp</span>
                        </div>
                        <div class="home-consult-point">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Tim Profesional dan Berpengalaman</span>
                        </div>
                        <div class="home-consult-point">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Dukungan Pelanggan Responsif</span>
                        </div>
                        <div class="home-consult-point">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Solusi untuk Rumah, Bisnis, dan Event</span>
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
                                    <input name="name" value="{{ old('name') }}" class="form-control home-form-control @error('name') is-invalid @enderror" required placeholder="Masukkan nama lengkap">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp</label>
                                    <input name="phone" value="{{ old('phone') }}" class="form-control home-form-control @error('phone') is-invalid @enderror" required placeholder="08xxxxxxxxxx">
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
                                    <input name="coverage_area" value="{{ old('coverage_area') }}" class="form-control home-form-control @error('coverage_area') is-invalid @enderror" placeholder="Contoh: Perumahan Griya Asri">
                                    @error('coverage_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Kebutuhan Singkat</label>
                                    <textarea name="message" rows="4" class="form-control home-form-control home-form-textarea @error('message') is-invalid @enderror" placeholder="Jelaskan kebutuhan Anda secara singkat">{{ old('message') }}</textarea>
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
                    <h2 class="display-6 fw-800 mb-3">Partner Terpercaya untuk Kebutuhan Internet dan Teknologi Anda</h2>
                    <p class="text-muted mb-3">Kami menghadirkan berbagai layanan terintegrasi mulai dari Internet Fiber Optik, CCTV & Security System, Event WiFi, Car Wash, hingga solusi Retail Digital untuk membantu kebutuhan rumah maupun bisnis Anda.</p>
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

    <!-- Social Proof Section -->
    <section class="py-2 home-section">
        <div class="container py-2">
            <div class="section-header text-center mb-5 fade-up">
                <h6 class="text-primary fw-bold text-uppercase">Mengapa Memilih Kami</h6>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card text-center">
                        <i class="fas fa-users fa-2x mb-3 text-primary"></i>
                        <div class="fw-bold">Tim Berpengalaman</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card text-center">
                        <i class="fas fa-headset fa-2x mb-3 text-primary"></i>
                        <div class="fw-bold">Dukungan Cepat</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card text-center">
                        <i class="fas fa-puzzle-piece fa-2x mb-3 text-primary"></i>
                        <div class="fw-bold">Solusi Terintegrasi</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="landing-mini-card h-100 home-mini-card text-center">
                        <i class="fas fa-briefcase fa-2x mb-3 text-primary"></i>
                        <div class="fw-bold">Layanan Profesional</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-5 home-section bg-primary bg-opacity-10">
        <div class="container py-5">
            <div class="text-center fade-up">
                <h2 class="display-5 fw-800 mb-4">Siap Mendapatkan Solusi Terbaik untuk Kebutuhan Anda?</h2>
                <p class="text-muted mb-6">Hubungi tim kami sekarang untuk konsultasi gratis dan dapatkan penawaran terbaik.</p>
                <a href="{{ $waUrlBase }}?text={{ urlencode('Halo, saya ingin konsultasi layanan MStore.') }}" class="btn btn-primary btn-lg track-service-action" data-track-service="umbrella" data-track-action="final_cta_whatsapp">
                    <i class="fab fa-whatsapp me-2"></i> Hubungi Kami Sekarang
                </a>
            </div>
        </div>
    </section>
@endsection
