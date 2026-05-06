@extends('layouts.app')

@section('content')
<style>
    /* Custom Styling for Modern UI */
    .attendance-shell { background: #f8f9fa; }
    .attendance-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
    }
    .leave-header-greeting { opacity: 0.8; }
    .clock-panel {
        background: white;
        color: #333;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .clock-time { color: #4e73df; letter-spacing: 2px; }
    
    /* Fingerprint Animation & Double Ring Effect */
    .fingerprint-container {
        position: relative;
        width: 120px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    /* Double Ring Layers */
    .fingerprint-ring {
        position: absolute;
        border-radius: 50%;
        border: 2px solid #4e73df;
        opacity: 0;
        transition: all 0.4s ease;
        pointer-events: none;
    }

    /* Efek saat Ready (Aktif) */
    .fingerprint-ready .ring-1 {
        width: 100px;
        height: 100px;
        opacity: 0.3;
        animation: pulse-ring 2s infinite;
    }
    .fingerprint-ready .ring-2 {
        width: 115px;
        height: 115px;
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
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: none;
        background: #e0e0e0;
        color: white;
        font-size: 2.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: not-allowed;
    }

    .fingerprint-ready .fingerprint-main-btn {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        box-shadow: 0 0 20px rgba(78, 115, 223, 0.5);
        cursor: pointer;
    }

    .fingerprint-ready .fingerprint-main-btn:active {
        transform: scale(0.85);
        box-shadow: 0 0 5px rgba(78, 115, 223, 0.8);
    }
    
    /* Camera Preview Styling */
    .modern-camera-box {
        width: 100%;
        max-width: 300px;
        height: 180px;
        border: 2px dashed #dee2e6;
        border-radius: 20px;
        display: inline-block;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }
    .modern-camera-box.has-image { border-style: solid; border-color: #4e73df; background: #fff; }
    .modern-preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
    }
    .has-image .modern-preview-img { display: block; }
    .has-image #upload-placeholder { display: none !important; }
    #photo { display: none; }
    
    .status-info-card { transition: transform 0.2s; border: 1px solid rgba(0,0,0,0.03); }
    .status-info-masuk { background: #f0fdf4; color: #166534; }
    .status-info-izin { background: #fffbeb; color: #92400e; }
    .status-info-sakit { background: #fef2f2; color: #991b1b; }
    
    .clock-location-status.is-loading { color: #6b7280; }
    .clock-location-status.is-detected { color: #059669; font-weight: 600; }
    .clock-location-status.is-error { color: #dc2626; }
</style>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 px-0 px-md-3">
        <div class="card shadow-lg border-0 rounded-5 overflow-hidden attendance-shell mb-2 pb-2">
            <!-- Header Section -->
            <div class="leave-header-card p-4 pb-2 rounded-bottom-5 shadow position-relative attendance-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <p class="leave-header-greeting small mb-0">{{ __('Selamat Datang,') }}</p>
                        <h4 class="leave-header-name fw-bold mb-0">{{ Auth::user()->name }}</h4>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-warning p-2 border border-white border-opacity-25 rounded-circle shadow" data-bs-toggle="modal" data-bs-target="#attendanceHelpModal">
                            <i class="fa-solid fa-circle-question text-dark"></i>
                        </button>
                    </div>
                </div>

                <!-- Clock & GPS Panel -->
                <div class="clock-panel p-4 rounded-4 shadow-sm text-center position-relative" style="z-index: 10; margin-bottom: -70px;">
                    <p class="clock-date text-uppercase tracking-wider small fw-bold mb-1 text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
                    <h2 class="display-5 fw-bold mb-2 font-monospace clock-time" id="clock">00:00:00</h2>
                    
                    <div class="d-flex align-items-center justify-content-center small clock-location mb-3">
                        <i class="fa-solid fa-location-dot text-danger me-2"></i>
                        <span id="location-status" class="clock-location-status is-loading">{{ __('Mencari lokasi...') }}</span>
                    </div>

                    <div class="d-flex flex-column align-items-center justify-content-center pb-2">
                        <div class="fingerprint-container" id="fingerprintContainer">
                            <!-- Double Ring Elements -->
                            <div class="fingerprint-ring ring-1"></div>
                            <div class="fingerprint-ring ring-2"></div>
                            
                            <button type="submit" form="attendanceForm" id="submitBtn" class="fingerprint-main-btn" disabled>
                                <i class="fa-solid fa-fingerprint"></i>
                            </button>
                        </div>
                        <button type="button" id="retryLocationBtn" class="btn btn-sm btn-link text-decoration-none text-muted mt-1">
                            <i class="fa-solid fa-arrows-rotate me-1"></i>{{ __('Refresh Lokasi') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Body -->
            <div class="card-body p-4 pt-3 mt-5">
                <div id="face-model-status" class="alert alert-info rounded-4 text-center mb-2 border-0 shadow-sm" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin me-2"></i> {{ __('Memuat Model Deteksi Wajah...') }}
                </div>

                @if($errors->any())
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3">
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Stats Grid -->
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="status-info-card status-info-masuk rounded-4 p-2 text-center">
                            <div class="small fw-bold">{{ __('Masuk') }}</div>
                            <div class="h5 mb-0 fw-bold">{{ $attendanceSummary['masuk'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-info-card status-info-izin rounded-4 p-2 text-center">
                            <div class="small fw-bold">{{ __('Izin') }}</div>
                            <div class="h5 mb-0 fw-bold">{{ $attendanceSummary['izin'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-info-card status-info-sakit rounded-4 p-2 text-center">
                            <div class="small fw-bold">{{ __('Sakit') }}</div>
                            <div class="h5 mb-0 fw-bold">{{ $attendanceSummary['sakit'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Shift Alert -->
                <div class="alert alert-primary rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-business-time me-1"></i> <b>{{ $shiftInfo['shift_label'] ?? 'Reguler' }}</b>
                    </div>
                    <div class="fw-bold text-primary">{{ $shiftInfo['shift_start'] ?? '--:--' }} - {{ $shiftInfo['shift_end'] ?? '--:--' }}</div>
                </div>

                @if($todayAttendance && $todayAttendance->clock_out)
                    <div class="text-center p-4 rounded-5 border bg-white my-3">
                        <div class="display-5 text-success mb-2"><i class="fa-solid fa-circle-check"></i></div>
                        <h6 class="fw-bold">{{ __('Presensi Hari Ini Selesai') }}</h6>
                        <p class="text-muted small mb-0">Pulang pukul <b>{{ $todayAttendance->clock_out->format('H:i') }}</b></p>
                    </div>
                @else
                    @php
                        $isOut = ($todayAttendance && !$todayAttendance->clock_out);
                        $formRoute = $isOut ? route('attendance.update', $todayAttendance->id) : route('attendance.store');
                    @endphp

                    <form action="{{ $formRoute }}" method="POST" enctype="multipart/form-data" id="attendanceForm">
                        @csrf
                        @if($isOut) @method('PUT') @endif
                        
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <input type="hidden" name="device_fingerprint" id="deviceFingerprint">

                        @if($isOut)
                        <div class="bg-warning-subtle text-warning-emphasis rounded-4 p-2 text-center mb-3 small border border-warning border-opacity-10">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Jam Masuk: <b>{{ $todayAttendance->clock_in->format('H:i') }}</b>
                        </div>
                        @endif

                        <!-- Camera Section -->
                        <div class="text-center mb-3">
                            <label class="modern-camera-box shadow-sm" id="upload-area">
                                <div id="upload-placeholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                    <i class="fa-solid fa-camera fs-2 text-muted mb-2"></i>
                                    <span class="text-muted small fw-bold">{{ __('Foto Selfie (Opsional)') }}</span>
                                </div>
                                <img id="image-preview" class="modern-preview-img" src="#">
                                <input type="file" name="photo" id="photo" accept="image/*" capture="user">
                            </label>
                            <div id="photo-upload-note" class="x-small text-muted mt-1" style="font-size: 0.7rem;"></div>
                        </div>

                        <div class="text-center">
                            <h6 class="fw-bold mb-1 text-uppercase">{{ $isOut ? __('Absen Pulang') : __('Absen Masuk') }}</h6>
                            <p class="text-muted small lh-sm" id="instruction-text">
                                {{ __('Tombol akan aktif otomatis saat lokasi Anda ditemukan.') }}
                            </p>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js/weights';
    const faceVerificationEnabled = {{ $faceVerificationEnabled == '1' ? 'true' : 'false' }};
    
    const submitBtn = document.getElementById('submitBtn');
    const instructionText = document.getElementById('instruction-text');
    const fingerprintContainer = document.getElementById('fingerprintContainer');
    const photoInput = document.getElementById('photo');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const locationStatus = document.getElementById('location-status');

    // Realtime Clock
    setInterval(() => {
        document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    }, 1000);

    // Refresh Tombol & Efek Ring
    function refreshSubmitState() {
        const hasLat = latitudeInput.value !== '';
        
        // Aktifkan tombol jika lokasi sudah terisi
        if (hasLat) {
            submitBtn.disabled = false;
            fingerprintContainer.classList.add('fingerprint-ready');
            instructionText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Lokasi Siap. Silakan tekan tombol.</span>';
        } else {
            submitBtn.disabled = true;
            fingerprintContainer.classList.remove('fingerprint-ready');
        }
    }

    // Geolocation
    async function getPosition() {
        if (!navigator.geolocation) {
            locationStatus.textContent = "GPS tidak didukung";
            locationStatus.className = "clock-location-status is-error";
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
                refreshSubmitState();
            },
            (err) => {
                locationStatus.textContent = "Gagal memuat lokasi";
                locationStatus.className = "clock-location-status is-error";
                instructionText.textContent = "Harap izinkan akses lokasi di browser Anda.";
            },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    // Handle Photo Change
    photoInput?.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Preview
        const reader = new FileReader();
        reader.onload = (event) => {
            const preview = document.getElementById('image-preview');
            preview.src = event.target.result;
            document.getElementById('upload-area').classList.add('has-image');
        };
        reader.readAsDataURL(file);

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
@endsection
