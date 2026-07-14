<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lupa Password - {{ config('app.name', 'MStore') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        Lupa Password
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <form method="POST" action="{{ route('password.send_otp', [], false) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email atau Nomor HP</label>
                                <input type="text" name="identifier" class="form-control @error('identifier') is-invalid @enderror" value="{{ old('identifier') }}" placeholder="Masukkan email atau nomor HP terdaftar" required>
                                @error('identifier')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Kirim Kode OTP</button>
                            </div>
                        </form>
                        <hr class="my-4">
                        <div class="text-center">
                            <a href="{{ route('login') }}">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
