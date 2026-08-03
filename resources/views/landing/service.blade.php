@extends('layouts.landing-public')

@section('content')
    @php
        $siteName = $storeName ?? config('app.name', 'MStore');
        $assetUrl = static fn (string $path): string => app()->environment('production') ? secure_asset($path) : asset($path);
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
        $weddingHeroGallery = collect($weddingGallery ?? [])->map(function ($img) use ($assetUrl) {
            return str_starts_with((string) $img, 'http') ? $img : $assetUrl($img);
        })->filter()->values();
        if ($weddingHeroGallery->isEmpty()) {
            $weddingHeroGallery = $weddingFallbackGallery;
        }
    @endphp

    @if($servicePage['slug'] === 'wedding-event')
        <div class="wedding-topbar">
            <div class="container text-center">
                Promo Spesial: Bonus handbouquet & undangan digital untuk paket wedding tertentu.
            </div>
        </div>

        <section class="wedding-hero">
            <div class="wedding-hero-glow wedding-hero-glow-left"></div>
            <div class="wedding-hero-glow wedding-hero-glow-right"></div>
            <div class="container position-relative">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7 fade-up text-center text-lg-start">
                        <span class="wedding-pill">Wedding Organizer Premium & Elegan</span>
                        <h1 class="wedding-hero-title">Wujudkan Pernikahan Impian dengan Dekor Elegan dan Alur Booking yang Jelas</h1>
                        <p class="wedding-hero-desc">{{ $servicePage['hero_desc'] }}</p>
                        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-center justify-content-lg-start gap-3">
                            <a href="#wedding-packages" class="btn wedding-btn-dark track-service-action" data-track-service="wedding-event" data-track-action="hero_packages">
                                Lihat Pilihan Paket
                            </a>
                            <a href="#wedding-calculator" class="btn wedding-btn-light track-service-action" data-track-service="wedding-event" data-track-action="hero_calculator">
                                Simulasi Budget
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
                                    <strong>Respon Cepat</strong>
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
            $isMemberType = fn($pt) => in_array(strtolower((string)$pt), ['member', 'membership', 'hotspot'], true);
            $isResidentialType = fn($pt) => in_array(strtolower((string)$pt), ['pppoe', 'home', 'residential', 'rumahan'], true);
            $isVoucherType = fn($pt) => strtolower((string)$pt) === 'voucher';

            $pppoeInternetPackages = $packages->filter(fn($p) => $isResidentialType($p->package_type ?? ''))->values();

            $voucherFromPackages = $packages->filter(fn($p) => $isVoucherType($p->package_type ?? ''))->values();
            $voucherTemplatesBase = isset($voucherTemplates) ? collect($voucherTemplates) : collect([]);
            $voucherProfiles = $voucherTemplatesBase->merge($voucherFromPackages)->unique(fn($p) => is_object($p) ? ($p->id ?? spl_object_id($p)) : $p)->values();

            $hotspotMemberPackages = $packages->filter(fn($p) => $isMemberType($p->package_type ?? ''))->values();
            $washMemberPkgList = collect([]);
            if (isset($washMemberPackages)) {
                if ($washMemberPackages instanceof \Illuminate\Database\Eloquent\Collection) {
                    $washMemberPkgList = $washMemberPackages->toBase();
                } else {
                    $washMemberPkgList = collect($washMemberPackages);
                }
            }

            $convertedFromHotspotProfile = ($hotspotMemberPackages instanceof \Illuminate\Database\Eloquent\Collection
                ? $hotspotMemberPackages->toBase()
                : collect($hotspotMemberPackages)
            )->map(function ($hp) {
                $seconds = (int)($hp->duration_seconds ?? 0);
                $durHari = 0;
                if ($seconds > 0) {
                    $durHari = (int)round($seconds / 86400);
                    if ($durHari < 1) $durHari = 1;
                }
                $benefitList = [];
                if (!empty($hp->description)) {
                    $benefitList = preg_split('/\r\n|\r|\n/', (string)$hp->description);
                }
                return (object)[
                    'id' => $hp->id,
                    'name' => $hp->name,
                    'code' => 'MBR-' . $hp->id,
                    'type' => 'wifi',
                    'duration_days' => $durHari > 0 ? $durHari : 30,
                    'rate_limit_mbps' => $hp->rate_limit_mbps ?? null,
                    'daily_wifi_minutes' => null,
                    'price' => (float)($hp->price ?? 0),
                    'benefits' => $benefitList,
                    'from_hotspot_profile' => true,
                ];
            });

            $wifiMemberPackages = $washMemberPkgList->merge($convertedFromHotspotProfile)
                ->sortBy(fn($item) => [ (float)(is_array($item) ? ($item['price'] ?? 0) : ($item->price ?? 0)), (string)(is_array($item) ? ($item['name'] ?? '') : ($item->name ?? '')) ])
                ->values();

            $formatInternetSpeedFromRateLimit = function ($rateLimitValue) {
                $speedText = trim((string) $rateLimitValue);
                if ($speedText === '' || $speedText === '0') {
                    return '-';
                }
                $num = (float) $speedText;
                if ($num <= 0) {
                    return '-';
                }
                if (fmod($num, 1) === 0.0) {
                    return ((int) $num).' Mbps';
                }
                return number_format($num, 2).' Mbps';
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

                                $pkgTypeLower = strtolower(trim((string) ($package->package_type ?? 'home')));
                                if (in_array($pkgTypeLower, ['home', 'residential', 'rumahan'], true)) {
                                    $packageCode = 'HOME-' . $package->id;
                                } elseif ($pkgTypeLower === 'pppoe') {
                                    $packageCode = 'PPPOE-' . $package->id;
                                } elseif ($pkgTypeLower === 'voucher') {
                                    $packageCode = 'VOUCHER-' . $package->id;
                                } else {
                                    $packageCode = 'PKT-' . $package->id;
                                }

                                $sharedUsers = (int) ($package->shared_users ?? 0);
                                if ($sharedUsers > 1) {
                                    $devicesLabel = 'Hingga ' . $sharedUsers . ' perangkat bersamaan';
                                    $devicesIcon = 'fa-users';
                                } else {
                                    $devicesLabel = 'Unlimited perangkat';
                                    $devicesIcon = 'fa-wifi';
                                }

                                $quotaMb = (int) ($package->quota_mb ?? 0);
                                if ($quotaMb > 0) {
                                    if ($quotaMb >= 1024) {
                                        $quotaGb = $quotaMb / 1024;
                                        $quotaLabel = 'Kuota ' . (fmod($quotaGb, 1) === 0.0 ? (int) $quotaGb : number_format($quotaGb, 1)) . ' GB';
                                    } else {
                                        $quotaLabel = 'Kuota ' . $quotaMb . ' MB';
                                    }
                                    $quotaIcon = 'fa-database';
                                } else {
                                    $quotaLabel = 'Kuota Unlimited';
                                    $quotaIcon = 'fa-database';
                                }

                                $speedLabel = $formatInternetSpeedFromRateLimit($package->rate_limit_mbps);
                            @endphp
                            <div class="scroll-item">
                                <div class="card">
                                    <div class="pricing-header">
                                        <div class="speed">{{ $package->name }}</div>
                                        <div class="fw-bold small text-muted" style="letter-spacing:.5px;">Kode paket: <span class="fw-semibold">{{ $packageCode }}</span></div>
                                    </div>
                                    <div class="pricing-body d-flex flex-column">
                                        <div class="price text-primary">Rp {{ number_format((int) $package->price, 0, ',', '.') }} <span class="fs-6 text-muted">/ Bulan</span></div>
                                        <h5 class="mb-3">{{ $speedLabel }}</h5>
                                        <ul class="features">
                                            <li><i class="fas {{ $devicesIcon }} text-primary"></i> {{ $devicesLabel }}</li>
                                            <li><i class="fas {{ $quotaIcon }} text-primary"></i> {{ $quotaLabel }}</li>
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
                            @php
                                $voucherSpeed = $formatInternetSpeedFromRateLimit($profile->rate_limit_mbps);
                                $voucherQuotaMb = (int) ($profile->quota_mb ?? 0);
                                if ($voucherQuotaMb > 0) {
                                    if ($voucherQuotaMb >= 1024) {
                                        $voucherQuotaGb = $voucherQuotaMb / 1024;
                                        $voucherQuotaLabel = 'Kuota ' . (fmod($voucherQuotaGb, 1) === 0.0 ? (int) $voucherQuotaGb : number_format($voucherQuotaGb, 1)) . ' GB';
                                    } else {
                                        $voucherQuotaLabel = 'Kuota ' . $voucherQuotaMb . ' MB';
                                    }
                                } else {
                                    $voucherQuotaLabel = 'Kuota Unlimited';
                                }
                                $voucherDuration = $profile->formatted_uptime ?: ($profile->duration_seconds ? format_duration((int) $profile->duration_seconds) : 'Unlimited');
                                $voucherCode = 'VOUCHER-' . $profile->id;
                            @endphp
                            <div class="scroll-item">
                                <div class="card">
                                    <div class="pricing-header">
                                        <div class="speed">{{ $profile->name }}</div>
                                        <div class="small text-muted" style="letter-spacing:.5px;">Kode paket: <span class="fw-semibold">{{ $voucherCode }}</span></div>
                                    </div>
                                    <div class="pricing-body d-flex flex-column">
                                        <div class="price text-primary">Rp {{ number_format((float) $profile->price, 0, ',', '.') }}</div>
                                        @if($voucherSpeed !== '-')
                                            <h5 class="mb-2">{{ $voucherSpeed }}</h5>
                                        @else
                                            <div class="mb-2"></div>
                                        @endif
                                        <div class="small text-muted mb-2">Durasi: {{ $voucherDuration }}</div>
                                        <div class="small text-muted mb-3">{{ $voucherQuotaLabel }}</div>
                                        <a href="https://buy.mstore.id/e-voucher" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="internet" data-track-action="voucher_qris">
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

                @if($wifiMemberPackages->count() > 0)
                <div class="fade-up mt-5">
                    <h5 class="fw-bold mb-2">Paket Member Hotspot</h5>
                    <div class="scroll-container">
                        @foreach($wifiMemberPackages as $memberPkg)
                            @php
                                $memberTypeLower = strtolower(trim((string) ($memberPkg->type ?? 'both')));
                                if ($memberTypeLower === 'wifi') {
                                    $memberBadge = 'Member WiFi';
                                    $memberIcon = 'fa-wifi';
                                } elseif ($memberTypeLower === 'wash') {
                                    $memberBadge = 'Member Wash';
                                    $memberIcon = 'fa-car';
                                } else {
                                    $memberBadge = 'Member Wash+WiFi';
                                    $memberIcon = 'fa-layer-group';
                                }

                                $memberDurationDays = (int) ($memberPkg->duration_days ?? 0);
                                if ($memberDurationDays >= 330) {
                                    $memberDurationLabel = number_format($memberDurationDays / 365, 1) . ' Tahun';
                                } elseif ($memberDurationDays >= 28) {
                                    $months = (int) round($memberDurationDays / 30);
                                    $memberDurationLabel = $months . ' Bulan';
                                } else {
                                    $memberDurationLabel = $memberDurationDays . ' Hari';
                                }
                                $memberDurationHint = $memberDurationDays > 0 ? ('Berlaku ' . $memberDurationDays . ' hari') : 'Durasi menyesuaikan';

                                $memberSpeed = $formatInternetSpeedFromRateLimit($memberPkg->rate_limit_mbps);

                                $memberWifiMinutes = (int) ($memberPkg->daily_wifi_minutes ?? 0);
                                if ($memberWifiMinutes <= 0) {
                                    $memberWifiLabel = 'WiFi Unlimited';
                                } elseif ($memberWifiMinutes >= 1440) {
                                    $memberWifiLabel = 'WiFi Unlimited / hari';
                                } else {
                                    if ($memberWifiMinutes >= 60) {
                                        $h = (int) floor($memberWifiMinutes / 60);
                                        $m = $memberWifiMinutes % 60;
                                        $memberWifiLabel = 'WiFi ' . $h . ' jam' . ($m > 0 ? (' ' . $m . ' menit') : '') . ' / hari';
                                    } else {
                                        $memberWifiLabel = 'WiFi ' . $memberWifiMinutes . ' menit / hari';
                                    }
                                }

                                $memberBenefits = is_array($memberPkg->benefits) ? $memberPkg->benefits : [];
                                if (is_string($memberPkg->benefits) && trim($memberPkg->benefits) !== '') {
                                    $maybe = json_decode($memberPkg->benefits, true);
                                    if (is_array($maybe)) {
                                        $memberBenefits = $maybe;
                                    } else {
                                        $memberBenefits = preg_split('/\r\n|\r|\n/', (string) $memberPkg->benefits);
                                    }
                                }
                                $memberBenefits = collect($memberBenefits)->map(fn ($b) => trim((string) $b))->filter()->values();
                            @endphp
                            <div class="scroll-item">
                                <div class="card">
                                    <div class="pricing-header">
                                        <div class="speed">{{ $memberPkg->name }}</div>
                                        <div class="small text-muted" style="letter-spacing:.5px;">
                                            {{ $memberBadge }}
                                            @if(!empty($memberPkg->code))
                                                · <span class="fw-semibold">Kode: {{ $memberPkg->code }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="pricing-body d-flex flex-column">
                                        <div class="price text-primary">Rp {{ number_format((float) $memberPkg->price, 0, ',', '.') }} <span class="fs-6 text-muted">/ {{ $memberDurationLabel }}</span></div>
                                        @if($memberSpeed !== '-')
                                            <h5 class="mb-2">{{ $memberSpeed }}</h5>
                                        @else
                                            <div class="mb-2"></div>
                                        @endif
                                        <div class="small text-muted mb-2">{{ $memberDurationHint }}</div>
                                        <div class="small text-muted mb-3"><i class="fas {{ $memberIcon }} text-primary me-1"></i> {{ $memberWifiLabel }}</div>
                                        @if($memberBenefits->count() > 0)
                                            <ul class="features mb-3">
                                                @foreach($memberBenefits->take(5) as $benefit)
                                                    <li><i class="fas fa-check-circle text-primary"></i> {{ $benefit }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        <a href="#service-lead" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="internet" data-track-action="member_cta" data-track-label="{{ $memberPkg->name }}">
                                            Pilih Member
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
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
            $weddingPackageCards = ($weddingPackageSource->count() > 0 ? $weddingPackageSource : $defaultWeddingPackages)->values()->take(4)->map(function ($pkg, $index) use ($weddingHeroGallery, $assetUrl) {
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
                $packageImagePath = $pkg['image_path'] ?? $pkg->image_path ?? null;
                $image = null;
                if (is_string($packageImagePath) && trim($packageImagePath) !== '') {
                    $image = $assetUrl('storage/'.ltrim($packageImagePath, '/'));
                }
                if (! $image) {
                    $image = $weddingHeroGallery->get($index) ?? $weddingHeroGallery->first();
                }

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
                    <p class="wedding-section-subtitle">Konsultasi lebih rapi, detail kebutuhan tercatat, dan proses booking lebih cepat.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-4 fade-up">
                        <div class="wedding-feature-card">
                            <div class="wedding-feature-icon"><i class="fas fa-award"></i></div>
                            <h3>Kualitas Terjaga</h3>
                            <p>Standar dekor, rias, dan detail acara disusun rapi agar hasilnya terlihat premium di lokasi maupun di foto.</p>
                        </div>
                    </div>
                    <div class="col-md-4 fade-up">
                        <div class="wedding-feature-card">
                            <div class="wedding-feature-icon"><i class="fas fa-spa"></i></div>
                            <h3>Dekor Elegan</h3>
                            <p>Pilihan konsep bisa disesuaikan: modern, minimalis, atau glam. Fokus pada detail yang membuat acara terasa berkelas.</p>
                        </div>
                    </div>
                    <div class="col-md-4 fade-up">
                        <div class="wedding-feature-card">
                            <div class="wedding-feature-icon"><i class="fas fa-users"></i></div>
                            <h3>Pelayanan Profesional</h3>
                            <p>Kami bantu dari konsultasi hingga hari H. Detail tanggal, lokasi, dan jumlah tamu mempermudah penawaran yang tepat.</p>
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
                    <p class="wedding-section-subtitle">Pilih paket lewat tab untuk melihat fasilitas dan harga lebih cepat, lalu lanjut booking.</p>
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
                <div class="wedding-gallery-carousel fade-up" id="weddingGallery">
                    <div class="wedding-gallery-track" id="galleryTrack">
                        @foreach($weddingHeroGallery as $index => $img)
                            <div class="wedding-gallery-slide">
                                <img src="{{ $img }}" alt="Gallery Wedding {{ $index + 1 }}" loading="lazy" decoding="async">
                            </div>
                        @endforeach
                    </div>
                    <div class="wedding-gallery-nav">
                        <button class="wedding-gallery-btn" id="prevBtn">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <button class="wedding-gallery-btn" id="nextBtn">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                    <div class="wedding-gallery-dots" id="galleryDots">
                        @foreach($weddingHeroGallery as $index => $img)
                            <button class="wedding-gallery-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="wedding-calculator" class="wedding-section wedding-section-dark">
            <div class="container">
                <div class="row g-5 align-items-start">
                    <div class="col-lg-5 fade-up">
                        <span class="wedding-section-kicker wedding-section-kicker-light">Kalkulator Budget</span>
                        <h2 class="wedding-dark-title">Simulasikan Anggaran dan Dapatkan Rekomendasi Paket</h2>
                        <p class="wedding-dark-desc">Pilih paket dasar, tambahkan kebutuhan, lalu kirim ringkasan simulasi untuk konsultasi dan cek ketersediaan jadwal.</p>
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

            function escapeWeddingHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function safeWeddingUrl(value) {
                const url = String(value ?? '').trim();
                if (!url) return '';
                if (/^javascript:/i.test(url)) return '';
                if (/^data:(?!image\/)/i.test(url)) return '';
                return url;
            }

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
                            <h3>${escapeWeddingHtml(target.name)}</h3>
                            <div class="wedding-package-price">${formatWeddingIDR(target.price)}</div>
                            <div class="wedding-package-normal">Harga normal ${formatWeddingIDR(target.normal_price || target.price)}</div>
                            <p>${escapeWeddingHtml(target.description || '')}</p>
                            <div class="wedding-package-meta">Kapasitas: ${escapeWeddingHtml(target.capacity || 'Custom')}</div>
                            <a href="#service-lead" class="btn wedding-btn-gold w-100 mt-3 track-service-action" data-track-service="wedding-event" data-track-action="dynamic_package_cta" data-track-label="${escapeWeddingHtml(target.name)}">
                                Booking Paket Ini
                            </a>
                        </div>
                        <div class="wedding-package-detail">
                            <div class="row g-4 align-items-start">
                                <div class="col-lg-5">
                                    <img src="${escapeWeddingHtml(safeWeddingUrl(target.image))}" alt="${escapeWeddingHtml(target.name)}" class="wedding-package-image">
                                </div>
                                <div class="col-lg-7">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="wedding-package-list">
                                                <h4><i class="fas fa-tents me-2"></i>Fasilitas Utama</h4>
                                                <ul>
                                                    ${leftFacilities.map(item => `<li><span>✔</span><span>${escapeWeddingHtml(item)}</span></li>`).join('')}
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="wedding-package-list">
                                                <h4><i class="fas fa-gift me-2"></i>Highlight Paket</h4>
                                                <ul>
                                                    ${(rightFacilities.length ? rightFacilities : leftFacilities.slice(0, 6)).map(item => `<li><span>✔</span><span>${escapeWeddingHtml(item)}</span></li>`).join('')}
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

                // Wedding Gallery Slider
                const galleryTrack = document.getElementById('galleryTrack');
                const dots = document.querySelectorAll('.wedding-gallery-dot');
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');

                if (galleryTrack && dots.length > 0) {
                    let galleryIndex = 0;
                    const totalSlides = dots.length;

                    function updateGallery() {
                        galleryTrack.style.transform = `translateX(-${galleryIndex * 100}%)`;
                        dots.forEach((dot, idx) => {
                            dot.classList.toggle('active', idx === galleryIndex);
                        });
                    }

                    function nextSlide() {
                        galleryIndex = (galleryIndex + 1) % totalSlides;
                        updateGallery();
                    }

                    function prevSlide() {
                        galleryIndex = (galleryIndex - 1 + totalSlides) % totalSlides;
                        updateGallery();
                    }

                    if (nextBtn) {
                        nextBtn.addEventListener('click', nextSlide);
                    }

                    if (prevBtn) {
                        prevBtn.addEventListener('click', prevSlide);
                    }

                    dots.forEach((dot, idx) => {
                        dot.addEventListener('click', () => {
                            galleryIndex = idx;
                            updateGallery();
                        });
                    });

                    // Auto slide every 4 seconds
                    setInterval(nextSlide, 4000);
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
                                <div class="fw-bold">Membership</div>
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
                                <div class="text-muted small">Sambil menunggu wash selesai, Anda bisa pesan boba, milkshake, dan coffee.</div>
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
                                        <img src="{{ $assetUrl('storage/' . $service->image) }}" alt="{{ $service->name }}" class="product-img" loading="lazy" decoding="async">
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
                    <h2 class="kedai-title">Menunggu kendaraan selesai jadi lebih nyaman</h2>
                    <p class="kedai-desc">Sambil menunggu wash, Anda bisa pesan boba, milkshake, atau coffee. Lihat menu di bawah, lalu lanjut order atau tanya ketersediaan via WhatsApp.</p>
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
                @if($atkFeeProfiles->count() > 0)
                    <div class="section-header text-center mb-4 fade-up">
                        <h2 class="display-6 fw-800">Layanan Jasa & Keuangan</h2>
                        <p class="text-muted mb-0">Nikmati layanan keuangan cepat dan mudah di ATK Store kami.</p>
                    </div>
                    <div class="scroll-container fade-up mb-5">
                        @foreach($atkFeeProfiles as $feeProfile)
                            <div class="scroll-item">
                                <div class="card">
                                    <div class="product-img d-flex align-items-center justify-content-center bg-gradient-to-br from-blue-500 to-purple-600 text-white">
                                        <i class="fas fa-hand-holding-usd fa-3x"></i>
                                    </div>
                                    <div class="product-body d-flex flex-column h-100">
                                        <div class="chip mb-2 align-self-start bg-info text-white">
                                            Jasa
                                        </div>
                                        <h5 class="product-title mb-1">{{ $feeProfile->name }}</h5>
                                        <p class="small text-muted mb-3">
                                            @if($feeProfile->fee_mode === 'fixed')
                                                Biaya tetap untuk setiap transaksi
                                            @elseif($feeProfile->fee_mode === 'percentage')
                                                Biaya persentase dari nominal transaksi
                                            @elseif($feeProfile->fee_mode === 'fixed_percentage')
                                                Biaya tetap + persentase dari nominal transaksi
                                            @else
                                                Biaya sesuai tier nominal
                                            @endif
                                        </p>
                                        @if($feeProfile->tiers->count() > 0)
                                            <div class="mb-3">
                                                <h6 class="fw-bold mb-2">Detail Biaya:</h6>
                                                <ul class="list-unstyled small text-muted">
                                                    @foreach($feeProfile->tiers as $tier)
                                                        <li class="mb-1">
                                                            <i class="fas fa-check text-primary me-2"></i>
                                                            @if($tier->fee_type === 'fixed')
                                                                Rp {{ number_format($tier->fee_value, 0, ',', '.') }}
                                                            @elseif($tier->fee_type === 'percentage')
                                                                {{ $tier->fee_value }}%
                                                            @else
                                                                Rp {{ number_format($tier->fixed_value, 0, ',', '.') }} + {{ $tier->fee_value }}%
                                                            @endif
                                                            @if($tier->max_amount)
                                                                (Rp {{ number_format($tier->min_amount, 0, ',', '.') }} - Rp {{ number_format($tier->max_amount, 0, ',', '.') }})
                                                            @else
                                                                (Rp {{ number_format($tier->min_amount, 0, ',', '.') }}+)
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        <a href="#service-lead" class="btn btn-primary w-100 mt-auto track-service-action" data-track-service="atk-store" data-track-action="service_cta" data-track-label="{{ $feeProfile->name }}">
                                            Gunakan Layanan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="section-header text-center mb-4 fade-up">
                    <h2 class="display-6 fw-800">Produk Unggulan & Promo</h2>
                    <p class="text-muted mb-0">Pilih produk, kirim kebutuhan Anda, lalu kami bantu proses pemesanan dengan cepat.</p>
                </div>
                <div class="scroll-container fade-up">
                    @forelse($atkProducts as $product)
                        <div class="scroll-item">
                            <div class="card">
                                @if($product->image)
                                    <img src="{{ $assetUrl('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-img">
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
                        <h2 class="display-6 fw-800 mb-2 {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-section-title' : 'service-section-title' }}">Konsultasi & Booking</h2>
                        <p class="text-muted mb-0 {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-section-subtitle' : 'service-section-subtitle' }}">Isi form singkat, tim kami akan menghubungi Anda via WhatsApp untuk konfirmasi detail dan langkah berikutnya.</p>
                    </div>
                    <div class="landing-mini-card {{ $servicePage['slug'] === 'wedding-event' ? 'wedding-side-note' : 'service-side-note' }}">
                        <i class="fas fa-circle-info"></i>
                        <div>
                            <div class="fw-bold">Respon Lebih Cepat</div>
                            <div class="text-muted small">Data yang Anda kirim sudah sesuai kebutuhan layanan, jadi proses follow up lebih cepat dan tepat.</div>
                        </div>
                    </div>
                    @if($servicePage['slug'] === 'wedding-event')
                        <div class="wedding-side-stack">
                            <div class="wedding-side-card">
                                <strong>Detail Lebih Jelas</strong>
                                <span>Tanggal acara, jumlah tamu, dan lokasi membantu kami menyiapkan penawaran yang lebih sesuai.</span>
                            </div>
                            <div class="wedding-side-card">
                                <strong>Penawaran Lebih Tepat</strong>
                                <span>Setelah data masuk, tim akan menghubungi untuk rekomendasi paket atau opsi custom sesuai kebutuhan Anda.</span>
                            </div>
                        </div>
                    @else
                        <div class="service-side-stack">
                            <div class="service-side-card">
                                <strong>Lebih Cepat Dipahami</strong>
                                <span>Field pada form menyesuaikan kebutuhan layanan, jadi tim tidak perlu menebak kebutuhan utama Anda.</span>
                            </div>
                            <div class="service-side-card">
                                <strong>Lebih Mudah Ditindaklanjuti</strong>
                                <span>Setelah submit, Anda bisa langsung lanjut via WhatsApp untuk mempercepat konfirmasi detail.</span>
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
