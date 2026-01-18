@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">ODP Details: {{ $odp->name }}</h5>
                <a href="{{ route('odps.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small fw-bold">Basic Info</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Name</span>
                                <span class="fw-medium">{{ $odp->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Capacity</span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $odp->capacity ?? 'Unlimited' }} {{ $odp->capacity ? 'Ports' : '' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Filled</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $odp->filled }} Used</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Coordinates</span>
                                <span class="font-monospace small">{{ $odp->latitude }}, {{ $odp->longitude }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small fw-bold">Description</h6>
                        <p class="text-muted bg-light p-3 rounded small">
                            {{ $odp->description ?? 'No description available.' }}
                        </p>
                    </div>
                </div>

                <div id="map-view" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;"></div>
            </div>
            
            <div class="card-footer bg-white border-top-0 d-flex justify-content-end gap-2 py-3">
                @if(Auth::user()->hasPermission('map.manage'))
                <a href="{{ route('odps.edit', $odp) }}" class="btn btn-primary">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit ODP
                </a>
                @endif
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
        var lat = {{ $odp->latitude }};
        var lng = {{ $odp->longitude }};
        var zoom = 15;

        var map = L.map('map-view').setView([lat, lng], zoom);

        var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        });
        var dark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        });
        
        function setMapLayer(theme) {
            if (theme === 'dark') {
                map.removeLayer(osm);
                dark.addTo(map);
            } else {
                map.removeLayer(dark);
                osm.addTo(map);
            }
        }

        // Initial set
        var currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        setMapLayer(currentTheme);

        // Listen for theme changes
        window.addEventListener('themeChanged', function(e) {
            setMapLayer(e.detail.theme);
        });

        L.marker([lat, lng]).addTo(map)
            .bindPopup("<b>{{ $odp->name }}</b>")
            .openPopup();
    });
</script>
@endsection
