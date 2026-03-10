@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Edit User') }}: {{ $user->name }}</h5>
                </div>

                <div class="card-body">
                    <form action="{{ route('users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">{{ __('Name') }}</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                <div class="form-text">Wajib diisi.</div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">{{ __('Email') }}</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                                <div class="form-text">Opsional. Digunakan untuk reset password.</div>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="form-control @error('username') is-invalid @enderror">
                                <div class="form-text">Wajib untuk login. Jika dikosongkan akan dibuat otomatis.</div>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Role -->
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">{{ __('Role') }}</label>
                                <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror">
                                    <option value="">{{ __('Select Role') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Wajib diisi.</div>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                <div class="form-text">Opsional. Digunakan untuk reset password.</div>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Radius Username -->
                            <div class="col-md-6">
                                <label for="radius_username" class="form-label">Radius Username</label>
                                <input type="text" name="radius_username" id="radius_username" value="{{ old('radius_username', $user->radius_username) }}" class="form-control @error('radius_username') is-invalid @enderror">
                                <div class="form-text">Opsional. Isi hanya jika akun terhubung ke RADIUS.</div>
                                @error('radius_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Daily Salary -->
                            <div class="col-md-6">
                                <label for="daily_salary" class="form-label">{{ __('Daily Salary (IDR)') }}</label>
                                <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', $user->daily_salary ?? 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                                <div class="form-text">{{ __('Gaji per hari kehadiran (untuk teknisi/staff).') }}</div>
                                @error('daily_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Active Status -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="form-check-input">
                                    <label for="is_active" class="form-check-label">
                                        {{ __('Active Account') }}
                                    </label>
                                </div>
                            </div>

                            <!-- Password Section -->
                            <div class="col-12 mt-4">
                                <div class="border-top pt-3">
                                    <h6 class="fw-bold mb-3">{{ __('Change Password') }}</h6>
                                    <p class="text-muted small mb-3">{{ __("Leave blank if you don't want to change the password.") }}</p>
                                    
                                    <div class="row g-3">
                                        <!-- Password -->
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">{{ __('New Password') }}</label>
                                            <div class="input-group">
                                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Tampilkan/Sembunyikan Password">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="col-md-6">
                                            <label for="password_confirmation" class="form-label">{{ __('Confirm New Password') }}</label>
                                            <div class="input-group">
                                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password_confirmation" aria-label="Tampilkan/Sembunyikan Password">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-1"></i> Update User
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
