@extends('layouts.app')

@section('content')
<div class="container investor-create-page py-3 py-md-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-9 px-0 px-md-3">
            <div class="card border-0 shadow-lg rounded-5 overflow-hidden investor-create-shell">
                <div class="investor-create-header p-4 p-md-5 pb-3">
                    <h4 class="fw-bold mb-1">Tambah Investor</h4>
                    <p class="mb-0 small investor-create-subtitle">Lengkapi formulir berikut untuk menambahkan data investor.</p>
                </div>

                <div class="card-body p-3 p-md-4 pt-3">
                    <form method="POST" action="{{ route('investors.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="coordinator_id" class="form-label fw-semibold">{{ __('Coordinator') }}</label>
                            <select class="form-select form-select-lg @error('coordinator_id') is-invalid @enderror" id="coordinator_id" name="coordinator_id" required>
                                <option value="">{{ __('Select Coordinator') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mode</label>
                            <div class="investor-mode-group">
                                <label class="investor-mode-option">
                                    <input class="form-check-input" type="radio" name="mode" id="mode_new" value="new" {{ old('mode', 'new') === 'new' ? 'checked' : '' }}>
                                    <span>Buat Investor Baru</span>
                                </label>
                                <label class="investor-mode-option">
                                    <input class="form-check-input" type="radio" name="mode" id="mode_select" value="select" {{ old('mode') === 'select' ? 'checked' : '' }}>
                                    <span>Pilih Investor yang Sudah Ada</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="existingInvestorWrapper">
                            <label for="source_investor_id" class="form-label fw-semibold">Investor Tersedia</label>
                            <select class="form-select form-select-lg @error('source_investor_id') is-invalid @enderror" id="source_investor_id" name="source_investor_id">
                                <option value="">Pilih Investor</option>
                                @foreach($existingInvestors as $investor)
                                    <option value="{{ $investor->id }}" {{ old('source_investor_id') == $investor->id ? 'selected' : '' }}>
                                        {{ $investor->name }} @if($investor->phone) ({{ $investor->phone }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('source_investor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Gunakan opsi ini untuk memakai investor lama ke koordinator lain.
                            </div>
                        </div>

                        <div id="newInvestorFields">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">{{ __('Name') }}</label>
                                <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold">Nomor Telepon</label>
                                <input type="text" class="form-control form-control-lg @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Akun Login</label>
                            <div class="investor-mode-group">
                                <label class="investor-mode-option">
                                    <input class="form-check-input" type="radio" name="user_option" id="user_option_existing" value="existing" {{ old('user_option', 'existing') === 'existing' ? 'checked' : '' }}>
                                    <span>Pakai Akun yang Sudah Ada</span>
                                </label>
                                <label class="investor-mode-option">
                                    <input class="form-check-input" type="radio" name="user_option" id="user_option_new" value="new" {{ old('user_option') === 'new' ? 'checked' : '' }}>
                                    <span>Buat Akun Baru</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="existingUserWrapper">
                            <label for="user_id" class="form-label fw-semibold">Akun Tersedia</label>
                            <select class="form-select form-select-lg @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                                <option value="">Pilih Akun</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} @if($user->username) ({{ $user->username }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="newUserFields">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">{{ __('Username') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
                                <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">{{ __('Password') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-lg" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="{{ route('investors.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary px-4">Simpan Investor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    .investor-create-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .investor-create-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.15);
    }

    .investor-create-header h4 {
        color: #0f172a;
    }

    .investor-create-subtitle {
        color: #475569;
    }

    .investor-mode-group {
        display: grid;
        gap: 0.75rem;
    }

    .investor-mode-option {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.85rem 1rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.9rem;
        background: var(--bs-body-bg);
    }

    .investor-mode-option .form-check-input {
        margin-top: 0;
    }

    @media (max-width: 767.98px) {
        .investor-create-page {
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }

        .investor-create-shell {
            border-radius: 1.25rem !important;
        }

        .investor-create-header {
            padding: 1rem 1rem 0.75rem !important;
        }

        .investor-create-header h4 {
            font-size: 1.1rem;
        }

        .investor-mode-option {
            padding: 0.75rem 0.85rem;
        }
    }

    [data-bs-theme="dark"] .investor-create-shell {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
    }

    [data-bs-theme="dark"] .investor-create-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .investor-create-header h4 {
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .investor-create-subtitle {
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .investor-mode-option {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }
</style>
@endpush
<script>
    (function () {
        function updateMode() {
            var mode = document.querySelector('input[name="mode"]:checked')?.value || 'new';
            var existingWrapper = document.getElementById('existingInvestorWrapper');
            var newFields = document.getElementById('newInvestorFields');

            if (mode === 'select') {
                existingWrapper.style.display = '';
                newFields.style.display = 'none';
            } else {
                existingWrapper.style.display = 'none';
                newFields.style.display = '';
            }
        }

        function updateUserOption() {
            var userOption = document.querySelector('input[name="user_option"]:checked')?.value || 'existing';
            var existingUserWrapper = document.getElementById('existingUserWrapper');
            var newUserFields = document.getElementById('newUserFields');

            if (userOption === 'new') {
                existingUserWrapper.style.display = 'none';
                newUserFields.style.display = '';
            } else {
                existingUserWrapper.style.display = '';
                newUserFields.style.display = 'none';
            }
        }

        document.getElementById('mode_new').addEventListener('change', updateMode);
        document.getElementById('mode_select').addEventListener('change', updateMode);
        document.getElementById('user_option_existing').addEventListener('change', updateUserOption);
        document.getElementById('user_option_new').addEventListener('change', updateUserOption);

        updateMode();
        updateUserOption();
    })();
</script>
@endsection
