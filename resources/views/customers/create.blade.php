@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <!-- Changed to col-12 on mobile for full width usage -->
    <div class="col-12 col-lg-10 px-3 px-lg-0">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header  py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis fs-6 fs-md-5">{{ __('Create Customer') }}</h5>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> <span class="d-none d-sm-inline">{{ __('Back') }}</span>
                </a>
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="POST" action="{{ route('customers.store') }}" id="customerForm">
                    @csrf

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Personal Information') }}</h6>
                    <div class="row g-3 mb-4">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label small text-muted fw-bold">{{ __('Full Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $prefill['name'] ?? '') }}" required class="form-control @error('name') is-invalid @enderror" placeholder="{{ __('Enter full name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label small text-muted fw-bold">{{ __('Phone Number') }}</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0812...">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label small text-muted fw-bold">{{ __('Address') }}</label>
                            <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="{{ __('Enter full address') }}">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Link to User (Client Portal) -->
                        <div class="col-12">
                            <h6 class="fw-bold text-body-secondary text-uppercase small mt-3 mb-3 border-top pt-3">{{ __('Portal Account (Optional)') }}</h6>
                            <div class="row g-3">
                                <div class="col-md-12 mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="create_user" id="create_user" value="1" {{ old('create_user') ? 'checked' : '' }} onchange="togglePortalFields()">
                                        <label class="form-check-label fw-bold" for="create_user">{{ __('Create Portal Account for this Customer') }}</label>
                                    </div>
                                </div>

                                <div id="portal_fields" class="{{ old('create_user') ? '' : 'd-none' }}">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="username" class="form-label small text-muted fw-bold">{{ __('Portal Username') }}</label>
                                            <input type="text" name="username" id="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" placeholder="username">
                                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="email" class="form-label small text-muted fw-bold">{{ __('Portal Email') }}</label>
                                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="email@example.com">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="password" class="form-label small text-muted fw-bold">{{ __('Portal Password') }}</label>
                                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="min 8 chars">
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="password_confirmation" class="form-label small text-muted fw-bold">{{ __('Confirm Password') }}</label>
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="confirm password">
                                        </div>
                                    </div>
                                </div>

                                <div id="existing_user_group" class="{{ old('create_user') ? 'd-none' : '' }}">
                                    <div class="col-md-6">
                                        <label for="user_id" class="form-label small text-muted fw-bold">{{ __('Link to Existing User (Portal)') }}</label>
                                        <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                            <option value="">{{ __('-- Optional: Select Existing User --') }}</option>
                                            @foreach(($availableUsers ?? []) as $u)
                                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                                    {{ $u->name }} @if($u->username) ({{ $u->username }}) @endif @if($u->email) - {{ $u->email }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            function togglePortalFields() {
                                const createChecked = document.getElementById('create_user').checked;
                                document.getElementById('portal_fields').classList.toggle('d-none', !createChecked);
                                document.getElementById('existing_user_group').classList.toggle('d-none', createChecked);
                                
                                // Disable inputs when hidden to avoid validation issues if browser sends them
                                const portalInputs = document.querySelectorAll('#portal_fields input');
                                portalInputs.forEach(input => {
                                    input.disabled = !createChecked;
                                });
                                
                                const userSelect = document.querySelector('#existing_user_group select');
                                if (userSelect) userSelect.disabled = createChecked;
                            }
                            
                            // Initialize on load
                            document.addEventListener('DOMContentLoaded', function() {
                                togglePortalFields();
                            });
                        </script>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label for="latitude" class="form-label small text-muted">{{ __('Latitude') }}</label>
                            <div class="input-group">
                                <span class="input-group-text "><i class="fa-solid fa-map-pin text-muted"></i></span>
                                <input type="text" name="latitude" id="latitude" value="{{ old('latitude') }}" class="form-control @error('latitude') is-invalid @enderror" placeholder="-6.200000">
                            </div>
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="longitude" class="form-label small text-muted">{{ __('Longitude') }}</label>
                            <div class="input-group">
                                <span class="input-group-text "><i class="fa-solid fa-map-pin text-muted"></i></span>
                                <input type="text" name="longitude" id="longitude" value="{{ old('longitude') }}" class="form-control @error('longitude') is-invalid @enderror" placeholder="106.816666">
                            </div>
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-text text-muted mb-2 small">{{ __('Tap map to set location') }}</div>
                            <div id="map-picker" class="border rounded"></div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">{{ __('Service Details') }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="package_id" class="form-label small text-muted fw-bold">{{ __('Package') }}</label>
                            <select name="package_id" id="package_id" class="form-select @error('package_id') is-invalid @enderror">
                                <option value="">{{ __('Select package') }}</option>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" {{ old('package_id', $customer->package_id ?? '') == $pkg->id ? 'selected' : '' }}>
                                        {{ $pkg->name }} @if($pkg->price) - {{ number_format($pkg->price, 0, ',', '.') }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('package_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- IP Address -->
                        <div class="col-md-6">
                            <label for="ip_address" class="form-label small text-muted fw-bold">{{ __('IP Address') }}</label>
                            <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $prefill['ip_address'] ?? '') }}" class="form-control @error('ip_address') is-invalid @enderror" placeholder="192.168.x.x">
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- GenieACS Device ID -->
                        <div class="col-md-6">
                            <label for="genieacs_device_id" class="form-label small text-muted fw-bold">{{ __('GenieACS Device ID') }}</label>
                            <input type="text" name="genieacs_device_id" id="genieacs_device_id" value="{{ old('genieacs_device_id') }}" class="form-control @error('genieacs_device_id') is-invalid @enderror" placeholder="e.g. 64b8f9...">
                            @error('genieacs_device_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- VLAN -->
                        <div class="col-6">
                            <label for="vlan" class="form-label small text-muted">{{ __('VLAN') }}</label>
                            <input type="number" name="vlan" id="vlan" value="{{ old('vlan') }}" class="form-control @error('vlan') is-invalid @enderror">
                            @error('vlan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- WAN MAC -->
                        <div class="col-6">
                            <label for="wan_mac" class="form-label small text-muted">{{ __('WAN MAC') }}</label>
                            <input type="text" name="wan_mac" id="wan_mac" value="{{ old('wan_mac') }}" class="form-control @error('wan_mac') is-invalid @enderror" placeholder="AA:BB:CC...">
                            @error('wan_mac')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- PPPoE Username -->
                        <div class="col-6">
                            <label for="pppoe_user" class="form-label small text-muted fw-bold">{{ __('PPPoE User') }}</label>
                            <input type="text" name="pppoe_user" id="pppoe_user" value="{{ old('pppoe_user', $prefill['pppoe_user'] ?? '') }}" class="form-control @error('pppoe_user') is-invalid @enderror">
                            @error('pppoe_user')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- PPPoE Password -->
                        <div class="col-6">
                            <label for="pppoe_password" class="form-label small text-muted fw-bold">{{ __('PPPoE Pass') }}</label>
                            <input type="text" name="pppoe_password" id="pppoe_password" value="{{ old('pppoe_password') }}" class="form-control @error('pppoe_password') is-invalid @enderror">
                            @error('pppoe_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- OLT -->
                        <div class="col-md-6">
                            <label for="olt_id" class="form-label small text-muted">{{ __('OLT Server') }}</label>
                            <select name="olt_id" id="olt_id" class="form-select @error('olt_id') is-invalid @enderror">
                                <option value="">-- {{ __('Select OLT') }} --</option>
                                @foreach($olts as $olt)
                                    <option value="{{ $olt->id }}" {{ old('olt_id') == $olt->id ? 'selected' : '' }}>
                                        {{ $olt->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('olt_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Closure first (filter for ODC & ODP) -->
                        <div class="col-md-6">
                            <label for="closure_id" class="form-label small text-muted fw-bold">{{ __('Closure') }}</label>
                            <select id="closure_id" class="form-select">
                                <option value="">{{ __('Select Closure (optional)') }}</option>
                                @foreach(($closures ?? []) as $cl)
                                    <option value="{{ $cl->id }}" data-odc-id="{{ $cl->odc_id }}">{{ $cl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-bold">{{ __('ODC (auto from Closure)') }}</label>
                            <input type="text" id="odc_display" class="form-control " readonly>
                        </div>

                        <!-- Connection Type -->
                        <div class="col-12">
                            <label class="form-label d-block small text-muted fw-bold">{{ __('Connection Type') }}</label>
                            <div class="d-flex gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="connection_type" id="conn_odp" value="odp" {{ old('connection_type', 'odp') == 'odp' ? 'checked' : '' }} onchange="toggleConnectionType()">
                                    <label class="form-check-label" for="conn_odp">{{ __('Direct ODP') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="connection_type" id="conn_htb" value="htb" {{ old('connection_type') == 'htb' ? 'checked' : '' }} onchange="toggleConnectionType()">
                                    <label class="form-check-label" for="conn_htb">{{ __('Via HTB') }}</label>
                                </div>
                            </div>
                        </div>

                        <!-- ODP -->
                        <div class="col-md-6" id="odp_select_group">
                            <label for="odp_id" class="form-label small text-muted fw-bold">{{ __('ODP Connection') }}</label>
                            <select name="odp_id" id="odp_id" class="form-select @error('odp_id') is-invalid @enderror">
                                <option value="">-- {{ __('Select ODP') }} --</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" {{ old('odp_id', $customer->odp_id ?? '') == $odp->id ? 'selected' : '' }} {{ ($odp->capacity !== null && $odp->filled >= $odp->capacity && ($customer->odp_id ?? '') != $odp->id) ? 'disabled' : '' }}>
                                        {{ $odp->name }} ({{ $odp->filled }}/{{ $odp->capacity ?? '∞' }}){{ ($odp->capacity !== null && $odp->filled >= $odp->capacity) ? ' - Full' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('odp_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- HTB -->
                        <div class="col-md-6 d-none" id="htb_select_group">
                            <label for="htb_id" class="form-label small text-muted fw-bold">{{ __('HTB Connection') }}</label>
                            <select name="htb_id" id="htb_id" class="form-select @error('htb_id') is-invalid @enderror" disabled>
                                <option value="">-- {{ __('Select HTB') }} --</option>
                                @foreach($htbs as $htb)
                                    <option value="{{ $htb->id }}" {{ old('htb_id', $customer->htb_id ?? '') == $htb->id ? 'selected' : '' }} {{ (($htb->id ?? '') != ($customer->htb_id ?? '') && $htb->capacity !== null && $htb->filled >= $htb->capacity) ? 'disabled' : '' }}>
                                        {{ $htb->name }} {{ $htb->parent ? '(via ' . $htb->parent->name . ')' : '' }} ({{ $htb->filled }}/{{ $htb->capacity ?? '∞' }}){{ ($htb->capacity !== null && $htb->filled >= $htb->capacity) ? ' - Full' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('htb_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ONU Serial -->
                        <div class="col-md-6">
                            <label for="onu_serial" class="form-label small text-muted fw-bold">{{ __('ONU Serial') }}</label>
                            <input type="text" list="onu_list" name="onu_serial" id="onu_serial" value="{{ old('onu_serial', $prefill['onu_serial'] ?? '') }}" class="form-control @error('onu_serial') is-invalid @enderror" placeholder="{{ __('Type or select...') }}">
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
                            <label for="device_model" class="form-label small text-muted">{{ __('Device Model') }}</label>
                            <input type="text" name="device_model" id="device_model" value="{{ old('device_model', $prefill['device_model'] ?? '') }}" class="form-control  @error('device_model') is-invalid @enderror" readonly>
                            @error('device_model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Name -->
                        <div class="col-6">
                            <label for="ssid_name" class="form-label small text-muted">{{ __('SSID Name') }}</label>
                            <input type="text" name="ssid_name" id="ssid_name" value="{{ old('ssid_name', $prefill['ssid_name'] ?? '') }}" class="form-control @error('ssid_name') is-invalid @enderror">
                            @error('ssid_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Password -->
                        <div class="col-6">
                            <label for="ssid_password" class="form-label small text-muted">{{ __('SSID Password') }}</label>
                            <div class="input-group">
                                <input type="password" name="ssid_password" id="ssid_password" value="{{ old('ssid_password') }}" class="form-control @error('ssid_password') is-invalid @enderror">
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
                            <label for="status" class="form-label small text-muted fw-bold">{{ __('Status') }}</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="suspend" {{ old('status') == 'suspend' ? 'selected' : '' }}>{{ __('Suspend') }}</option>
                                <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Sticky Footer for Mobile UX -->
                    <div class="d-flex flex-column-reverse flex-md-row justify-content-end gap-2 border-top pt-4 mobile-sticky-footer">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary w-100 w-md-auto">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary w-100 w-md-auto px-4">{{ __('Save Customer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    function toggleConnectionType() {
        const type = document.querySelector('input[name="connection_type"]:checked').value;
        const odpGroup = document.getElementById('odp_select_group');
        const htbGroup = document.getElementById('htb_select_group');
        const odpSelect = document.getElementById('odp_id');
        const htbSelect = document.getElementById('htb_id');

        if (type === 'htb') {
            odpGroup.classList.add('d-none');
            htbGroup.classList.remove('d-none');
            odpSelect.disabled = true;
            htbSelect.disabled = false;
            odpSelect.value = "";
        } else {
            odpGroup.classList.remove('d-none');
            htbGroup.classList.add('d-none');
            odpSelect.disabled = false;
            htbSelect.disabled = true;
            htbSelect.value = "";
        }
    }

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

    function filterOdpByClosure(closureId) {
        const closureList = @json($closures ?? []);
        const odpList = @json($odps ?? []);
        const odpSelect = document.getElementById('odp_id');
        const odcDisplay = document.getElementById('odc_display');

        let selectedClosure = null;
        let selectedOdcId = null;
        let selectedOdcName = '';

        if (closureId) {
            selectedClosure = closureList.find(c => String(c.id) === String(closureId));
            selectedOdcId = selectedClosure ? selectedClosure.odc_id : null;
        }

        // Try to derive ODC name from available data
        if (selectedClosure && selectedClosure.odc) {
            selectedOdcName = selectedClosure.odc.name || '';
        }
        odcDisplay.value = selectedOdcName || (closureId ? ('ODC #' + (selectedOdcId ?? '')) : '');

        // Rebuild ODP options
        const currentValue = odpSelect.value;
        odpSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- {{ __('Select ODP') }} --';
        odpSelect.appendChild(placeholder);

        const list = selectedOdcId ? odpList.filter(o => String(o.odc_id) === String(selectedOdcId)) : odpList;
        list.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            const cap = (o.capacity !== null && o.capacity !== undefined) ? o.capacity : '∞';
            opt.textContent = `${o.name} (${o.filled}/${cap})${(o.capacity !== null && o.filled >= o.capacity) ? ' - Full' : ''}`;
            if (o.capacity !== null && o.filled >= o.capacity) opt.disabled = true;
            odpSelect.appendChild(opt);
        });

        // Try to preserve selection if still in filtered list
        const stillExists = Array.from(odpSelect.options).some(opt => opt.value === currentValue);
        if (stillExists) {
            odpSelect.value = currentValue;
        } else {
            odpSelect.value = '';
        }
    }

    // Auto-populate from GenieACS
    document.getElementById('onu_serial').addEventListener('change', function() {
        var serial = this.value;
        if (serial) {
            fetch('{{ route("customers.genie_device") }}?serial=' + encodeURIComponent(serial))
                .then(response => {
                    if (!response.ok) throw new Error('Device not found');
                    return response.json();
                })
                .then(data => {
                    const setFieldValue = (id, value) => {
                        const element = document.getElementById(id);
                        if (!element) return;
                        if (value === undefined || value === null) return;
                        const normalized = String(value).trim();
                        if (normalized === '') return;
                        element.value = normalized;
                    };

                    setFieldValue('onu_serial', data.onu_serial);
                    setFieldValue('genieacs_device_id', data.genieacs_device_id);
                    setFieldValue('name', data.name);
                    setFieldValue('ip_address', data.ip_address);
                    setFieldValue('vlan', data.vlan);
                    setFieldValue('wan_mac', data.wan_mac);
                    setFieldValue('device_model', data.device_model);
                    setFieldValue('pppoe_user', data.pppoe_user);
                    setFieldValue('pppoe_password', data.pppoe_password);
                    setFieldValue('ssid_name', data.ssid_name);
                    setFieldValue('ssid_password', data.ssid_password);
                })
                .catch(error => console.log('GenieACS Auto-populate:', error));
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var lat = @json(old('latitude'));
        var lng = @json(old('longitude'));
        var zoom = 15;

        // Handle null, empty string, or non-numeric values
        if (lat === null || lat === '') lat = -6.200000;
        if (lng === null || lng === '') lng = 106.816666;

        lat = parseFloat(lat);
        lng = parseFloat(lng);

        if (isNaN(lat)) lat = -6.200000;
        if (isNaN(lng)) lng = 106.816666;

        var mapContainer = document.getElementById('map-picker');
        if (!mapContainer) return;

        try {
            var map = L.map('map-picker').setView([lat, lng], zoom);

            var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            });

            var googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                maxZoom: 22,
                attribution: '&copy; Google Maps'
            });

            var darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            });

            var currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            if (currentTheme === 'dark') {
                darkLayer.addTo(map);
            } else {
                osm.addTo(map);
            }

            var baseMaps = {
                "Dark Mode": darkLayer,
                "Satellite (Google)": googleHybrid,
                "Street (OSM)": osm
            };
            L.control.layers(baseMaps).addTo(map);

            // Fix map rendering issues
            setTimeout(function() {
                map.invalidateSize();
            }, 500);

            window.addEventListener('themeChanged', function(e) {
                if (e.detail.theme === 'dark') {
                    if (map.hasLayer(osm)) map.removeLayer(osm);
                    if (map.hasLayer(googleHybrid)) map.removeLayer(googleHybrid);
                    if (!map.hasLayer(darkLayer)) darkLayer.addTo(map);
                } else {
                    if (map.hasLayer(darkLayer)) map.removeLayer(darkLayer);
                    if (!map.hasLayer(osm) && !map.hasLayer(googleHybrid)) osm.addTo(map);
                }
            });

            var marker = L.marker([lat, lng], {draggable: true}).addTo(map);

            function updateInputs(latlng) {
                document.getElementById('latitude').value = latlng.lat.toFixed(8);
                document.getElementById('longitude').value = latlng.lng.toFixed(8);
            }

            marker.on('dragend', function(e) {
                updateInputs(e.target.getLatLng());
            });

            map.on('click', function(e) {
                updateInputs(e.latlng);
                marker.setLatLng(e.latlng);
            });

            // ODP Markers
            var odps = @json($odps ?? []);
            var odpSelect = document.getElementById('odp_id');

            odps.forEach(function(odp) {
                if (!odp.latitude || !odp.longitude) return;

                var odpMarker = L.circleMarker([odp.latitude, odp.longitude], {
                    radius: 6,
                    color: '#0dcaf0',
                    fillColor: '#0dcaf0',
                    fillOpacity: 0.8
                }).addTo(map);

                var label = odp.name;
                if (typeof odp.filled !== 'undefined' && typeof odp.capacity !== 'undefined') {
                    label += ' (' + odp.filled + '/' + odp.capacity + ')';
                }
                odpMarker.bindPopup(label);

                odpMarker.on('click', function() {
                    updateInputs({lat: odp.latitude, lng: odp.longitude});
                    marker.setLatLng([odp.latitude, odp.longitude]);

                    if (odpSelect) {
                        for (var i = 0; i < odpSelect.options.length; i++) {
                            if (parseInt(odpSelect.options[i].value) === odp.id) {
                                odpSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    map.panTo([odp.latitude, odp.longitude]);
                });
            });
        } catch (error) {
            console.error("Map Error:", error);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const closureSelect = document.getElementById('closure_id');
        if (closureSelect) {
            closureSelect.addEventListener('change', function() {
                filterOdpByClosure(this.value);
            });
            // Initial build without filter
            filterOdpByClosure(closureSelect.value || '');
        }
    });
</script>
@endpush
