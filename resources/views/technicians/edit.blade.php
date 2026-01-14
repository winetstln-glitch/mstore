@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Edit Technician') }}: {{ $technician->name }}</h5>
                <a href="{{ route('technicians.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('technicians.update', $technician) }}" class="max-w-2xl mx-auto">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">{{ __('Name') }}</label>
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

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">{{ __('Phone') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $technician->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Telegram Chat ID -->
                    <div class="mb-3">
                        <label for="telegram_chat_id" class="form-label fw-bold">{{ __('Telegram Chat ID (Optional)') }}</label>
                        <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="{{ old('telegram_chat_id', $technician->telegram_chat_id) }}" class="form-control @error('telegram_chat_id') is-invalid @enderror">
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
                        <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', $technician->daily_salary ?? 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                        <div class="form-text">{{ __('Gaji per hari kehadiran.') }}</div>
                        @error('daily_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $technician->is_active) ? 'checked' : '' }} class="form-check-input">
                            <label for="is_active" class="form-check-label">{{ __('Active Account') }}</label>
                        </div>
                    </div>

                    <div class="border-top pt-4 mt-4">
                        <h6 class="fw-bold mb-3">{{ __('Change Password (Optional)') }}</h6>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('New Password') }}</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                            <div class="form-text">{{ __('Leave blank to keep current password.') }}</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirm New Password') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Update Technician') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
