@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Edit ODP') }}: {{ $odp->name }}</h5>
                <a href="{{ route('odps.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('odps.update', $odp) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('ODP Name') }}</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $odp->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">{{ __('Capacity (Ports)') }}</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', $odp->capacity) }}" min="1">
                            <div class="form-text text-muted">{{ __('Leave empty for unlimited capacity.') }}</div>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Location') }}</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $odp->latitude) }}" placeholder="Latitude" required>
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $odp->longitude) }}" placeholder="Longitude" required>
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-text text-muted">{{ __('Click on the map below to update location.') }}</div>
                    </div>

                    <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-3"></div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description (Optional)') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $odp->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Update ODP') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var lat = {{ $odp->latitude ?? -6.2088 }};
        var lng = {{ $odp->longitude ?? 106.8456 }};
        var zoom = 15;

        var map = L.map('map-picker').setView([lat, lng], zoom);

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
    });
</script>
@endsection
