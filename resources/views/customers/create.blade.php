@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Create Customer') }}</h5>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('customers.store') }}">
                    @csrf

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Personal Information') }}</h6>
                    <div class="row g-3 mb-4">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('Full Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $prefill['name'] ?? '') }}" required class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label">{{ __('Phone Number') }}</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address') }}</label>
                            <textarea name="address" id="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Coordinates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Latitude') }}</label>
                                <input type="text" name="latitude" id="latitude" value="{{ old('latitude') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" placeholder="-6.200000">
                                @error('latitude')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Longitude') }}</label>
                                <input type="text" name="longitude" id="longitude" value="{{ old('longitude') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" placeholder="106.816666">
                                @error('longitude')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">{{ __('Service Details') }}</h6>
                    <div class="row g-3 mb-4">
                        <!-- Package -->
                        <div class="col-md-6">
                            <label for="package" class="form-label">{{ __('Package') }}</label>
                            <input type="text" name="package" id="package" value="{{ old('package') }}" class="form-control @error('package') is-invalid @enderror">
                            @error('package')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- IP Address -->
                        <div class="col-md-6">
                            <label for="ip_address" class="form-label">{{ __('IP Address') }}</label>
                            <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $prefill['ip_address'] ?? '') }}" class="form-control @error('ip_address') is-invalid @enderror">
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- VLAN -->
                        <div class="col-md-6">
                            <label for="vlan" class="form-label">{{ __('VLAN') }}</label>
                            <input type="text" name="vlan" id="vlan" value="{{ old('vlan') }}" class="form-control @error('vlan') is-invalid @enderror">
                            @error('vlan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- OLT -->
                        <div class="col-md-6">
                            <label for="olt_id" class="form-label">{{ __('OLT Server') }}</label>
                            <select name="olt_id" id="olt_id" class="form-select @error('olt_id') is-invalid @enderror">
                                <option value="">-- {{ __('Select OLT') }} --</option>
                                @foreach($olts as $olt)
                                    <option value="{{ $olt->id }}" {{ old('olt_id') == $olt->id ? 'selected' : '' }}>
                                        {{ $olt->name }} ({{ $olt->host }})
                                    </option>
                                @endforeach
                            </select>
                            @error('olt_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ODP -->
                        <div class="col-md-6">
                            <label for="odp_id" class="form-label">{{ __('ODP Connection') }}</label>
                            <select name="odp_id" id="odp_id" class="form-select @error('odp_id') is-invalid @enderror">
                                <option value="">-- {{ __('Select ODP') }} --</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" {{ old('odp_id') == $odp->id ? 'selected' : '' }}>
                                        {{ $odp->name }} ({{ $odp->filled }}/{{ $odp->capacity }})
                                    </option>
                                @endforeach
                            </select>
                            @error('odp_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ONU Serial -->
                        <div class="col-md-6">
                            <label for="onu_serial" class="form-label">{{ __('ONU Serial (GenieACS)') }}</label>
                            <input type="text" list="onu_list" name="onu_serial" id="onu_serial" value="{{ old('onu_serial', $prefill['onu_serial'] ?? '') }}" class="form-control @error('onu_serial') is-invalid @enderror" placeholder="{{ __('Select or type serial...') }}">
                            <datalist id="onu_list">
                                @foreach($onuDevices as $device)
                                    <option value="{{ $device['serial'] }}">{{ $device['serial'] }} - {{ $device['model'] }}</option>
                                @endforeach
                            </datalist>
                            @error('onu_serial')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Device Model -->
                        <div class="col-md-6">
                            <label for="device_model" class="form-label">{{ __('Device Model') }}</label>
                            <input type="text" name="device_model" id="device_model" value="{{ old('device_model', $prefill['device_model'] ?? '') }}" class="form-control @error('device_model') is-invalid @enderror" readonly>
                            @error('device_model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Name -->
                        <div class="col-md-6">
                            <label for="ssid_name" class="form-label">{{ __('SSID Name') }}</label>
                            <input type="text" name="ssid_name" id="ssid_name" value="{{ old('ssid_name', $prefill['ssid_name'] ?? '') }}" class="form-control @error('ssid_name') is-invalid @enderror">
                            @error('ssid_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Password -->
                        <div class="col-md-6">
                            <label for="ssid_password" class="form-label">{{ __('SSID Password') }}</label>
                            <div class="input-group">
                                <input type="text" name="ssid_password" id="ssid_password" value="{{ old('ssid_password', $prefill['ssid_password'] ?? '') }}" class="form-control @error('ssid_password') is-invalid @enderror">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('ssid_password')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('ssid_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label for="status" class="form-label">{{ __('Status') }}</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="suspend" {{ old('status') == 'suspend' ? 'selected' : '' }}>{{ __('Suspend') }}</option>
                                <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('Save Customer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endpush
