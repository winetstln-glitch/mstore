@extends('layouts.app')

@section('title', 'Ubah Karyawan')

@section('content')
<div class="container-fluid wash-employee-edit-page">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ubah Karyawan</h1>
        <a href="{{ route('wash.employees.index') }}" class="btn btn-sm btn-secondary shadow-sm" title="Kembali">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i>
            <span class="d-none d-md-inline ms-1">Kembali</span>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detail Karyawan</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('wash.employees.update', $employee->id) }}" method="POST" id="editEmployeeForm">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $employee->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Nomor Telepon</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Opsi Akun Login') }}</label>
                    <div class="d-flex flex-column gap-2">
                        <label class="border rounded p-2 d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="radio" name="user_option" value="existing" {{ old('user_option', 'existing') === 'existing' ? 'checked' : '' }}>
                            <span>{{ __('Pakai akun yang sudah ada') }}</span>
                        </label>
                        <label class="border rounded p-2 d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="radio" name="user_option" value="new" {{ old('user_option') === 'new' ? 'checked' : '' }}>
                            <span>{{ __('Buat akun baru di sini') }}</span>
                        </label>
                    </div>
                </div>

                <div id="newAccountFields" class="d-none">
                    <div class="mb-3">
                        <label for="username" class="form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">{{ __('Konfirmasi Password') }} <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>
                </div>

                <div class="mb-3" id="existingAccountField">
                    <label for="user_id" class="form-label">Tautkan ke Akun</label>
                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                        <option value="">{{ __('— Optional —') }}</option>
                        @foreach(($users ?? []) as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $employee->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} @if($user->email) ({{ $user->email }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Mengaitkan akun memungkinkan absensi seperti teknisi.') }}</small>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span class="d-none d-md-inline ms-1">Perbarui Karyawan</span>
                </button>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow d-md-none" style="z-index: 1030;">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.employees.index') }}" class="btn btn-outline-secondary w-50">Kembali</a>
            <button type="submit" class="btn btn-primary w-50" form="editEmployeeForm">Perbarui</button>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-employee-edit-page .form-control,
    .wash-employee-edit-page .form-select {
        min-height: 44px;
    }

    @media (max-width: 767.98px) {
        .wash-employee-edit-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 5rem !important;
        }

        .wash-employee-edit-page .h3 {
            font-size: 1.1rem;
            margin-bottom: 0.9rem !important;
        }

        .wash-employee-edit-page .card-body {
            padding: 0.9rem;
        }
    }
</style>
@endpush
@push('scripts')
<script>
    (function () {
        const radios = document.querySelectorAll('input[name="user_option"]');
        const existingField = document.getElementById('existingAccountField');
        const newFields = document.getElementById('newAccountFields');

        function toggleAccountMode() {
            const mode = document.querySelector('input[name="user_option"]:checked')?.value || 'existing';
            if (mode === 'new') {
                newFields.classList.remove('d-none');
                existingField.classList.add('d-none');
            } else {
                newFields.classList.add('d-none');
                existingField.classList.remove('d-none');
            }
        }

        radios.forEach((radio) => radio.addEventListener('change', toggleAccountMode));
        toggleAccountMode();
    })();
</script>
@endpush
@endsection
