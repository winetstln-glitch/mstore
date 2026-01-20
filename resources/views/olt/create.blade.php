@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mapElement = document.getElementById('olt-map');
        if (!mapElement) return;

        var defaultLat = -6.800142;
        var defaultLng = 105.93952;
        var initialZoom = 15;

        var lat = parseFloat(document.getElementById('latitude').value || defaultLat);
        var lng = parseFloat(document.getElementById('longitude').value || defaultLng);

        if (isNaN(lat)) lat = defaultLat;
        if (isNaN(lng)) lng = defaultLng;

        var map = L.map('olt-map').setView([lat, lng], initialZoom);

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

        function setInputs(latVal, lngVal) {
            document.getElementById('latitude').value = latVal.toFixed(8);
            document.getElementById('longitude').value = lngVal.toFixed(8);
        }

        marker.on('dragend', function(e) {
            var newLat = e.target.getLatLng().lat;
            var newLng = e.target.getLatLng().lng;
            setInputs(newLat, newLng);
        });

        map.on('click', function(e) {
            var clickLat = e.latlng.lat;
            var clickLng = e.latlng.lng;
            setInputs(clickLat, clickLng);
            marker.setLatLng(e.latlng);
        });

        var btnPick = document.getElementById('btnPickOnMap');
        if (btnPick) {
            btnPick.addEventListener('click', function() {
                map.invalidateSize();
                if (document.getElementById('latitude').value && document.getElementById('longitude').value) {
                    var latVal = parseFloat(document.getElementById('latitude').value);
                    var lngVal = parseFloat(document.getElementById('longitude').value);
                    if (!isNaN(latVal) && !isNaN(lngVal)) {
                        map.setView([latVal, lngVal], 16);
                        marker.setLatLng([latVal, lngVal]);
                    }
                }
            });
        }
    });
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Add New OLT') }}</h5>
                <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('olt.store') }}">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('OLT Name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="host" class="form-label">{{ __('Host / IP Address') }}</label>
                            <input type="text" name="host" id="host" value="{{ old('host') }}" class="form-control @error('host') is-invalid @enderror" required>
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="port" class="form-label">{{ __('Port') }}</label>
                            <input type="number" name="port" id="port" value="{{ old('port', 23) }}" class="form-control @error('port') is-invalid @enderror" required>
                            <div class="form-text">{{ __('Default: 23 (Telnet), 22 (SSH)') }}</div>
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="brand" class="form-label">{{ __('Brand') }}</label>
                            <select name="brand" id="brand" class="form-select @error('brand') is-invalid @enderror" required>
                                <option value="zte" {{ old('brand') == 'zte' ? 'selected' : '' }}>ZTE</option>
                                <option value="huawei" {{ old('brand') == 'huawei' ? 'selected' : '' }}>Huawei</option>
                                <option value="hsgq" {{ old('brand') == 'hsgq' ? 'selected' : '' }}>HSGQ</option>
                                <option value="cdata" {{ old('brand') == 'cdata' ? 'selected' : '' }}>C-Data</option>
                                <option value="vsol" {{ old('brand') == 'vsol' ? 'selected' : '' }}>VSOL</option>
                            </select>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="type" class="form-label">{{ __('Type') }}</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="epon" {{ old('type') == 'epon' ? 'selected' : '' }}>EPON</option>
                                <option value="gpon" {{ old('type') == 'gpon' ? 'selected' : '' }}>GPON</option>
                                <option value="xpon" {{ old('type') == 'xpon' ? 'selected' : '' }}>XPON</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">{{ __('Username') }}</label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="snmp_port" class="form-label">SNMP Port</label>
                            <input type="number" name="snmp_port" id="snmp_port" value="{{ old('snmp_port', 161) }}" class="form-control @error('snmp_port') is-invalid @enderror">
                            @error('snmp_port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="snmp_community" class="form-label">SNMP Community</label>
                            <input type="text" name="snmp_community" id="snmp_community" value="{{ old('snmp_community') }}" class="form-control @error('snmp_community') is-invalid @enderror">
                            @error('snmp_community')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 d-flex align-items-center pt-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                            @error('is_active')
                                <div class="text-danger small ms-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">{{ __('Location & Coordinates') }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">{{ __('Latitude') }}</label>
                            <input type="text" id="latitude" name="latitude" value="{{ old('latitude') }}" class="form-control @error('latitude') is-invalid @enderror" placeholder="-6.200000">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="longitude" class="form-label">{{ __('Longitude') }}</label>
                            <input type="text" id="longitude" name="longitude" value="{{ old('longitude') }}" class="form-control @error('longitude') is-invalid @enderror" placeholder="106.816666">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-text text-muted mb-2">{{ __('Click on the map or drag the marker to update location.') }}</div>
                            <div id="olt-map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd; overflow: hidden;"></div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <button type="button" onclick="testConnection()" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-plug me-1"></i> {{ __('Test Connection') }}
                        </button>
                        <button type="submit" class="btn btn-primary px-4">{{ __('Save OLT') }}</button>
                    </div>
                </form>

<script>
    function testConnection() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __('Testing...') }}';
        btn.disabled = true;

        const host = document.getElementById('host').value;
        const port = document.getElementById('port').value;
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const brand = document.getElementById('brand').value;

        if (!host || !port) {
            alert('{{ __('Please fill Host and Port fields.') }}');
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }

        fetch('{{ route('olt.test_connection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                host: host, 
                port: port,
                username: username,
                password: password,
                brand: brand
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('{{ __('Success!') }} ' + data.message);
            } else {
                alert('{{ __('Error!') }} ' + data.message);
            }
        })
        .catch(error => {
            alert('{{ __('Error!') }} ' + error);
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>
@endsection
