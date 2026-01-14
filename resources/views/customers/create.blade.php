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
                        <div class="col-12">
                            <label for="address" class="form-label">{{ __('Address') }}</label>
                            <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">{{ __('Latitude') }}</label>
                            <input type="text" name="latitude" id="latitude" value="{{ old('latitude') }}" class="form-control @error('latitude') is-invalid @enderror" placeholder="-6.200000">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="longitude" class="form-label">{{ __('Longitude') }}</label>
                            <input type="text" name="longitude" id="longitude" value="{{ old('longitude') }}" class="form-control @error('longitude') is-invalid @enderror" placeholder="106.816666">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-text text-muted mb-2">{{ __('Click on the map or drag the marker to update location.') }}</div>
                            <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;"></div>
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
                                <input type="password" name="ssid_password" id="ssid_password" value="{{ old('ssid_password', $prefill['ssid_password'] ?? '') }}" class="form-control @error('ssid_password') is-invalid @enderror">
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

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('scripts')
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

    document.addEventListener('DOMContentLoaded', function() {
        var defaultLat = -6.800142;
        var defaultLng = 105.93952;
        var initialZoom = 15;

        var lat = @json(old('latitude', null));
        var lng = @json(old('longitude', null));

        if (lat === null) lat = defaultLat;
        if (lng === null) lng = defaultLng;

        lat = parseFloat(lat);
        lng = parseFloat(lng);

        if (isNaN(lat)) lat = defaultLat;
        if (isNaN(lng)) lng = defaultLng;

        var map = L.map('map-picker').setView([lat, lng], initialZoom);

        var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        });

        var googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
            maxZoom: 22,
            subdomains: ['mt0','mt1','mt2','mt3'],
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
            var newLat = e.target.getLatLng().lat;
            var newLng = e.target.getLatLng().lng;
            document.getElementById('latitude').value = newLat.toFixed(8);
            document.getElementById('longitude').value = newLng.toFixed(8);
        });

        map.on('click', function(e) {
            var clickLat = e.latlng.lat;
            var clickLng = e.latlng.lng;

            document.getElementById('latitude').value = clickLat.toFixed(8);
            document.getElementById('longitude').value = clickLng.toFixed(8);

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {draggable: true}).addTo(map);
                marker.on('dragend', function(e) {
                    var dragLat = e.target.getLatLng().lat;
                    var dragLng = e.target.getLatLng().lng;
                    document.getElementById('latitude').value = dragLat.toFixed(8);
                    document.getElementById('longitude').value = dragLng.toFixed(8);
                });
            }
        });

        var odps = @json($odps ?? []);
        var odpSelect = document.getElementById('odp_id');

        odps.forEach(function(odp) {
            if (!odp.latitude || !odp.longitude) {
                return;
            }

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
                document.getElementById('latitude').value = odp.latitude.toFixed(8);
                document.getElementById('longitude').value = odp.longitude.toFixed(8);
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
    });
</script>
@endpush
