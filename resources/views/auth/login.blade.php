<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Masuk - {{ config('app.name', 'MStore') }}</title>
    
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
    <div class="auth-shell">
        <div class="auth-header-actions d-flex justify-content-between align-items-center mb-3">
            <a href="{{ url('/') }}" class="back-home-btn">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </a>
            <button type="button" class="theme-toggle-btn" id="themeToggleLogin">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
        <div class="auth-card">
            <div class="auth-left">
                <div>
                    <div class="auth-left-title"> 
                        <img src="{{ app()->environment('production') ? secure_asset('img/logo.png') : asset('img/logo.png') }}" alt="MSTORE.NET">
                    </div>
                    <div class="auth-left-sub">
                        Platform monitoring jaringan fiber optic tercanggih. Kelola infrastruktur Anda dengan mudah.
                    </div>
                </div>
            </div>
            <div class="auth-right">
                @if (session('status'))
                    <div class="alert alert-success mb-3 w-100" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="w-100">
                    @csrf

                    <div class="mb-3">
                        <label for="login" class="auth-form-label">Username | Email</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text auth-input-addon border-end-0">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input id="login" class="form-control auth-input border-start-0 @error('login') is-invalid @enderror" type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="Masukan Username PPPoE/Hotspot atau ID Pelanggan" />
                            @error('login')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="auth-form-label">Password</label>
                        <div class="input-group auth-input-group auth-input-group-toggle">
                            <span class="input-group-text auth-input-addon border-end-0">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input id="password" class="form-control auth-input border-start-0 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" placeholder="Masukan Password" />
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 auth-links">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPasswordLogin">
                            <label class="form-check-label" for="showPasswordLogin">
                                Tampilkan Password
                            </label>
                        </div>
                        <a href="{{ route('password.forgot') }}">Lupa Password?</a>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-auth-primary">
                            Masuk Sekarang <span class="ms-2"><i class="fa-solid fa-arrow-right"></i></span>
                        </button>
                    </div>
                    
                    <div class="auth-separator">
                        <span>atau</span>
                    </div>

                    <div class="text-center auth-links">
                        <span>Belum punya akun?</span>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Buat Akun</a>
                        @else
                            <a href="{{ route('login') }}">Hubungi Admin</a>
                        @endif
                    </div>
                </form>

                <div class="auth-footer text-center mt-4">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MStore') }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        const savedTheme = document.documentElement.getAttribute('data-bs-theme') || localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        const themeToggleLogin = document.getElementById('themeToggleLogin');
        
        function updateThemeIcon(theme) {
            if (themeToggleLogin) {
                const icon = themeToggleLogin.querySelector('i');
                if (theme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
        }
        
        updateThemeIcon(savedTheme);

        if (themeToggleLogin) {
            themeToggleLogin.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('theme', next);
                updateThemeIcon(next);
            });
        }

        const loginPasswordField = document.getElementById('password');
        const loginShowPasswordCheckbox = document.getElementById('showPasswordLogin');
        if (loginPasswordField && loginShowPasswordCheckbox) {
            loginShowPasswordCheckbox.addEventListener('change', function () {
                loginPasswordField.type = loginShowPasswordCheckbox.checked ? 'text' : 'password';
            });
        }
        
        // Prevent 419 Page Expired on back button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
