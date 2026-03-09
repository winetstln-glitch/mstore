@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
<div class="container-fluid wash-employee-create-page">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Employee</h1>
        <a href="{{ route('wash.employees.index') }}" class="btn btn-sm btn-secondary shadow-sm" title="Back">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i>
            <span class="d-none d-md-inline ms-1">{{ __('Back to List') }}</span>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Employee Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('wash.employees.store') }}" method="POST" id="createEmployeeForm">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
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
                    
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="user_id" class="form-label">Link to Account</label>
                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                        <option value="">{{ __('— Optional —') }}</option>
                        @foreach(($users ?? []) as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span class="d-none d-md-inline ms-1">Save Employee</span>
                </button>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow d-md-none" style="z-index: 1030;">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.employees.index') }}" class="btn btn-outline-secondary w-50">Back</a>
            <button type="submit" class="btn btn-primary w-50" form="createEmployeeForm">Save</button>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-employee-create-page .form-control,
    .wash-employee-create-page .form-select {
        min-height: 44px;
    }

    @media (max-width: 767.98px) {
        .wash-employee-create-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 5rem !important;
        }

        .wash-employee-create-page .h3 {
            font-size: 1.1rem;
            margin-bottom: 0.9rem !important;
        }

        .wash-employee-create-page .card-body {
            padding: 0.9rem;
        }
    }
</style>
@endpush
@endsection
