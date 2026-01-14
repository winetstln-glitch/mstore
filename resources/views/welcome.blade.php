<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MStore') }} - Welcome</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2rem 1rem;
        }

        [data-bs-theme="dark"] .hero-section {
            background: linear-gradient(135deg, #1e2024 0%, #23272b 100%);
        }

        .welcome-card {
            max-width: 900px;
            width: 100%;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.175);
            overflow: hidden;
        }

        .brand-section {
            background-color: #3f6ad8;
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .content-section {
            padding: 3rem;
            background-color: var(--bs-body-bg);
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .btn-login {
            background-color: #3f6ad8;
            border-color: #3f6ad8;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }

        .btn-login:hover {
            background-color: #2955c8;
            border-color: #2955c8;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top bg-body-tertiary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fa-solid fa-server me-2"></i> {{ config('app.name', 'MStore') }}
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary border-0" id="themeToggle" title="Toggle Theme">
                    <i class="fa-solid fa-moon"></i>
                </button>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm fw-bold">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm fw-bold">Log in</a>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="welcome-card">
            <div class="row g-0">
                <div class="col-md-5 brand-section">
                    <div class="mb-4">
                        <i class="fa-solid fa-network-wired fa-3x"></i>
                    </div>
                    <h2 class="fw-bold mb-3">ISP Management System</h2>
                    <p class="lead opacity-75 mb-0">Streamline your internet service provider operations with our all-in-one solution.</p>
                </div>
                <div class="col-md-7 content-section">
                    <h4 class="fw-bold mb-4">Core Features</h4>
                    
                    <div class="feature-item">
                        <div class="feature-icon bg-primary-subtle text-primary">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Customer Management</h6>
                            <p class="text-muted small mb-0">Efficiently manage customer profiles, subscriptions, and billing cycles.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Ticketing System</h6>
                            <p class="text-muted small mb-0">Track and resolve customer support issues with an integrated ticketing workflow.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon bg-warning-subtle text-warning">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Installation Scheduling</h6>
                            <p class="text-muted small mb-0">Organize technician visits and track new installation progress.</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-2 border-top">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-login text-white w-100 shadow-sm">
                                Go to Dashboard <i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-login text-white w-100 shadow-sm">
                                Login to Access <i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-3 text-center text-body-secondary small bg-body-tertiary">
        <div class="container">
            &copy; {{ date('Y') }} {{ config('app.name', 'MStore') }}. All rights reserved.
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Theme Toggle Logic
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        const html = document.documentElement;

        // Check local storage
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);
        updateIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });

        function updateIcon(theme) {
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
    </script>
</body>
</html>