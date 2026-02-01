@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Add New ODC') }}</h5>
                <a href="{{ route('odcs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('odcs.store') }}" method="POST">
                    @csrf
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="region_id" class="form-label">{{ __('Region') }}</label>
                            <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id">
                                <option value="">{{ __('Select Region') }}</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="olt_id" class="form-label">{{ __('OLT') }}</label>
                            <select class="form-select @error('olt_id') is-invalid @enderror" id="olt_id" name="olt_id" required>
                                <option value="">{{ __('Select OLT') }}</option>
                                @foreach($olts as $olt)
                                    <option value="{{ $olt->id }}" {{ old('olt_id') == $olt->id ? 'selected' : '' }}>{{ $olt->name }}</option>
                                @endforeach
                            </select>
                            @error('olt_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pon_port" class="form-label">{{ __('PON Port') }}</label>
                            <input type="text" class="form-control @error('pon_port') is-invalid @enderror" id="pon_port" name="pon_port" value="{{ old('pon_port') }}" required placeholder="e.g. PON01">
                            @error('pon_port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="area" class="form-label">{{ __('Area') }}</label>
                            <input type="text" class="form-control @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area') }}" required placeholder="e.g. CIBADAK">
                            @error('area')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="color" class="form-label">{{ __('Tube / Fiber Color') }}</label>
                            <select class="form-select @error('color') is-invalid @enderror" id="color" name="color" required>
                                <option value="">{{ __('Select Color') }}</option>
                                <option value="BLUE" {{ old('color') == 'BLUE' ? 'selected' : '' }} data-code="B">Blue (Biru)</option>
                                <option value="ORANGE" {{ old('color') == 'ORANGE' ? 'selected' : '' }} data-code="O">Orange (Oranye)</option>
                                <option value="GREEN" {{ old('color') == 'GREEN' ? 'selected' : '' }} data-code="G">Green (Hijau)</option>
                                <option value="BROWN" {{ old('color') == 'BROWN' ? 'selected' : '' }} data-code="C">Brown (Coklat)</option>
                                <option value="SLATE" {{ old('color') == 'SLATE' ? 'selected' : '' }} data-code="S">Slate (Abu-abu)</option>
                                <option value="WHITE" {{ old('color') == 'WHITE' ? 'selected' : '' }} data-code="P">White (Putih)</option>
                                <option value="RED" {{ old('color') == 'RED' ? 'selected' : '' }} data-code="M">Red (Merah)</option>
                                <option value="BLACK" {{ old('color') == 'BLACK' ? 'selected' : '' }} data-code="H">Black (Hitam)</option>
                                <option value="YELLOW" {{ old('color') == 'YELLOW' ? 'selected' : '' }} data-code="K">Yellow (Kuning)</option>
                                <option value="VIOLET" {{ old('color') == 'VIOLET' ? 'selected' : '' }} data-code="U">Violet (Ungu)</option>
                                <option value="ROSE" {{ old('color') == 'ROSE' ? 'selected' : '' }} data-code="P">Rose (Pink)</option>
                                <option value="AQUA" {{ old('color') == 'AQUA' ? 'selected' : '' }} data-code="T">Aqua (Tosca)</option>
                            </select>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="cable_no" class="form-label">{{ __('Cable No') }}</label>
                            <input type="text" class="form-control @error('cable_no') is-invalid @enderror" id="cable_no" name="cable_no" value="{{ old('cable_no') }}" required placeholder="e.g. 01">
                            @error('cable_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">{{ __('Capacity (Ports)') }}</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 144) }}" min="0" required>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="name" class="form-label">{{ __('ODC Name (Auto-generated)') }}</label>
                            <input type="text" class="form-control bg-light" id="name" name="name" value="{{ old('name') }}" readonly placeholder="{{ __('Auto-generated: ODC-[PON]-[AREA]-[COLOR]-[CABLE]') }}">
                            <div class="form-text">{{ __('Format: ODC PON AREA WARNA KABEL') }}</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description (Optional)') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Location') }}</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}" placeholder="Latitude" required>
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}" placeholder="Longitude" required>
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-text text-muted">{{ __('Click on the map below to select location.') }}</div>
                    </div>

                    <div id="map-picker" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-3"></div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-outline-secondary">{{ __('Reset') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save ODC') }}
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
        // Name Auto-generation Preview
        const ponInput = document.getElementById('pon_port');
        const areaInput = document.getElementById('area');
        const colorInput = document.getElementById('color');
        const cableInput = document.getElementById('cable_no');
        const nameInput = document.getElementById('name');

        function updateNamePreview() {
            let ponVal = ponInput.value ? ponInput.value.replace(/[^0-9]/g, '') : '';
            let pon = ponVal ? ponVal.padStart(2, '0') : '[PON]';

            let areaVal = areaInput.value ? areaInput.value.replace(/\s+/g, '').toUpperCase() : '';
            let area = '[AREA]';
            if (areaVal) {
                if (areaVal.length > 3) {
                    let first = areaVal.charAt(0);
                    let last = areaVal.charAt(areaVal.length - 1);
                    let middle = areaVal.charAt(Math.floor(areaVal.length / 2));
                    area = first + middle + last;
                } else {
                    area = areaVal;
                }
            }

            // Get selected option's data-code for color abbreviation
            let selectedOption = colorInput.options[colorInput.selectedIndex];
            let colorCode = selectedOption && selectedOption.getAttribute('data-code') ? selectedOption.getAttribute('data-code') : '';
            let color = colorCode ? colorCode : '[WARNA]';

            let cableVal = cableInput.value ? cableInput.value.replace(/[^0-9]/g, '') : '';
            let cable = cableVal ? cableVal.padStart(2, '0') : '[KABEL]';
            
            nameInput.value = `ODC-${pon}-${area}-${color}-${cable}`;
        }

        ponInput.addEventListener('input', updateNamePreview);
        areaInput.addEventListener('input', updateNamePreview);
        colorInput.addEventListener('change', updateNamePreview); // Changed to change event for select
        cableInput.addEventListener('input', updateNamePreview);

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

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 15);
            });
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });
        
        // Load existing location if validation failed
        var oldLat = document.getElementById('latitude').value;
        var oldLng = document.getElementById('longitude').value;
        if(oldLat && oldLng) {
            var latlng = [parseFloat(oldLat), parseFloat(oldLng)];
            marker = L.marker(latlng).addTo(map);
            map.setView(latlng, 15);
        }
    });
</script>
@endsection
