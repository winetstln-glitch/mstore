@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Tambah Teknisi Baru') }}</h5>
                <a href="{{ route('technicians.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Daftar') }}
                </a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('technicians.store') }}" class="max-w-2xl mx-auto">
                    @csrf

                    <!-- Nama -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">{{ __('Nama') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nomor HP -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">{{ __('Nomor HP') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Telegram Chat ID -->
                    <div class="mb-3">
                        <label for="telegram_chat_id" class="form-label fw-bold">{{ __('ID Chat Telegram (Opsional)') }}</label>
                        <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="{{ old('telegram_chat_id') }}" class="form-control @error('telegram_chat_id') is-invalid @enderror">
                        <div class="form-text">
                            {{ __('ID Chat Telegram untuk notifikasi bot.') }}
                        </div>
                        @error('telegram_chat_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Monthly Salary -->
                    <div class="mb-3">
                        <label for="monthly_salary" class="form-label fw-bold">{{ __('Gaji Pokok Bulanan (IDR)') }}</label>
                        <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary', 0) }}" class="form-control @error('monthly_salary') is-invalid @enderror">
                        <div class="form-text">{{ __('Gaji pokok satu bulan.') }}</div>
                        @error('monthly_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Daily Salary -->
                    <div class="mb-3">
                        <label for="daily_salary" class="form-label fw-bold">{{ __('Gaji Harian Manual (IDR)') }}</label>
                        <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                        <div class="form-text">{{ __('Isi jika ingin menggunakan nilai tetap per hari.') }}</div>
                        @error('daily_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">{{ __('Password') }}</label>
                        <input type="password" name="password" id="password" required class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Konfirmasi password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-bold">{{ __('Konfirmasi Password') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required class="form-control">
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Teknisi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const dailySalaryInput = document.getElementById('daily_salary');
    const workingDays = {{ \App\Models\Setting::getValue('attendance_working_days', 28) }};

    if (monthlySalaryInput && dailySalaryInput) {
        monthlySalaryInput.addEventListener('input', function() {
            const monthly = parseFloat(this.value) || 0;
            const daily = Math.round(monthly / workingDays);
            dailySalaryInput.value = daily;
        });
    }
});
</script>
@endsection
