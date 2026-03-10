<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - {{ config('app.name', 'MStore') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        Reset Password
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <form method="POST" action="{{ route('password.reset') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email ?? '') }}" placeholder="Masukkan email terdaftar" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kode OTP</label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Masukkan kode OTP 6 digit" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password baru" required>
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="password">Show</button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password baru" required>
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="password_confirmation">Show</button>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Reset Password</button>
                            </div>
                        </form>
                        <hr class="my-4">
                        <div class="text-center">
                            <a href="{{ route('password.forgot') }}">Kirim ulang kode OTP</a> · 
                            <a href="{{ route('login') }}">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-toggle-password]').forEach((toggleButton) => {
            toggleButton.addEventListener('click', function () {
                const inputId = this.getAttribute('data-toggle-password');
                const input = document.getElementById(inputId);
                if (!input) {
                    return;
                }
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = 'Hide';
                    return;
                }
                input.type = 'password';
                this.textContent = 'Show';
            });
        });
    </script>
</body>
</html>
