@extends('layouts.app')

@section('content')
@push('styles')
<style>
    /* Mobile Optimization Styles */
    @media (max-width: 768px) {
        /* Map height adjustment */
        #map-picker {
            height: 250px; /* Reduce height on mobile */
        }

        /* Sticky Bottom Action Bar */
        .mobile-sticky-footer {
            position: sticky;
            bottom: 0;
            background-color: var(--bs-body-bg);
            padding: 1rem;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1020;
            margin-left: -1rem;
            margin-right: -1rem;
            margin-bottom: -1rem;
            border-top: 1px solid rgba(0,0,0,0.075);
        }

        /* Prevent iOS auto-zoom on inputs */
        input, select, textarea {
            font-size: 16px !important;
        }
        
        /* Header adjustments */
        .header-title h5 {
            font-size: 1.1rem;
        }
    }
    
    #map-picker {
        z-index: 1;
    }
</style>
@endpush

<div class="row justify-content-center">
    <div class="col-12 col-lg-10 px-3 px-lg-0">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <div class="header-title text-truncate" style="max-width: 100%;">
                        <h5 class="mb-0 fw-bold text-body-emphasis">
                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>
                            {{ __('Edit Customer') }}: <span class="text-truncate d-inline-block align-bottom" style="max-width: 150px; vertical-align: middle;">{{ $customer->name }}</span>
                        </h5>
                        <div class="small text-muted mt-1 d-md-none">ID: {{ $customer->id }}</div>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end">
                        @if($customer->onu_serial)
                        <a href="{{ route('customers.settings', $customer) }}" class="btn btn-info btn-sm text-white">
                            <i class="fa-solid fa-sliders"></i> <span class="d-none d-sm-inline">{{ __('Settings') }}</span>
                        </a>
                        @endif
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> <span class="d-none d-sm-inline">{{ __('Back') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="POST" action="{{ route('customers.update', $customer) }}" id="editCustomerForm">
                    @csrf
                    @method('PUT')

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Personal Information') }}</h6>
                    <div class="row g-3 mb-4">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label small text-muted fw-bold">{{ __('Full Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label small text-muted fw-bold">{{ __('Phone Number') }}</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label small text-muted fw-bold">{{ __('Address') }}</label>
                            <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label for="latitude" class="form-label small text-muted">{{ __('Latitude') }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-map-pin text-muted"></i></span>
                                <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $customer->latitude) }}" class="form-control @error('latitude') is-invalid @enderror" placeholder="-6.200000">
                            </div>
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="longitude" class="form-label small text-muted">{{ __('Longitude') }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-map-pin text-muted"></i></span>
                                <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $customer->longitude) }}" class="form-control @error('longitude') is-invalid @enderror" placeholder="106.816666">
                            </div>
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                              <div class="form-text text-muted">{{ __('Click on the map below to select location.') }}</div>
                            <div id="map-picker" class="border rounded"></div>
                        </div>
                    </div>
  <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-3"></div>
                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">{{ __('Service Details') }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="package_id" class="form-label small text-muted fw-bold">{{ __('Package') }}</label>
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
                            <label for="ip_address" class="form-label small text-muted fw-bold">{{ __('IP Address') }}</label>
                            <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $customer->ip_address) }}" class="form-control @error('ip_address') is-invalid @enderror" placeholder="192.168.x.x">
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- VLAN -->
                        <div class="col-6">
                            <label for="vlan" class="form-label small text-muted">VLAN</label>
                            <input type="number" name="vlan" id="vlan" value="{{ old('vlan', $customer->vlan) }}" class="form-control @error('vlan') is-invalid @enderror">
                            @error('vlan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- WAN MAC -->
                        <div class="col-6">
                            <label for="wan_mac" class="form-label small text-muted">{{ __('WAN MAC') }}</label>
                            <input type="text" name="wan_mac" id="wan_mac" value="{{ old('wan_mac', $customer->wan_mac) }}" class="form-control @error('wan_mac') is-invalid @enderror" placeholder="AA:BB:CC...">
                            @error('wan_mac')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ODP -->
                        <div class="col-md-6">
                            <label for="odp_id" class="form-label small text-muted fw-bold">{{ __('ODP Connection') }}</label>
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
                            <label for="htb_id" class="form-label small text-muted fw-bold">{{ __('HTB Connection') }}</label>
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
                            <label for="onu_serial" class="form-label small text-muted fw-bold">{{ __('ONU Serial') }}</label>
                            <input type="text" list="onu_list" name="onu_serial" id="onu_serial" value="{{ old('onu_serial', $customer->onu_serial) }}" class="form-control @error('onu_serial') is-invalid @enderror" placeholder="{{ __('Type or select...') }}">
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
                            <input type="text" name="device_model" id="device_model" value="{{ old('device_model', $customer->device_model) }}" class="form-control bg-light @error('device_model') is-invalid @enderror" readonly>
                            @error('device_model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Name -->
                        <div class="col-6">
                            <label for="ssid_name" class="form-label small text-muted">{{ __('SSID Name') }}</label>
                            <input type="text" name="ssid_name" id="ssid_name" value="{{ old('ssid_name', $customer->ssid_name) }}" class="form-control @error('ssid_name') is-invalid @enderror">
                            @error('ssid_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SSID Password -->
                        <div class="col-6">
                            <label for="ssid_password" class="form-label small text-muted">{{ __('SSID Password') }}</label>
                            <div class="input-group">
                                <input type="password" name="ssid_password" id="ssid_password" value="{{ old('ssid_password', $customer->ssid_password) }}" class="form-control @error('ssid_password') is-invalid @enderror">
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
                                <option value="active" {{ old('status', $customer->status) == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="suspend" {{ old('status', $customer->status) == 'suspend' ? 'selected' : '' }}>{{ __('Suspend') }}</option>
                                <option value="terminated" {{ old('status', $customer->status) == 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Sticky Footer -->
                    <div class="d-flex flex-column-reverse flex-md-row justify-content-end gap-2 border-top pt-4 mobile-sticky-footer">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary w-100 w-md-auto">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary w-100 w-md-auto px-4">{{ __('Update Customer') }}</button>
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

    document.addEventListener('DOMContentLoaded', function() {
        var lat = @json(old('latitude', $customer->latitude));
        var lng = @json(old('longitude', $customer->longitude));
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
        } catch (error) {
            console.error("Map Error:", error);
            if(mapContainer) mapContainer.innerHTML = '<div class="alert alert-danger">Failed to load map.</div>';
        }
    });
</script>
@endpush