<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Masuk</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ app()->environment('production') ? secure_asset('favicon.svg') : asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ app()->environment('production') ? secure_asset('favicon.ico') : asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Custom Auth CSS -->
    <link rel="stylesheet" href="{{ app()->environment('production') ? secure_asset('css/auth-custom.css') : asset('css/auth-custom.css') }}">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
        }
        @media (prefers-color-scheme: dark) {
            html[data-bs-theme="dark"] body {
                background: linear-gradient(135deg, #1e3a8a 0%, #4c1d95 100%);
            }
        }
        .customer-auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
            position: relative;
            overflow: hidden;
        }
        .customer-auth-shell::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            top: -200px;
            right: -150px;
        }
        .customer-auth-shell::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            bottom: -150px;
            left: -100px;
        }
        .customer-auth-card {
            width: 100%;
            max-width: 980px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 22px;
            box-shadow: 0 25px 70px rgba(31, 38, 135, 0.35);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }
        html[data-bs-theme="dark"] .customer-auth-card {
            background: rgba(23, 25, 55, 0.97);
        }
        .customer-hero {
            padding: 48px 42px;
            background: linear-gradient(160deg, #0ea5e9 0%, #10b981 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        html[data-bs-theme="dark"] .customer-hero {
            background: linear-gradient(160deg, #0284c7 0%, #059669 100%);
        }
        .customer-hero::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            bottom: -120px;
            right: -80px;
        }
        .customer-hero::after {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            top: -60px;
            left: -40px;
        }
        .hero-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 2;
        }
        .hero-logo img {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #fff;
            padding: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .hero-logo-text {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            margin: 40px 0;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(6px);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .hero-title {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0 0 14px;
        }
        .hero-desc {
            font-size: 14.5px;
            opacity: 0.92;
            line-height: 1.6;
            margin: 0 0 22px;
        }
        .hero-features {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 12px;
        }
        .hero-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
        }
        .hero-feature-icon {
            width: 26px;
            height: 26px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .hero-footer {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            opacity: 0.9;
        }
        .hero-footer a {
            color: #fff;
            text-decoration: underline;
            opacity: 0.95;
        }
        .customer-form-wrap {
            padding: 46px 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .form-avatar {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0ea5e9, #10b981);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
            margin-bottom: 14px;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }
        .form-title {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 4px;
        }
        .form-subtitle {
            font-size: 13.5px;
            opacity: 0.65;
            margin: 0;
        }
        .cust-auth-form-label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            opacity: 0.85;
        }
        .cust-auth-input-group {
            background: #f5f7fa;
            border-radius: 12px;
            border: 1.5px solid transparent;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        html[data-bs-theme="dark"] .cust-auth-input-group {
            background: rgba(255,255,255,0.04);
        }
        .cust-auth-input-group:focus-within {
            border-color: #0ea5e9;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
        }
        html[data-bs-theme="dark"] .cust-auth-input-group:focus-within {
            background: rgba(255,255,255,0.06);
        }
        .cust-auth-input-addon {
            background: transparent;
            border: none;
            padding: 0 12px;
            opacity: 0.55;
        }
        .cust-auth-input {
            background: transparent;
            border: none;
            padding: 14px 16px 14px 0;
            font-size: 14.5px;
            box-shadow: none !important;
        }
        .cust-auth-input::placeholder {
            opacity: 0.45;
        }
        .cust-auth-btn-primary {
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 6px 18px rgba(14, 165, 233, 0.35);
        }
        .cust-auth-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(14, 165, 233, 0.42);
            color: #fff;
        }
        .cust-auth-btn-primary:active {
            transform: translateY(0);
        }
        .back-home-cust {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 999px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-home-cust:hover {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }
        .cust-auth-footer {
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid rgba(0,0,0,0.07);
            text-align: center;
            font-size: 12.5px;
            opacity: 0.6;
        }
        html[data-bs-theme="dark"] .cust-auth-footer {
            border-top-color: rgba(255,255,255,0.08);
        }
        .theme-toggle-cust {
            position: absolute;
            bottom: 16px;
            right: 16px;
            z-index: 10;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            color: #fff;
            transition: all 0.2s ease;
        }
        .theme-toggle-cust:hover {
            background: rgba(255,255,255,0.25);
        }
        @media (max-width: 780px) {
            .customer-auth-card {
                grid-template-columns: 1fr;
                max-width: 440px;
            }
            .customer-hero {
                padding: 58px 28px 30px;
            }
            .customer-form-wrap {
                padding: 32px 26px 38px;
            }
            .hero-title {
                font-size: 24px;
            }
            .theme-toggle-cust {
                bottom: 16px;
                right: 16px;
            }
        }
    </style>

    <script>
        (function() {
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
    <div class="customer-auth-shell">
        <button type="button" class="theme-toggle-cust" id="themeToggleCust" aria-label="Toggle tema">
            <i class="fa-solid fa-moon"></i>
        </button>

        <div class="customer-auth-card">
            <div class="customer-hero">
                <a href="{{ url('/') }}" class="back-home-cust">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>

                <div class="hero-logo">
                    <img src="{{ app()->environment('production') ? secure_asset('img/logo.png') : asset('img/logo.png') }}" alt="Logo MSTORE" onerror="this.style.display='none'">
                    <span class="hero-logo-text">{{ config('app.name', 'MStore') }}</span>
                </div>

                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="fa-solid fa-house-circle-check"></i>
                        PORTAL PELANGGAN
                    </span>
                    <h1 class="hero-title">Selamat Datang Kembali, Pelanggan Terhormat! 👋</h1>
                    <p class="hero-desc">
                        Kelola layanan internet rumahan Anda sendiri dengan mudah. Cek tagihan, ganti password WiFi ONU, lihat status jaringan, dan pantau perangkat terhubung - semua dalam satu portal!
                    </p>
                    <ul class="hero-features">
                        <li>
                            <span class="hero-feature-icon"><i class="fa-solid fa-wifi"></i></span>
                            <span><strong>Ganti Password WiFi ONU</strong> kapan saja tanpa panggil teknisi</span>
                        </li>
                        <li>
                            <span class="hero-feature-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                            <span><strong>Cek &amp; Bayar Tagihan</strong> online via berbagai payment channel</span>
                        </li>
                        <li>
                            <span class="hero-feature-icon"><i class="fa-solid fa-signal"></i></span>
                            <span><strong>Pantau Status Jaringan</strong> real-time ONU dan koneksi Anda</span>
                        </li>
                        <li>
                            <span class="hero-feature-icon"><i class="fa-solid fa-key"></i></span>
                            <span><strong>Kelola Kredensial PPPoE</strong> sendiri dengan aman</span>
                        </li>
                    </ul>
                </div>

                <div class="hero-footer">
                    <i class="fa-brands fa-whatsapp fa-lg"></i>
                    <div>Butuh bantuan? <a href="https://wa.me/{{ $waNumber ?? '6281234567890' }}" target="_blank" rel="noopener noreferrer">Chat WhatsApp CS kami</a></div>
                </div>
            </div>

            <div class="customer-form-wrap">
                <div class="form-header">
                    <div class="form-avatar"><i class="fa-solid fa-user-circle"></i></div>
                    <h2 class="form-title">Masuk ke Akun Pelanggan</h2>
                    <p class="form-subtitle">Masukkan username &amp; password akun portal Anda</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-3 w-100" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login', [], false) }}" class="w-100">
                    @csrf
                    <input type="hidden" name="mode" value="customer">

                    <div class="mb-3">
                        <label for="login" class="cust-auth-form-label">Username Portal / PPPoE</label>
                        <div class="input-group cust-auth-input-group">
                            <span class="input-group-text cust-auth-input-addon">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input id="login" class="form-control cust-auth-input @error('login') is-invalid @enderror" type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="Contoh: pelanggan123 atau pppoe_username" />
                            @error('login')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="cust-auth-form-label">Password</label>
                        <div class="input-group cust-auth-input-group">
                            <span class="input-group-text cust-auth-input-addon">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input id="password" class="form-control cust-auth-input @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password akun Anda" />
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 auth-links" style="font-size: 13px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPasswordLogin">
                            <label class="form-check-label" for="showPasswordLogin">
                                Tampilkan password
                            </label>
                        </div>
                        <a href="{{ route('password.forgot') }}">Lupa password?</a>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="cust-auth-btn-primary">
                            Masuk ke Portal Pelanggan <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                    <div class="auth-separator" style="margin: 18px 0;">
                        <span>catatan</span>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 12.5px; border-radius: 10px;">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <strong>Belum / tidak punya akun?</strong>
                        <div>Username &amp; password sama dengan akun PPPoE Anda. Jika login otomatis gagal, silakan hubungi Admin / CS ISP kami untuk aktivasi portal.</div>
                    </div>
                </form>

                <div class="cust-auth-footer">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MStore') }} • Portal Pelanggan Terintegrasi
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        const savedTheme = document.documentElement.getAttribute('data-bs-theme') || localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        const themeToggleCust = document.getElementById('themeToggleCust');

        function updateThemeIconCust(theme) {
            if (themeToggleCust) {
                const icon = themeToggleCust.querySelector('i');
                if (theme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
        }

        updateThemeIconCust(savedTheme);

        if (themeToggleCust) {
            themeToggleCust.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('theme', next);
                updateThemeIconCust(next);
            });
        }

        const loginPasswordField = document.getElementById('password');
        const loginShowPasswordCheckbox = document.getElementById('showPasswordLogin');
        if (loginPasswordField && loginShowPasswordCheckbox) {
            loginShowPasswordCheckbox.addEventListener('change', function () {
                loginPasswordField.type = loginShowPasswordCheckbox.checked ? 'text' : 'password';
            });
        }

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
