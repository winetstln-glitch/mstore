@extends('layouts.app')

@section('title', 'Ganti Kredensial Hotspot/PPPoE')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <strong>Ganti Username/Password (MixRADIUS)</strong>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    <form method="POST" action="{{ route('client.credentials.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username Saat Ini</label>
                            <input class="form-control" value="{{ $user->radius_username ?? $user->username ?? $user->email }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="new_username" class="form-label">Username Baru</label>
                            <input id="new_username" name="new_username" type="text" class="form-control @error('new_username') is-invalid @enderror" value="{{ old('new_username') }}" required>
                            @error('new_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <div class="input-group">
                                <input id="new_password" name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" required>
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="new_password" aria-label="Tampilkan/Sembunyikan Password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-warning">Perbarui Kredensial</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 text-muted">
        Perubahan dikirim ke server RADIUS. Jika perangkat masih terhubung, perlu reconnect agar kredensial baru berlaku.
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-toggle-password]').forEach((toggleButton) => {
        toggleButton.addEventListener('click', function () {
            const inputId = this.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon?.classList.remove('fa-eye');
                icon?.classList.add('fa-eye-slash');
                return;
            }
            input.type = 'password';
            icon?.classList.remove('fa-eye-slash');
            icon?.classList.add('fa-eye');
        });
    });
</script>
@endpush
