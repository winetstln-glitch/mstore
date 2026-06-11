@extends('layouts.app')

@section('content')
@php
    $isOut = ($todayAttendance && !$todayAttendance->clock_out);
    $formRoute = $isOut ? route('attendance.update', $todayAttendance->id) : route('attendance.store');
@endphp
<style>
    /* Custom Styling for Modern UI */
    :root {
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        --danger-gradient: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        /* Default Light Colors */
        --att-bg: #f8f9fa;
        --att-card: #ffffff;
        --att-text: #2d3436;
        --att-muted: #636e72;
        --att-border: rgba(0,0,0,0.05);
    }

    [data-bs-theme="dark"] {
        --att-bg: #050816;
        --att-card: #0f172a;
        --att-text: #e6f1ff;
        --att-muted: #86a4c7;
        --att-border: rgba(0, 229, 255, 0.15);
    }

    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .attendance-shell { background: var(--att-bg); min-height: 100vh; }
    
    .main-attendance-card {
        background: var(--att-card);
        border: none;
        border-radius: 30px !important;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    .attendance-header {
        background: var(--primary-gradient);
        color: white;
        padding: 30px 25px 80px 25px !important;
        position: relative;
    }
    
    .clock-panel {
        background: var(--att-card);
        color: var(--att-text);
        border: 1px solid var(--att-border);
        border-radius: 24px !important;
        padding: 25px !important;
        margin-top: -65px;
        position: relative;
        z-index: 10;
        box-shadow: var(--card-shadow);
        margin-left: 15px;
        margin-right: 15px;
    }

    .clock-time { color: #4e73df; letter-spacing: -1px; font-weight: 800; }
    
    /* Fingerprint Animation */
    .fingerprint-container {
        position: relative;
        width: 130px;
        height: 130px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fingerprint-ring {
        position: absolute;
        border-radius: 50%;
        border: 2px solid #4e73df;
        opacity: 0;
        transition: all 0.4s ease;
    }

    .fingerprint-ready .ring-1 {
        width: 105px;
        height: 105px;
        opacity: 0.3;
        animation: pulse-ring 2s infinite;
    }
    .fingerprint-ready .ring-2 {
        width: 125px;
        height: 125px;
        opacity: 0.15;
        animation: pulse-ring 2s infinite 0.5s;
    }

    @keyframes pulse-ring {
        0% { transform: scale(0.95); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.2; }
        100% { transform: scale(0.95); opacity: 0.5; }
    }

    .fingerprint-main-btn {
        position: relative;
        z-index: 2;
        width: 85px;
        height: 85px;
        border-radius: 50%;
        border: 4px solid #dee2e6;
        background: #f1f1f1;
        color: #adb5bd;
        font-size: 2.3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .fingerprint-container.is-pending .fingerprint-main-btn {
        background: #f1f1f1;
        border-color: #dee2e6;
        color: #adb5bd;
        box-shadow: none;
        cursor: not-allowed;
    }

    .fingerprint-ready .fingerprint-main-btn {
        background: var(--primary-gradient);
        border-color: #ffffff;
        color: white;
        box-shadow: 0 0 25px rgba(78, 115, 223, 0.5);
        cursor: pointer;
    }

    .fingerprint-ready.is-allowed .fingerprint-main-btn {
        background: linear-gradient(135deg, #16a34a 0%, #059669 100%);
        box-shadow: 0 0 25px rgba(5, 150, 105, 0.45);
    }

    .fingerprint-ready.is-blocked .fingerprint-main-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 0 25px rgba(220, 38, 38, 0.4);
    }

    .fingerprint-container.is-blocked .fingerprint-ring {
        border-color: #dc2626;
    }

    .fingerprint-container.is-allowed .fingerprint-ring {
        border-color: #059669;
    }

    .fingerprint-ready.is-out .fingerprint-main-btn {
        background: var(--danger-gradient);
        box-shadow: 0 0 25px rgba(231, 74, 59, 0.5);
    }
    
    .is-out .fingerprint-ring {
        border-color: #e74a3b;
    }

    .fingerprint-main-btn::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(255, 255, 255, 0.6);
        box-shadow: 0 0 10px #fff;
        display: none;
        animation: scan-move 2s linear infinite;
    }

    .fingerprint-ready .fingerprint-main-btn::after { display: block; }

    @keyframes scan-move {
        0% { top: 20%; opacity: 0; }
        50% { top: 50%; opacity: 1; }
        100% { top: 80%; opacity: 0; }
    }
    
    .modern-camera-box {
        width: 100%;
        max-width: 260px;
        height: 150px;
        border: 2px dashed #dee2e6;
        border-radius: 20px;
        overflow: hidden;
        background: var(--att-bg);
        display: inline-block;
        position: relative;
    }
    .modern-camera-box.has-image { border-style: solid; border-color: #4e73df; }
    .modern-preview-img { width: 100%; height: 100%; object-fit: cover; display: none; }
    .has-image .modern-preview-img { display: block; }
    
    .status-info-card { 
        border-radius: 18px !important;
        padding: 12px !important;
        border: none;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .status-info-masuk { background: #f0fdf4; color: #166534; }
    .status-info-izin { background: #fffbeb; color: #92400e; }
    .status-info-sakit { background: #fef2f2; color: #991b1b; }
    
    [data-bs-theme="dark"] .status-info-masuk { background: rgba(22, 101, 52, 0.2); color: #4ade80; }
    [data-bs-theme="dark"] .status-info-izin { background: rgba(146, 64, 14, 0.2); color: #fbbf24; }
    [data-bs-theme="dark"] .status-info-sakit { background: rgba(153, 27, 27, 0.2); color: #f87171; }

    .history-card {
        background: var(--att-card);
        border-radius: 20px !important;
        border: 1px solid var(--att-border);
    }
    .history-item { border-bottom: 1px solid var(--att-border); padding: 12px 0; }
    .history-item:last-child { border-bottom: none; }

    .clock-location-status.is-detected { color: #059669; font-weight: 700; }
    .clock-location-status.is-error { color: #dc2626; font-weight: 700; }
    .clock-location-status.is-warning { color: #d97706; font-weight: 700; }
    .distance-card {
        background: var(--att-bg);
        border: 1px solid var(--att-border);
        border-radius: 18px;
        padding: 14px 16px;
    }
    .distance-value { color: var(--att-text); font-weight: 800; }
    .distance-note { color: var(--att-muted); font-size: 0.85rem; }
    .distance-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .distance-badge.is-inside { background: rgba(5, 150, 105, 0.15); color: #059669; }
    .distance-badge.is-outside { background: rgba(220, 38, 38, 0.15); color: #dc2626; }
    .distance-badge.is-pending { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
    .attendance-map-card {
        background: var(--att-bg);
        border: 1px solid var(--att-border);
        border-radius: 18px;
        padding: 12px;
    }
    .attendance-mini-map {
        width: 100%;
        height: 220px;
        border-radius: 14px;
        overflow: hidden;
        background: rgba(148, 163, 184, 0.12);
    }
    .attendance-map-note {
        color: var(--att-muted);
        font-size: 0.8rem;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 px-0 px-md-3">
        <!-- Panduan Penggunaan -->
        <div class="alert alert-info py-2 mb-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            <strong>Panduan Penggunaan:</strong>
            <ul class="mb-0 mt-2">
                <li>Izinkan akses lokasi di browser Anda agar sistem dapat mendeteksi keberadaan Anda di kantor.</li>
                <li>Tombol absen akan aktif otomatis setelah lokasi Anda terdeteksi.</li>
                <li>{{ $attendancePhotoRequired ? __('Anda wajib mengambil foto selfie sebagai bukti kehadiran.') : __('Anda dapat mengambil foto selfie sebagai bukti kehadiran (opsional).') }}</li>
                <li>Setelah absen masuk, Anda dapat melakukan absen pulang pada waktu yang ditentukan.</li>
                <li>Riwayat absensi Anda bulan ini akan ditampilkan di bagian bawah.</li>
            </ul>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-2 mb-4">
            <button type="button" class="btn btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#createLeaveModal">
                <i class="fa-solid fa-file-lines me-2"></i>
                Ajukan Cuti/Izin
            </button>
        </div>

        <div class="card main-attendance-card mb-4">
            <!-- Header Section -->
            <div class="attendance-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small mb-1 opacity-75">{{ __('Selamat Datang,') }}</p>
                        <h4 class="fw-bold mb-0 text-white">{{ Auth::user()->name }}</h4>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-circle p-2">
                        <i class="fa-solid fa-user-check text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Clock Panel -->
            <div class="clock-panel text-center">
                <p class="text-uppercase small fw-bold mb-1 text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h1 class="display-5 mb-2 clock-time" id="clock">00:00:00</h1>
                
                <div class="d-flex align-items-center justify-content-center small mb-3">
                    <i class="fa-solid fa-location-dot text-danger me-2"></i>
                    <span id="location-status" class="clock-location-status is-loading text-muted">{{ __('Mencari lokasi...') }}</span>
                </div>

                <div class="distance-card mb-3 text-start">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <div class="small text-uppercase fw-bold text-muted">{{ __('Jarak ke Kantor') }}</div>
                            <div class="distance-value" id="distance-value">{{ __('Belum terdeteksi') }}</div>
                        </div>
                        <span class="distance-badge is-pending" id="distance-badge">
                            <i class="fa-solid fa-wave-square"></i>{{ __('Menunggu GPS') }}
                        </span>
                    </div>
                    <div class="distance-note" id="distance-note">
                        {{ __('Radius maksimal: :radius meter', ['radius' => number_format((float) ($attendanceRadius ?? 0), 0, ',', '.')]) }}
                    </div>
                </div>

                <div class="attendance-map-card mb-3 text-start">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div class="small text-uppercase fw-bold text-muted">{{ __('Peta Lokasi') }}</div>
                        <div class="attendance-map-note" id="map-status">{{ __('Menunggu GPS...') }}</div>
                    </div>
                    <div id="attendance-mini-map" class="attendance-mini-map"></div>
                </div>

                <!-- Info Jam Masuk/Keluar (Dinamis) -->
                @if($todayAttendance)
                    <div class="row g-2 mb-3 px-2">
                        <div class="col-6">
                            <div class="p-2 rounded-4 bg-primary-subtle border border-primary-subtle">
                                <p class="xx-small text-uppercase fw-bold text-primary mb-0" style="font-size: 0.6rem;">Jam Masuk</p>
                                <p class="small fw-bold text-dark mb-0">{{ $todayAttendance->clock_in?->format('H:i') ?? '--:--' }}</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-4 {{ $todayAttendance->clock_out ? 'bg-info-subtle border-info-subtle' : 'bg-light border-light' }}">
                                <p class="xx-small text-uppercase fw-bold {{ $todayAttendance->clock_out ? 'text-info' : 'text-muted' }} mb-0" style="font-size: 0.6rem;">Jam Pulang</p>
                                <p class="small fw-bold {{ $todayAttendance->clock_out ? 'text-dark' : 'text-muted' }} mb-0">{{ $todayAttendance->clock_out?->format('H:i') ?? '--:--' }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-column align-items-center justify-content-center">
                    @if(!($todayAttendance && $todayAttendance->clock_out))
                        <div class="fingerprint-container {{ $isOut ? 'is-out' : '' }}" id="fingerprintContainer">
                            <div class="fingerprint-ring ring-1"></div>
                            <div class="fingerprint-ring ring-2"></div>
                            <button type="submit" form="attendanceForm" id="submitBtn" class="fingerprint-main-btn" disabled>
                                <i class="fa-solid fa-fingerprint"></i>
                            </button>
                        </div>
                        
                        <button type="button" id="retryLocationBtn" class="btn btn-sm btn-light rounded-pill px-3 mt-2 text-muted border">
                            <i class="fa-solid fa-arrows-rotate me-1"></i>{{ __('Refresh Lokasi') }}
                        </button>

                        <div class="mt-3">
                            <h6 class="fw-bold mb-1 text-uppercase text-dark">{{ $isOut ? __('Absen Pulang') : __('Absen Masuk') }}</h6>
                            <p class="text-muted small mb-0 px-2" id="instruction-text">
                                {{ __('Tombol akan aktif otomatis saat lokasi Anda ditemukan.') }}
                            </p>
                        </div>
                    @else
                        <div class="py-3">
                            <div class="h1 text-success mb-2"><i class="fa-solid fa-circle-check"></i></div>
                            <h6 class="fw-bold text-dark">{{ __('Presensi Selesai') }}</h6>
                            <p class="text-muted small mb-0">{{ __('Sampai jumpa besok!') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Body Content -->
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger rounded-4 border-0 small mb-4">
                        <ul class="mb-0 ps-3 fw-bold">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Stats -->
                <div class="row g-2 mb-4">
                    <div class="col-4">
                        <div class="status-info-card status-info-masuk text-center">
                            <div class="small fw-bold opacity-75">{{ __('Masuk') }}</div>
                            <div class="h5 mb-0 fw-bold">{{ $attendanceSummary['masuk'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-info-card status-info-izin text-center">
                            <div class="small fw-bold opacity-75">{{ __('Izin') }}</div>
                            <div class="h5 mb-0 fw-bold">{{ $attendanceSummary['izin'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-info-card status-info-sakit text-center">
                            <div class="small fw-bold opacity-75">{{ __('Sakit') }}</div>
                            <div class="h5 mb-0 fw-bold">{{ $attendanceSummary['sakit'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Shift -->
                <div class="alert alert-primary rounded-4 border-0 py-3 px-3 d-flex justify-content-between align-items-center mb-4">
                    <div class="small">
                        <div class="opacity-75 fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">{{ __('Jadwal Shift Hari Ini') }}</div>
                        <b class="fs-6">{{ $shiftInfo['shift_label'] ?? 'Reguler' }}</b>
                    </div>
                    <div class="text-end">
                        <div class="h5 mb-0 fw-bold text-primary">{{ $shiftInfo['shift_start'] ?? '--:--' }} - {{ $shiftInfo['shift_end'] ?? '--:--' }}</div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle xx-small px-2" style="font-size: 0.6rem;">WIB</span>
                    </div>
                </div>

                @if(!($todayAttendance && $todayAttendance->clock_out))
                    <form action="{{ $formRoute }}" method="POST" enctype="multipart/form-data" id="attendanceForm">
                        @csrf
                        @if($isOut) @method('PUT') @endif
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <input type="hidden" name="device_fingerprint" id="deviceFingerprint">

                        <div class="text-center mb-4">
                            <label class="modern-camera-box shadow-sm" id="upload-area">
                                <div id="upload-placeholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                    <i class="fa-solid fa-camera fs-2 text-muted mb-1"></i>
                                    <span class="text-muted x-small fw-bold">
                                        @if($faceVerificationEnabled == '1')
                                            {{ __('Verifikasi Wajah') }}
                                        @else
                                            {{ $attendancePhotoRequired ? __('Selfie (Wajib)') : __('Selfie (Opsional)') }}
                                        @endif
                                    </span>
                                </div>
                                <img id="image-preview" class="modern-preview-img" src="#">
                                <input type="file" name="photo" id="photo" accept="image/*" capture="user" class="d-none">
                            </label>
                        </div>
                    </form>
                @endif

                <!-- History -->
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <h6 class="fw-bold mb-0 text-dark text-uppercase small">{{ __('Riwayat') }}</h6>
                        <span class="badge bg-primary rounded-pill">{{ $monthAttendances->count() }}</span>
                    </div>
                    <div class="card history-card p-3">
                        @forelse($monthAttendances->take(5) as $history)
                            <div class="history-item d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <div class="fw-bold text-dark">{{ ($history->work_date ?? $history->clock_in)?->translatedFormat('d M Y') }}</div>
                                    <div class="text-muted x-small">Hadir: {{ $history->clock_in?->format('H:i') ?? '--:--' }} @if($history->clock_out) - {{ $history->clock_out->format('H:i') }} @endif</div>
                                </div>
                                <span class="badge badge-status small {{ match($history->status) {'present'=>'bg-success-subtle text-success','late'=>'bg-warning-subtle text-warning','permit','leave'=>'bg-info-subtle text-info','sick'=>'bg-danger-subtle text-danger',default=>'bg-secondary-subtle text-secondary'} }}">
                                    {{ __($history->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-3 text-muted small">Belum ada riwayat</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js/weights';
    const faceVerificationEnabled = {{ $faceVerificationEnabled == '1' ? 'true' : 'false' }};
    const attendanceOfficeLat = {{ json_encode((float) ($attendanceOfficeLat ?? 0)) }};
    const attendanceOfficeLng = {{ json_encode((float) ($attendanceOfficeLng ?? 0)) }};
    const attendanceRadius = {{ json_encode((float) ($attendanceRadius ?? 0)) }};
    
    const submitBtn = document.getElementById('submitBtn');
    const instructionText = document.getElementById('instruction-text');
    const fingerprintContainer = document.getElementById('fingerprintContainer');
    const photoInput = document.getElementById('photo');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const locationStatus = document.getElementById('location-status');
    const distanceValue = document.getElementById('distance-value');
    const distanceBadge = document.getElementById('distance-badge');
    const distanceNote = document.getElementById('distance-note');
    const mapStatus = document.getElementById('map-status');
    const mapElement = document.getElementById('attendance-mini-map');
    let hasValidGpsLocation = false;
    let isWithinAttendanceRadius = false;
    let attendanceSettingReady = Boolean(attendanceOfficeLat && attendanceOfficeLng && attendanceRadius);
    let attendanceMap = null;
    let officeMarker = null;
    let userMarker = null;
    let radiusCircle = null;

    function formatMeters(meters) {
        if (meters < 1000) {
            return `${Math.round(meters)} meter`;
        }

        return `${(meters / 1000).toFixed(2).replace('.', ',')} km`;
    }

    function calculateDistanceMeters(lat1, lon1, lat2, lon2) {
        const toRadians = (degrees) => degrees * (Math.PI / 180);
        const earthRadius = 6371000;
        const dLat = toRadians(lat2 - lat1);
        const dLon = toRadians(lon2 - lon1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadius * c;
    }

    function setDistanceBadge(state, iconClass, text) {
        distanceBadge.className = `distance-badge ${state}`;
        distanceBadge.innerHTML = `<i class="${iconClass}"></i>${text}`;
    }

    function ensureAttendanceMap() {
        if (!window.L || attendanceMap || !mapElement) {
            return;
        }

        const initialLat = attendanceOfficeLat || -6.2;
        const initialLng = attendanceOfficeLng || 106.816666;
        attendanceMap = L.map(mapElement, {
            zoomControl: false,
            attributionControl: true,
        }).setView([initialLat, initialLng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(attendanceMap);

        if (attendanceOfficeLat && attendanceOfficeLng) {
            officeMarker = L.marker([attendanceOfficeLat, attendanceOfficeLng]).addTo(attendanceMap)
                .bindPopup('Lokasi kantor');

            if (attendanceRadius) {
                radiusCircle = L.circle([attendanceOfficeLat, attendanceOfficeLng], {
                    radius: attendanceRadius,
                    color: '#2563eb',
                    fillColor: '#60a5fa',
                    fillOpacity: 0.12,
                    weight: 1.5,
                }).addTo(attendanceMap);
            }
        }
    }

    function updateAttendanceMap(lat, lng) {
        ensureAttendanceMap();

        if (!attendanceMap) {
            return;
        }

        if (userMarker) {
            userMarker.setLatLng([lat, lng]);
        } else {
            userMarker = L.marker([lat, lng]).addTo(attendanceMap).bindPopup('Posisi Anda');
        }

        const bounds = [];
        if (attendanceOfficeLat && attendanceOfficeLng) {
            bounds.push([attendanceOfficeLat, attendanceOfficeLng]);
        }
        bounds.push([lat, lng]);

        if (bounds.length > 1) {
            attendanceMap.fitBounds(bounds, { padding: [30, 30] });
        } else {
            attendanceMap.setView([lat, lng], 17);
        }

        setTimeout(function () {
            attendanceMap.invalidateSize();
        }, 150);
    }

    function updateFingerprintState() {
        fingerprintContainer.classList.remove('is-pending', 'is-allowed', 'is-blocked', 'fingerprint-ready');

        if (!hasValidGpsLocation) {
            fingerprintContainer.classList.add('is-pending');
            return;
        }

        fingerprintContainer.classList.add('fingerprint-ready');

        if (!attendanceSettingReady || isWithinAttendanceRadius) {
            fingerprintContainer.classList.add('is-allowed');
            return;
        }

        fingerprintContainer.classList.add('is-blocked');
    }

    function updateDistanceInfo(lat, lng, accuracy) {
        attendanceSettingReady = Boolean(attendanceOfficeLat && attendanceOfficeLng && attendanceRadius);

        if (!attendanceSettingReady) {
            isWithinAttendanceRadius = false;
            distanceValue.textContent = 'Koordinat kantor belum diatur';
            distanceNote.textContent = 'Admin perlu mengisi latitude, longitude, dan radius absen di setting.';
            setDistanceBadge('is-outside', 'fa-solid fa-triangle-exclamation', 'Setting belum lengkap');
            mapStatus.textContent = 'Koordinat kantor belum diatur';
            updateFingerprintState();
            return;
        }

        const distance = calculateDistanceMeters(lat, lng, attendanceOfficeLat, attendanceOfficeLng);
        isWithinAttendanceRadius = distance <= attendanceRadius;
        const accuracyText = accuracy ? `Akurasi GPS ±${Math.round(accuracy)} meter.` : '';

        distanceValue.textContent = formatMeters(distance);
        distanceNote.textContent = `Radius maksimal ${formatMeters(attendanceRadius)}. ${accuracyText}`.trim();

        if (isWithinAttendanceRadius) {
            setDistanceBadge('is-inside', 'fa-solid fa-circle-check', 'Di dalam radius');
            mapStatus.textContent = 'Posisi Anda berada dalam radius kantor';
        } else {
            setDistanceBadge('is-outside', 'fa-solid fa-circle-xmark', 'Di luar radius');
            mapStatus.textContent = 'Posisi Anda berada di luar radius kantor';
        }

        updateFingerprintState();
    }

    // Realtime Clock
    setInterval(() => {
        document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    }, 1000);

    // Refresh Tombol & Efek Ring
    function refreshSubmitState() {
        const hasLat = latitudeInput.value !== '';
        hasValidGpsLocation = hasLat;
        updateFingerprintState();

        if (hasLat && (!attendanceSettingReady || isWithinAttendanceRadius)) {
            submitBtn.disabled = false;
            instructionText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Lokasi siap. Silakan tekan tombol.</span>';
        } else if (hasLat && attendanceSettingReady && !isWithinAttendanceRadius) {
            submitBtn.disabled = true;
            instructionText.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Anda berada di luar radius kantor.</span>';
        } else {
            submitBtn.disabled = true;
            instructionText.textContent = 'Tombol akan aktif otomatis saat lokasi Anda ditemukan.';
        }
    }

    // Geolocation
    async function getPosition() {
        if (!navigator.geolocation) {
            locationStatus.textContent = "GPS tidak didukung";
            locationStatus.className = "clock-location-status is-error";
            mapStatus.textContent = 'Browser tidak mendukung GPS';
            return;
        }

        locationStatus.textContent = "Mencari lokasi...";
        locationStatus.className = "clock-location-status is-loading";

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                latitudeInput.value = pos.coords.latitude;
                longitudeInput.value = pos.coords.longitude;
                locationStatus.textContent = `Terdeteksi (±${Math.round(pos.coords.accuracy)}m)`;
                locationStatus.className = "clock-location-status is-detected";
                updateAttendanceMap(pos.coords.latitude, pos.coords.longitude);
                updateDistanceInfo(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                refreshSubmitState();
            },
            (err) => {
                hasValidGpsLocation = false;
                isWithinAttendanceRadius = false;
                locationStatus.textContent = "Gagal memuat lokasi";
                locationStatus.className = "clock-location-status is-error";
                instructionText.textContent = "Harap izinkan akses lokasi di browser Anda.";
                distanceValue.textContent = 'Lokasi belum tersedia';
                distanceNote.textContent = 'Izinkan akses GPS agar sistem bisa menghitung jarak ke kantor.';
                setDistanceBadge('is-outside', 'fa-solid fa-location-crosshairs', 'GPS tidak aktif');
                mapStatus.textContent = 'GPS belum aktif atau akses lokasi ditolak';
                updateFingerprintState();
                refreshSubmitState();
            },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    // Handle Photo Change
    photoInput?.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Compression Settings
        const MAX_WIDTH = 1200;
        const MAX_HEIGHT = 1200;
        const QUALITY = 0.7;

        // Resize and Compress Image
        const processImage = (file) => {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    const img = new Image();
                    img.onload = () => {
                        let width = img.width;
                        let height = img.height;

                        if (width > height) {
                            if (width > MAX_WIDTH) {
                                height *= MAX_WIDTH / width;
                                width = MAX_WIDTH;
                            }
                        } else {
                            if (height > MAX_HEIGHT) {
                                width *= MAX_HEIGHT / height;
                                height = MAX_HEIGHT;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        canvas.toBlob((blob) => {
                            resolve(blob);
                        }, 'image/jpeg', QUALITY);
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
        };

        const compressedBlob = await processImage(file);
        const compressedFile = new File([compressedBlob], file.name, { type: 'image/jpeg' });
        
        // Update input file with compressed version
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressedFile);
        photoInput.files = dataTransfer.files;

        // Preview
        const preview = document.getElementById('image-preview');
        preview.src = URL.createObjectURL(compressedBlob);
        document.getElementById('upload-area').classList.add('has-image');

        // Face Verification
        if (faceVerificationEnabled) {
            Swal.fire({ title: 'Memeriksa Wajah...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const img = await faceapi.bufferToImage(file);
                const detection = await faceapi.detectSingleFace(img);
                Swal.close();
                if (!detection) {
                    Swal.fire('Wajah Tidak Jelas', 'Pastikan wajah terlihat jelas tanpa penghalang.', 'error');
                    photoInput.value = '';
                    document.getElementById('upload-area').classList.remove('has-image');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal memuat sistem deteksi wajah.', 'error');
            }
        }
    });

    // Device ID
    document.getElementById('deviceFingerprint').value = btoa(navigator.userAgent).substring(0, 24);

    // Initial Load
    window.onload = () => {
        ensureAttendanceMap();
        updateFingerprintState();
        getPosition();
        if (faceVerificationEnabled) {
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        }
    };

    document.getElementById('retryLocationBtn').addEventListener('click', getPosition);
    
    document.getElementById('attendanceForm')?.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    });
</script>

<div class="modal fade" id="createLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leave-requests.store') }}" method="POST">
            @csrf
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Ajukan Cuti/Izin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="category" class="form-select" required>
                            <option value="cuti">Cuti</option>
                            <option value="sakit">Izin Sakit</option>
                            <option value="keluarga">Izin Keperluan Keluarga</option>
                            <option value="mendadak">Izin Keperluan Mendadak</option>
                            <option value="lainnya">Izin Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-info rounded-4 border-0 mb-0">
                        Maximum {{ $leaveQuota }} hari diperbolehkan per bulan.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
