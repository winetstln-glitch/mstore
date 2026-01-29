@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mapElement = document.getElementById('router-map');
        if (!mapElement) return;

        var defaultLat = -6.800142;
        var defaultLng = 105.93952;
        var initialZoom = 15;

        var lat = parseFloat(document.getElementById('latitude').value || defaultLat);
        var lng = parseFloat(document.getElementById('longitude').value || defaultLng);

        if (isNaN(lat)) lat = defaultLat;
        if (isNaN(lng)) lng = defaultLng;

        var map = L.map('router-map').setView([lat, lng], initialZoom);

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
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 border-top border-4 border-success">
            <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Tambah Router Baru') }}</h5>
                <a href="{{ route('routers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali') }}
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('routers.store') }}">
                    @csrf

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3">{{ __('Informasi Router') }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('Nama Router') }}</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="region_id" class="form-label">{{ __('Wilayah / Region') }}</label>
                            <select id="region_id" name="region_id" class="form-select @error('region_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih Wilayah (Opsional)') }}</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">{{ __('Lokasi Router') }}</label>
                            <input type="text" id="location" name="location" value="{{ old('location') }}" class="form-control @error('location') is-invalid @enderror" placeholder="{{ __('Contoh: Kantor Pusat / POP Gedung A') }}">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="host" class="form-label">{{ __('Host IP / Domain') }}</label>
                            <input type="text" id="host" name="host" value="{{ old('host') }}" class="form-control @error('host') is-invalid @enderror" required>
                            @error('host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label for="port" class="form-label">{{ __('Port (API)') }}</label>
                            <input type="number" id="port" name="port" value="{{ old('port', 8728) }}" class="form-control @error('port') is-invalid @enderror" required>
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="username" class="form-label">{{ __('Username') }}</label>
                            <input type="text" id="username" name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="form-check-input">
                                <label for="is_active" class="form-check-label">
                                    {{ __('Router Aktif') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-body-secondary text-uppercase small mb-3 border-top pt-3">{{ __('Lokasi & Koordinat') }}</h6>
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
                            <div id="router-map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd; overflow: hidden;"></div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">{{ __('Deskripsi') }}</label>
                            <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-4">
                        <a href="{{ route('routers.index') }}" class="btn btn-secondary">
                            {{ __('Batal') }}
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Router') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
