@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Add New Technician') }}</h5>
                <a href="{{ route('technicians.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('technicians.store') }}" class="max-w-2xl mx-auto">
                    @csrf

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">
                        <div class="form-text">Wajib diisi.</div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                        <div class="form-text">Opsional. Digunakan untuk reset password.</div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">{{ __('Phone') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                        <div class="form-text">Opsional. Digunakan untuk reset password.</div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Telegram Chat ID -->
                    <div class="mb-3">
                        <label for="telegram_chat_id" class="form-label fw-bold">{{ __('Telegram Chat ID (Optional)') }}</label>
                        <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="{{ old('telegram_chat_id') }}" class="form-control @error('telegram_chat_id') is-invalid @enderror">
                        <div class="form-text">
                            {{ __('ID Chat Telegram untuk notifikasi bot.') }}
                        </div>
                        @error('telegram_chat_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Daily Salary -->
                    <div class="mb-3">
                        <label for="daily_salary" class="form-label fw-bold">{{ __('Daily Salary (IDR)') }}</label>
                        <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                        <div class="form-text">{{ __('Gaji per hari kehadiran.') }}</div>
                        @error('daily_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">{{ __('Password') }}</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" required class="form-control @error('password') is-invalid @enderror">
                            <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Tampilkan/Sembunyikan Password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-bold">{{ __('Confirm Password') }}</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" required class="form-control">
                            <button class="btn btn-outline-secondary" type="button" data-toggle-password="password_confirmation" aria-label="Tampilkan/Sembunyikan Password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Create Technician') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
