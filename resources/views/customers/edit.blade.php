@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Edit Customer') }}: {{ $customer->name }}</h5>
                <div>
                    @if($customer->onu_serial)
                    <a href="{{ route('customers.settings', $customer) }}" class="btn btn-info btn-sm text-white me-2">
                        <i class="fa-solid fa-sliders"></i> {{ __('Device Settings') }}
                    </a>
                    @endif
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('customers.update', $customer) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Personal Information') }}</h6>
                    <div class="row g-3 mb-4">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('Full Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label">{{ __('Phone Number') }}</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label">{{ __('Address') }}</label>
                            <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">{{ __('Latitude') }}</label>
                            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $customer->latitude) }}" class="form-control @error('latitude') is-invalid @enderror" placeholder="-6.200000">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="longitude" class="form-label">{{ __('Longitude') }}</label>
                            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $customer->longitude) }}" class="form-control @error('longitude') is-invalid @enderror" placeholder="106.816666">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-text text-muted mb-2">{{ __('Click on the map or drag the marker to update location.') }}</div>
                            <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;"></div>
                        </div>

                    <h6 class="fw-bold text-muted text-uppercase small mb-3 border-top pt-3">{{ __('Service Details') }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="package_id" class="form-label">{{ __('Package') }}</label>
                            <select name="package_id" id="package_id" class="form-select @error('package_id') is-invalid @enderror">
                                <option value="">{{ __('Select package') }}</option>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" {{ old('package_id', $customer->package_id) == $pkg->id ? 'selected' : '' }}>
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
                            <label for="ip_address" class="form-label">{{ __('IP Address') }}</label>
                            <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $customer->ip_address) }}" class="form-control @error('ip_address') is-invalid @enderror">
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- VLAN -->
                        <div class="col-md-6">
                            <label for="vlan" class="form-label">VLAN</label>
                            <input type="text" name="vlan" id="vlan" value="{{ old('vlan', $customer->vlan) }}" class="form-control @error('vlan') is-invalid @enderror">
                            @error('vlan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- WAN MAC -->
                        <div class="col-md-6">
                            <label for="wan_mac" class="form-label">{{ __('WAN MAC Address') }}</label>
                            <input type="text" name="wan_mac" id="wan_mac" value="{{ old('wan_mac', $customer->wan_mac) }}" class="form-control @error('wan_mac') is-invalid @enderror">
                            @error('wan_mac')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ODP -->
                        <div class="col-md-6">
                            <label for="odp_id" class="form-label">{{ __('ODP Connection') }}</label>
                            <select name="odp_id" id="odp_id" class="form-select @error('odp_id') is-invalid @enderror">
                                <option value="">-- {{ __('Select ODP') }} --</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" {{ old('odp_id', $customer->odp_id) == $odp->id ? 'selected' : '' }} {{ ($odp->capacity !== null && $odp->filled >= $odp->capacity && $customer->odp_id != $odp->id) ? 'disabled' : '' }}>
                                        {{ $odp->name }} ({{ $odp->filled }}/{{ $odp->capacity ?? '∞' }}){{ ($odp->capacity !== null && $odp->filled >= $odp->capacity) ? ' - Full' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('odp_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- HTB -->
                        <div class="col-md-6">
                            <label for="htb_id" class="form-label">{{ __('HTB Connection') }}</label>
                            <select name="htb_id" id="htb_id" class="form-select @error('htb_id') is-invalid @enderror">
                                <option value="">-- {{ __('Select HTB') }} --</option>
                                @foreach($htbs as $htb)
                                    <option value="{{ $htb->id }}" {{ old('htb_id', $customer->htb_id) == $htb->id ? 'selected' : '' }} {{ ($htb->id != $customer->htb_id && $htb->capacity !== null && $htb->filled >= $htb->capacity) ? 'disabled' : '' }}>
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
                            <label for="onu_serial" class="form-label">{{ __('ONU Serial (GenieACS)') }}</label>
                            <input type="text" list="onu_list" name="onu_serial" id="onu_serial" value="{{ old('onu_serial', $customer->onu_serial) }}" class="form-control @error('onu_serial') is-invalid @enderror" placeholder="{{ __('Select or type serial...') }}">
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
                            <input type="text" name="device_model" id="device_model" value="{{ old('device_model', $customer->device_model) }}" class="form-control @error('device_model') is-invalid @enderror" readonly>
                            @error('device_model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Name -->
                        <div class="col-md-6">
                            <label for="ssid_name" class="form-label">{{ __('SSID Name') }}</label>
                            <input type="text" name="ssid_name" id="ssid_name" value="{{ old('ssid_name', $customer->ssid_name) }}" class="form-control @error('ssid_name') is-invalid @enderror">
                            @error('ssid_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Password -->
                        <div class="col-md-6">
                            <label for="ssid_password" class="form-label">{{ __('SSID Password') }}</label>
                            <div class="input-group">
                                <input type="text" name="ssid_password" id="ssid_password" value="{{ old('ssid_password', $customer->ssid_password) }}" class="form-control @error('ssid_password') is-invalid @enderror">
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
                                <option value="active" {{ old('status', $customer->status) == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="suspend" {{ old('status', $customer->status) == 'suspend' ? 'selected' : '' }}>{{ __('Suspend') }}</option>
                                <option value="terminated" {{ old('status', $customer->status) == 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('Update Customer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    function toggleConnectionType() {
        const type = document.querySelector('input[name="connection_type"]:checked').value;
        const odpGroup = document.getElementById('odp_select_group');
        const htbGroup = document.getElementById('htb_select_group');
        const odpSelect = document.getElementById('odp_id');
        const htbSelect = document.getElementById('htb_id');

        if (type === 'odp') {
            odpGroup.classList.remove('d-none');
            htbGroup.classList.add('d-none');
            odpSelect.disabled = false;
            htbSelect.disabled = true;
            // Clear HTB selection if changing type (optional, but good for UX)
            // But for edit, we might want to preserve it if user toggles back and forth without saving
            // For now, let's just disable.
            if (htbSelect.value) {
                // htbSelect.value = ""; // Don't clear on edit, just disable
            }
        } else {
            odpGroup.classList.add('d-none');
            htbGroup.classList.remove('d-none');
            odpSelect.disabled = true;
            htbSelect.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleConnectionType();

        var lat = {{ $customer->latitude ?? -6.200000 }};
        var lng = {{ $customer->longitude ?? 106.816666 }};
        var zoom = 15;

        lat = parseFloat(lat);
        lng = parseFloat(lng);

        if (isNaN(lat)) lat = -6.200000;
        if (isNaN(lng)) lng = 106.816666;

        var mapContainer = document.getElementById('map-picker');
        if (!mapContainer) {
            console.error("Map container not found!");
            return;
        }

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

            // Fix map rendering issues in tabs/modals
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

            marker.on('dragend', function(e) {
                var lat = e.target.getLatLng().lat;
                var lng = e.target.getLatLng().lng;
                document.getElementById('latitude').value = lat.toFixed(8);
                document.getElementById('longitude').value = lng.toFixed(8);
            });

            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;

                document.getElementById('latitude').value = lat.toFixed(8);
                document.getElementById('longitude').value = lng.toFixed(8);

                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng, {draggable: true}).addTo(map);
                    marker.on('dragend', function(e) {
                        var lat = e.target.getLatLng().lat;
                        var lng = e.target.getLatLng().lng;
                        document.getElementById('latitude').value = lat.toFixed(8);
                        document.getElementById('longitude').value = lng.toFixed(8);
                    });
                }
            });
        } catch (error) {
            console.error("Error initializing map:", error);
            mapContainer.innerHTML = '<div class="alert alert-danger">Failed to load map. Please check console for details.</div>';
        }
    });

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
                    if (data.ip_address) document.getElementById('ip_address').value = data.ip_address;
                    if (data.vlan) document.getElementById('vlan').value = data.vlan;
                    if (data.wan_mac) document.getElementById('wan_mac').value = data.wan_mac;
                    if (data.device_model) document.getElementById('device_model').value = data.device_model;
                    if (data.ssid_name) document.getElementById('ssid_name').value = data.ssid_name;
                    if (data.ssid_password) document.getElementById('ssid_password').value = data.ssid_password;
                })
                .catch(error => console.log('GenieACS Auto-populate:', error));
        }
    });
</script>
@endpush
