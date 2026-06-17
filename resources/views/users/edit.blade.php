@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Ubah Pengguna') }}: {{ $user->name }}</h5>
                </div>

                <div class="card-body">
                    <form action="{{ route('users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Nama -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">{{ __('Nama') }}</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">{{ __('Email') }}</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <label for="username" class="form-label">{{ __('Username') }}</label>
                                <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="form-control @error('username') is-invalid @enderror" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Username dapat diubah secara manual.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="attendance_card_code" class="form-label">{{ __('Kode ID Card Absensi') }}</label>
                                <input type="text" name="attendance_card_code" id="attendance_card_code" value="{{ old('attendance_card_code', $user->attendance_card_code) }}" class="form-control @error('attendance_card_code') is-invalid @enderror">
                                <div class="form-text">Gunakan kode ini untuk barcode/QR di kartu pegawai.</div>
                                @error('attendance_card_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Peran -->
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">{{ __('Peran') }}</label>
                                <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror">
                                    <option value="">{{ __('Pilih Peran') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->label }}</option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nomor HP -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label">{{ __('Nomor HP') }}</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Username Radius -->
                            <div class="col-md-6">
                                <label for="radius_username" class="form-label">Radius Username</label>
                                <input type="text" name="radius_username" id="radius_username" value="{{ old('radius_username', $user->radius_username) }}" class="form-control @error('radius_username') is-invalid @enderror">
                                @error('radius_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Gaji -->
                            <div class="col-md-6">
                                <label for="monthly_salary" class="form-label">{{ __('Gaji Pokok Bulanan (IDR)') }}</label>
                                <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary', $user->monthly_salary ?? 0) }}" class="form-control @error('monthly_salary') is-invalid @enderror">
                                <div class="form-text">{{ __('Gaji pokok satu bulan (akan dibagi hari kerja untuk hitung harian).') }}</div>
                                @error('monthly_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="daily_salary" class="form-label">{{ __('Gaji Harian Manual (IDR)') }}</label>
                                <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', $user->daily_salary ?? 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                                <div class="form-text">{{ __('Isi jika ingin menggunakan nilai tetap per hari (abaikan gaji bulanan).') }}</div>
                                @error('daily_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Bank Info -->
                            <div class="col-md-4">
                                <label for="bank_name" class="form-label">{{ __('Nama Bank') }}</label>
                                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->bank_name) }}" class="form-control @error('bank_name') is-invalid @enderror" placeholder="BCA/Mandiri/BRI">
                                @error('bank_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="bank_account_number" class="form-label">{{ __('Nomor Rekening') }}</label>
                                <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" class="form-control @error('bank_account_number') is-invalid @enderror">
                                @error('bank_account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="bank_account_name" class="form-label">{{ __('Atas Nama') }}</label>
                                <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" class="form-control @error('bank_account_name') is-invalid @enderror">
                                @error('bank_account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status aktif -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="form-check-input">
                                    <label for="is_active" class="form-check-label">
                                        {{ __('Akun Aktif') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="border-top pt-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="reset_default_password" id="reset_default_password" value="1" class="form-check-input">
                                        <label class="form-check-label" for="reset_default_password">
                                            {{ __('Reset password pengguna ini ke default') }} `12345678`
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                {{ __('Batal') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-1"></i> {{ __('Perbarui Pengguna') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const usernameInput = document.getElementById('username');
        const monthlySalaryInput = document.getElementById('monthly_salary');
        const dailySalaryInput = document.getElementById('daily_salary');
        const workingDays = {{ \App\Models\Setting::getValue('attendance_working_days', 28) }};

        if (nameInput && usernameInput) {
            const slugify = (value) => value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
            const syncUsername = () => {
                // Only sync if user hasn't edited the username yet
                usernameInput.value = slugify(nameInput.value);
            };
            nameInput.addEventListener('input', function() {
                // Only auto-sync if the username was previously matching the name's slug
                if (usernameInput.value === slugify(nameInput.defaultValue)) {
                    syncUsername();
                }
            });
        }

        if (monthlySalaryInput && dailySalaryInput) {
            monthlySalaryInput.addEventListener('input', function() {
                const monthly = parseFloat(this.value) || 0;
                const daily = Math.round(monthly / workingDays);
                dailySalaryInput.value = daily;
            });
        }
    });
</script>
@endpush
