@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Ubah Teknisi') }}: {{ $technician->name }}</h5>
                <a href="{{ route('technicians.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Daftar') }}
                </a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('technicians.update', $technician) }}" class="max-w-2xl mx-auto">
                    @csrf
                    @method('PUT')

                    <!-- Nama -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">{{ __('Nama') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $technician->name) }}" required class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $technician->email) }}" required class="form-control @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nomor HP -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">{{ __('Nomor HP') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $technician->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Telegram Chat ID -->
                    <div class="mb-3">
                        <label for="telegram_chat_id" class="form-label fw-bold">{{ __('ID Chat Telegram (Opsional)') }}</label>
                        <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="{{ old('telegram_chat_id', $technician->telegram_chat_id) }}" class="form-control @error('telegram_chat_id') is-invalid @enderror">
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
                        <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary', $technician->employee->monthly_salary ?? $technician->monthly_salary ?? 0) }}" class="form-control @error('monthly_salary') is-invalid @enderror">
                        <div class="form-text">{{ __('Gaji pokok satu bulan.') }}</div>
                        @error('monthly_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Daily Salary -->
                    <div class="mb-3">
                        <label for="daily_salary" class="form-label fw-bold">{{ __('Gaji Harian Manual (IDR)') }}</label>
                        <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', $technician->employee->daily_salary ?? $technician->daily_salary ?? 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                        <div class="form-text">{{ __('Isi jika ingin menggunakan nilai tetap per hari.') }}</div>
                        @error('daily_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $technician->is_active) ? 'checked' : '' }} class="form-check-input">
                            <label for="is_active" class="form-check-label">{{ __('Akun Aktif') }}</label>
                        </div>
                    </div>

                    <div class="border-top pt-4 mt-4">
                        <h6 class="fw-bold mb-3">{{ __('Ubah Password (Opsional)') }}</h6>
                        
                        <!-- Password baru -->
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password Baru') }}</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                            <div class="form-text">{{ __('Kosongkan jika ingin mempertahankan password saat ini.') }}</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Konfirmasi password baru -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Konfirmasi Password Baru') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Perbarui Teknisi') }}
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
