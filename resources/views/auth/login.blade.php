<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Masuk - {{ config('app.name', 'MStore') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <style>
        body {
            background-color: #f5f7fb;
        }
        .auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-card {
            max-width: 960px;
            width: 100%;
            background-color: #ffffff;
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.1fr 1.1fr;
        }
        .auth-left {
            background: radial-gradient(circle at 0 0, rgba(255,255,255,0.22), transparent 55%), radial-gradient(circle at 100% 0, rgba(255,255,255,0.08), transparent 55%), linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 24px;
        }
        .auth-left-icon {
            width: 52px;
            height: 52px;
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.12);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .auth-left-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .auth-left-sub {
            font-size: 14px;
            opacity: 0.9;
            max-width: 260px;
        }
        .auth-right {
            padding: 40px 40px 36px 40px;
            display: flex;
            flex-direction: column;
        }
        .auth-header-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }
        .auth-header-sub {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        .auth-form-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .auth-input {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            font-size: 14px;
        }
        .auth-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
        }
        .auth-input-addon {
            border-radius: 12px 0 0 12px;
            border-right: 0;
            border-color: #e5e7eb;
            background-color: #ffffff;
        }
        .auth-input-group input {
            border-radius: 0 12px 12px 0;
        }
        .auth-input-group-toggle input {
            border-radius: 0;
        }
        .auth-input-toggle {
            border-radius: 0 12px 12px 0;
            border-color: #e5e7eb;
            background-color: #ffffff;
            padding-inline: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .auth-footer {
            margin-top: auto;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
        .btn-auth-primary {
            border-radius: 999px;
            font-weight: 600;
            font-size: 14px;
            padding-block: 10px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            color: #ffffff;
        }
        .btn-auth-primary:hover {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .theme-toggle-btn {
            position: absolute;
            top: 18px;
            right: 24px;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: rgba(255,255,255,0.9);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #4b5563;
        }
        .auth-links {
            font-size: 12px;
        }
        .auth-links a {
            text-decoration: none;
            font-weight: 600;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        @media (max-width: 992px) {
            .auth-shell {
                padding: 16px;
            }
            .auth-card {
                grid-template-columns: 1fr;
            }
            .auth-left {
                padding: 28px 24px 16px 24px;
                align-items: flex-start;
            }
            .auth-right {
                padding: 28px 24px 24px 24px;
            }
        }
        html.dark-mode body {
            background-color: #020617;
        }
        html.dark-mode .auth-card {
            background-color: #020617;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.9);
        }
        html.dark-mode .auth-right {
            background: #020617;
        }
        html.dark-mode .auth-header-title {
            color: #e5e7eb;
        }
        html.dark-mode .auth-header-sub {
            color: #9ca3af;
        }
        html.dark-mode .auth-form-label {
            color: #e5e7eb;
        }
        html.dark-mode .auth-input {
            background-color: #020617;
            border-color: #1f2937;
            color: #e5e7eb;
        }
        html.dark-mode .auth-input::placeholder {
            color: #6b7280;
        }
        html.dark-mode .auth-input-addon {
            background-color: #020617;
            border-color: #1f2937;
            color: #9ca3af;
        }
        html.dark-mode .auth-input-toggle {
            background-color: #020617;
            border-color: #1f2937;
            color: #9ca3af;
        }
        html.dark-mode .auth-footer {
            color: #6b7280;
        }
        html.dark-mode .theme-toggle-btn {
            background: #020617;
            color: #e5e7eb;
            border-color: #374151;
        }
    </style>
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
                <div class="auth-left-icon">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div>
                    <div class="auth-left-title">MSTORE.NET</div>
                    <div class="auth-left-sub">
                        Platform monitoring jaringan fiber optic tercanggih. Kelola infrastruktur Anda dengan mudah.
                    </div>
                </div>
            </div>
            <div class="auth-right">
                <div class="mb-4">
                    <div class="auth-header-title">Welcome Back! <span>👋</span></div>
                    <div class="auth-header-sub">Silakan login untuk mengakses dashboard.</div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success mb-3" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="auth-form-label">Username</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text auth-input-addon border-end-0">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input id="email" class="form-control auth-input border-start-0 @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="mstore" />
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
                            <input id="password" class="form-control auth-input border-start-0 @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                            <button type="button" class="btn auth-input-toggle border-start-0" onclick="togglePasswordVisibility('password')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
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
    </script>
</body>
</html>
