@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Ubah Investor</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('investors.update', $investor) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="coordinator_id" class="form-label">{{ __('Coordinator') }}</label>
                            <select class="form-select @error('coordinator_id') is-invalid @enderror" id="coordinator_id" name="coordinator_id" required>
                                <option value="">{{ __('Select Coordinator') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id', $investor->coordinator_id) == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }}</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $investor->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $investor->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $investor->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Akun Login</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="border rounded p-2 d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="radio" name="user_option" value="existing" {{ old('user_option', 'existing') === 'existing' ? 'checked' : '' }}>
                                    <span>Pakai Akun yang Sudah Ada</span>
                                </label>
                                <label class="border rounded p-2 d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="radio" name="user_option" value="new" {{ old('user_option') === 'new' ? 'checked' : '' }}>
                                    <span>Buat Akun Baru</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="existingAccountField">
                            <label for="user_id" class="form-label">Akun Tersedia</label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                                <option value="">Pilih Akun</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $investor->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} @if($user->username) ({{ $user->username }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Perbarui Investor</button>
                            <a href="{{ route('investors.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
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
@endsection
