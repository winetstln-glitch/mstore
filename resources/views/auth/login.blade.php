<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Masuk - {{ config('app.name', 'MStore') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Custom Auth CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth-custom.css') }}">
    
    <script>
        (function() {
            try {
                const theme = localStorage.getItem('nms_theme');
                if (theme === 'dark') document.documentElement.classList.add('dark-mode');
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="auth-shell position-relative">
        <button type="button" class="theme-toggle-btn" id="themeToggleLogin">
            <i class="fa-solid fa-moon"></i>
        </button>
        <div class="auth-card">
            <div class="auth-left">
                <div>
                    <div class="auth-left-title"> 
                        <img src="{{ asset('img/logo.png') }}" alt="MSTORE.NET">
                    </div>
                    <div class="auth-left-sub">
                        Platform monitoring jaringan fiber optic tercanggih. Kelola infrastruktur Anda dengan mudah.
                    </div>
                </div>
            </div>
            <div class="auth-right">
                <div class="mb-4 w-100 text-center">
                    <div class="auth-header-title">Welcome Back! <span>👋</span></div>
                    <div class="auth-header-sub">Silakan login untuk mengakses dashboard.</div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-3 w-100" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="w-100">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="auth-form-label">Username</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text auth-input-addon border-end-0">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input id="email" class="form-control auth-input border-start-0 @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Masukan Username" />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
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
                            <button type="button" class="btn auth-input-toggle border-start-0" onclick="togglePasswordVisibility('password')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 auth-links">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">
                                Ingat Saya
                            </label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">Lupa Password?</a>
                        @endif
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-auth-primary">
                            Masuk Sekarang <span class="ms-2"><i class="fa-solid fa-arrow-right"></i></span>
                        </button>
                    </div>

                    <div class="text-center auth-links">
                        <span>Belum punya akun?</span>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Daftar Sekarang</a>
                        @endif
                    </div>
                </form>

                <div class="auth-footer mt-4">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MStore') }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
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
                if (next === 'dark') {
                    document.documentElement.classList.add('dark-mode');
                } else {
                    document.documentElement.classList.remove('dark-mode');
                }
                localStorage.setItem('theme', next);
                localStorage.setItem('nms_theme', next);
                updateThemeIcon(next);
            });
        }

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            if (!field) return;
            const sibling = field.nextElementSibling;
            if (!sibling) return;
            const icon = sibling.querySelector('i');
            if (!icon) return;

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
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
