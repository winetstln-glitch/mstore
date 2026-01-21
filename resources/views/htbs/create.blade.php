@extends('layouts.app')

@section('title', __('Add New HTB'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Add New HTB') }}</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('htbs.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">{{ __('Uplink Type') }} <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="uplink_type" id="uplink_odp" value="odp" {{ old('uplink_type', 'odp') == 'odp' ? 'checked' : '' }} onchange="toggleUplink()">
                                <label class="form-check-label" for="uplink_odp">
                                    {{ __('From ODP (Server)') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="uplink_type" id="uplink_htb" value="htb" {{ old('uplink_type') == 'htb' ? 'checked' : '' }} onchange="toggleUplink()">
                                <label class="form-check-label" for="uplink_htb">
                                    {{ __('From Parent HTB') }}
                                </label>
                            </div>
                        </div>
                        @error('uplink_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="odp_select_group">
                        <label for="odp_id" class="form-label">{{ __('Parent ODP') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('uplink_id') is-invalid @enderror" id="odp_id" name="uplink_id">
                            <option value="">{{ __('Select ODP') }}</option>
                            @foreach($odps as $odp)
                                <option value="{{ $odp->id }}" {{ old('uplink_type', 'odp') == 'odp' && old('uplink_id') == $odp->id ? 'selected' : '' }}>
                                    {{ $odp->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('uplink_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 d-none" id="htb_select_group">
                        <label for="parent_htb_id" class="form-label">{{ __('Parent HTB') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('uplink_id') is-invalid @enderror" id="parent_htb_id" name="uplink_id" disabled>
                            <option value="">{{ __('Select Parent HTB') }}</option>
                            @foreach($parentHtbs as $htb)
                                <option value="{{ $htb->id }}" {{ old('uplink_type') == 'htb' && old('uplink_id') == $htb->id ? 'selected' : '' }}>
                                    {{ $htb->name }} ({{ $htb->odp ? $htb->odp->name : 'No ODP' }})
                                </option>
                            @endforeach
                        </select>
                         @error('uplink_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('HTB Name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="{{ __('Leave blank to auto-generate') }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Location') }}</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}" placeholder="Latitude">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}" placeholder="Longitude">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-text text-muted">{{ __('Click on the map below to select location.') }}</div>
                    </div>

                    <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-3"></div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">{{ __('Capacity (Ports)') }}</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity') }}">
                            <div class="form-text">{{ __('Leave blank for unlimited.') }}</div>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">{{ __('Color') }}</label>
                            <input type="color" class="form-control form-control-color w-100 @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', '#007bff') }}">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('htbs.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save HTB') }}</button>
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
    function toggleUplink() {
        const uplinkType = document.querySelector('input[name="uplink_type"]:checked').value;
        const odpGroup = document.getElementById('odp_select_group');
        const htbGroup = document.getElementById('htb_select_group');
        const odpSelect = document.getElementById('odp_id');
        const htbSelect = document.getElementById('parent_htb_id');

        if (uplinkType === 'odp') {
            odpGroup.classList.remove('d-none');
            htbGroup.classList.add('d-none');
            odpSelect.disabled = false;
            htbSelect.disabled = true;
        } else {
            odpGroup.classList.add('d-none');
            htbGroup.classList.remove('d-none');
            odpSelect.disabled = true;
            htbSelect.disabled = false;
        }
    }

    // Initialize state on load
    document.addEventListener('DOMContentLoaded', function() {
        toggleUplink();

        // Map Picker Logic
        var defaultLat = -6.2088;
        var defaultLng = 106.8456;
        var zoom = 13;

        var map = L.map('map-picker').setView([defaultLat, defaultLng], zoom);

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

        var marker;
        
        // Check for existing values
        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');

        if (latInput.value && lngInput.value) {
            var lat = parseFloat(latInput.value);
            var lng = parseFloat(lngInput.value);
            marker = L.marker([lat, lng]).addTo(map);
            map.setView([lat, lng], 15);
        } else if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 15);
            });
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });
    });
</script>
@endpush
