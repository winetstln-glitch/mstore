@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Create New User') }}</h5>
                </div>

                <div class="card-body">
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">{{ __('Name') }}</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">{{ __('Email') }}</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" id="username_preview" class="form-control" value="{{ old('name') ? Str::slug(old('name'), '_') : '' }}" placeholder="Otomatis dari nama" readonly>
                                <div class="form-text">Username dibuat otomatis dari nama.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="attendance_card_code" class="form-label">Kode ID Card Absensi</label>
                                <input type="text" name="attendance_card_code" id="attendance_card_code" value="{{ old('attendance_card_code') }}" class="form-control @error('attendance_card_code') is-invalid @enderror">
                                <div class="form-text">Jika kosong, otomatis pakai username.</div>
                                @error('attendance_card_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Password Default</label>
                                <input type="text" class="form-control" value="12345678" readonly>
                                <div class="form-text">Semua user baru memakai password default ini.</div>
                            </div>

                            <!-- Role -->
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">{{ __('Role') }}</label>
                                <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror">
                                    <option value="">{{ __('Select Role') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->label }}</option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Radius Username -->
                            <div class="col-md-6">
                                <label for="radius_username" class="form-label">Radius Username</label>
                                <input type="text" name="radius_username" id="radius_username" value="{{ old('radius_username') }}" class="form-control @error('radius_username') is-invalid @enderror">
                                @error('radius_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Daily Salary -->
                            <div class="col-md-6">
                                <label for="daily_salary" class="form-label">{{ __('Daily Salary (IDR)') }}</label>
                                <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                                <div class="form-text">{{ __('Gaji per hari kehadiran (untuk teknisi/staff).') }}</div>
                                @error('daily_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Active Status -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="form-check-input">
                                    <label for="is_active" class="form-check-label">
                                        {{ __('Active Account') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-1"></i> {{ __('Create User') }}
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
        const usernamePreview = document.getElementById('username_preview');
        if (!nameInput || !usernamePreview) {
            return;
        }
        const slugify = (value) => value
            .toLowerCase()
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        const syncPreview = () => {
            usernamePreview.value = slugify(nameInput.value);
        };
        nameInput.addEventListener('input', syncPreview);
        syncPreview();
    });
</script>
@endpush
