@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Create Coordinator') }}</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('coordinators.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('User Account') }}</label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_option" id="option_existing" value="existing" checked onchange="toggleUserOption()">
                                <label class="form-check-label" for="option_existing">
                                    {{ __('Select Existing User') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_option" id="option_new" value="new" onchange="toggleUserOption()">
                                <label class="form-check-label" for="option_new">
                                    {{ __('Create New User') }}
                                </label>
                            </div>
                        </div>

                        <!-- Existing User Select -->
                        <div id="existing_user_section">
                            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                                <option value="">{{ __('Select User') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">{{ __('Link this coordinator to an existing system user account.') }}</div>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- New User Form -->
                        <div id="new_user_section" style="display: none;">
                            <div class="card bg-light border-0 p-3">
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
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
                            </div>
                        </div>
                    </div>

                    <script>
                        function toggleUserOption() {
                            const isNew = document.getElementById('option_new').checked;
                            const existingSection = document.getElementById('existing_user_section');
                            const newSection = document.getElementById('new_user_section');
                            
                            if (isNew) {
                                existingSection.style.display = 'none';
                                newSection.style.display = 'block';
                                document.getElementById('user_id').value = ''; // Reset selection
                            } else {
                                existingSection.style.display = 'block';
                                newSection.style.display = 'none';
                            }
                        }
                        
                        // Run on load to handle validation errors state
                        document.addEventListener('DOMContentLoaded', function() {
                            if("{{ old('user_option') }}" === "new" || "{{ $errors->has('email') || $errors->has('password') }}") {
                                document.getElementById('option_new').checked = true;
                                toggleUserOption();
                            }
                        });
                    </script>

                    <div class="mb-3">
                        <label for="region_id" class="form-label">{{ __('Region') }}</label>
                        <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id" required>
                            <option value="">{{ __('Select Region') }}</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('region_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="router_id" class="form-label">{{ __('Router Server') }}</label>
                        <select class="form-select @error('router_id') is-invalid @enderror" id="router_id" name="router_id">
                            <option value="">{{ __('Select Router Server') }}</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>
                                    {{ $router->name }} ({{ $router->host }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">{{ __('Assign a specific router server to this coordinator (Optional).') }}</div>
                        @error('router_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">{{ __('Phone (Optional)') }}</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label">{{ __('Address (Optional)') }}</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('coordinators.index') }}" class="btn btn-light border">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('Create Coordinator') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
