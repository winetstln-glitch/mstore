@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 px-0 px-md-3">
        <div class="card shadow-lg border-0 rounded-5 overflow-hidden attendance-shell mb-2 pb-2">
            <div class="leave-header-card p-4 pb-2 rounded-bottom-5 shadow position-relative attendance-header">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <p class="leave-header-greeting small mb-0">{{ __('Selamat Datang,') }}</p>
                        <h4 class="leave-header-name fw-bold mb-0">{{ Auth::user()->name }}</h4>
                    </div>
                    <div class="user-avatar-badge rounded-circle p-2 border border-white border-opacity-25">
                        <i class="fa-solid fa-user-circle fs-3"></i>
                    </div>
                </div>

                <div class="clock-panel p-4 rounded-4 shadow-sm text-center position-relative" style="z-index: 10; margin-bottom: -60px;">
                    <p class="clock-date text-uppercase tracking-wider small fw-bold mb-1">{{ now()->format('l, d F Y') }}</p>
                    <h2 class="display-4 fw-bold mb-2 font-monospace clock-time" id="clock">00:00:00</h2>
                    <div class="d-flex align-items-center justify-content-center small clock-location">
                        <i class="fa-solid fa-location-dot text-danger me-2"></i>
                        <span id="location-status" class="clock-location-status">{{ __('Mencari lokasi...') }}</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 pt-5 mt-5">
                <div id="face-model-status" class="alert alert-info rounded-4 text-center mb-4 border-0 shadow-sm" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin me-2"></i> {{ __('Memuat Model Deteksi Wajah...') }}
                </div>

                @if($errors->any())
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4" role="alert">
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3 mb-4 px-1">
                    <div class="col-4">
                        <div class="status-info-card status-info-masuk rounded-4 p-3 text-center h-100">
                            <div class="status-info-label">{{ __('Masuk') }}</div>
                            <div class="status-info-value">{{ $attendanceSummary['masuk'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-info-card status-info-izin rounded-4 p-3 text-center h-100">
                            <div class="status-info-label">{{ __('Izin') }}</div>
                            <div class="status-info-value">{{ $attendanceSummary['izin'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-info-card status-info-sakit rounded-4 p-3 text-center h-100">
                            <div class="status-info-label">{{ __('Sakit') }}</div>
                            <div class="status-info-value">{{ $attendanceSummary['sakit'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                @if(Auth::user()->hasPermission('leave.create') || Auth::user()->hasPermission('leave.view'))
                <div class="d-grid mb-4 px-1">
                    <button type="button" class="btn btn-outline-primary rounded-4 fw-semibold py-2" data-bs-toggle="modal" data-bs-target="#attendanceLeaveModal">
                        <i class="fa-solid fa-plane-departure me-1"></i>{{ __('Request Leave') }}
                    </button>
                </div>
                @endif

                @if($todayAttendance && $todayAttendance->clock_out)
                    <div class="text-center p-5 rounded-5 shadow-sm border my-4 done-state-card">
                        <div class="display-1 text-success mb-4 text-gradient">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h4 class="fw-bold text-dark">{{ __('Selesai Hari Ini') }}</h4>
                        <p class="text-muted small">Anda telah melakukan absen pulang pada pukul <b>{{ $todayAttendance->clock_out->format('H:i') }}</b></p>
                        <div class="badge bg-success-subtle text-success px-3 py-2 rounded-pill mt-2">{{ __('Sampai Jumpa Besok!') }}</div>
                    </div>

                @else
                    @php
                        $isOut = ($todayAttendance && !$todayAttendance->clock_out);
                        $formRoute = $isOut ? route('attendance.update', $todayAttendance->id) : route('attendance.store');
                        $themeColorClass = $isOut ? 'theme-out' : 'theme-in';
                    @endphp

                    <form action="{{ $formRoute }}" method="POST" enctype="multipart/form-data" id="attendanceForm" class="{{ $themeColorClass }}">
                        @csrf
                        @if($isOut) @method('PUT') @endif
                        
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        @if($isOut)
                        <div class="bg-warning-subtle text-warning-emphasis rounded-4 p-3 text-center mb-4 small">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> {!! __('Jam Masuk: :time', ['time' => '<b>' . $todayAttendance->clock_in->format('H:i') . '</b>']) !!}
                        </div>
                        @endif

                        <div class="camera-card p-3 rounded-5 shadow-sm mb-2 text-center border position-relative">
                            <h6 class="text-muted text-center small fw-bold mb-3 text-uppercase text-start ps-2">{{ __('VERIFIKASI WAJAH') }}</h6>
                            <label class="modern-camera-box" id="upload-area">
                                <div id="upload-placeholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                    <div class="icon-camera-bg mb-2">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>
                                    <span class="text-muted small fw-bold">{{ __('Ambil Foto Selfie') }}</span>
                                </div>
                                <img id="image-preview" class="modern-preview-img" src="#" alt="Preview">
                                <input type="file" name="photo" id="photo" accept="image/*" capture="user" required onchange="previewImage(event)">
                            </label>
                        </div>

                        <div class="d-flex flex-column align-items-center justify-content-center my-2 py-2">
                            <div class="fingerprint-container" id="fingerprintContainer">
                                <div class="outer-ring-large"></div>
                                <div class="outer-ring-small"></div>

                                <button type="submit" id="submitBtn" class="fingerprint-main-btn" disabled>
                                    <div class="inner-glow"></div>
                                    <i class="fa-solid fa-fingerprint"></i>
                                </button>
                            </div>

                            <div class="text-center mt-4 pt-2">
                                <h5 class="fw-bold mb-1 status-label">
                                    {{ $isOut ? __('PRESENSI PULANG') : __('PRESENSI MASUK') }}
                                </h5>
                                <p class="text-muted small px-5" id="instruction-text">
                                    {{ __('Silahkan ambil foto selfie untuk mengaktifkan tombol absen.') }}
                                </p>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    :root {
        --in-color: #3b82f6;
        --out-color: #ef4444;
    }

    body { background-color: var(--bs-body-bg); }
    .theme-in { --current-color: var(--in-color); }
    .theme-out { --current-color: var(--out-color); }
    .status-label { color: var(--current-color); }
    .leave-header-card {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        color: #1e3a8a;
    }

    .leave-header-greeting {
        color: #334155;
    }

    .leave-header-name {
        color: #0f172a;
    }
    .attendance-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    }

    [data-bs-theme="dark"] .attendance-shell {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
    }

    [data-bs-theme="dark"] .leave-header-card {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-color: rgba(96, 165, 250, 0.28);
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .leave-header-greeting {
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .leave-header-name {
        color: #f8fafc;
    }
    .attendance-header {
        border-bottom-left-radius: 2.5rem;
        border-bottom-right-radius: 2.5rem;
    }

    .user-avatar-badge {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .clock-panel {
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        border: 1px solid var(--bs-border-color);
    }

    .clock-date,
    .clock-location,
    .clock-location-status {
        color: var(--bs-secondary-color);
    }

    .clock-time {
        color: var(--in-color);
    }

    .clock-location-status.is-detected {
        color: #16a34a;
        font-weight: 600;
    }

    .camera-card,
    .done-state-card {
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        border-color: var(--bs-border-color) !important;
    }

    .status-info-card {
        border: 1px solid transparent;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
    }

    .status-info-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.2rem;
    }

    .status-info-value {
        font-size: 1.4rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .status-info-masuk {
        background: rgba(16, 185, 129, 0.13);
        border-color: rgba(16, 185, 129, 0.3);
        color: #059669;
    }

    .status-info-izin {
        background: rgba(245, 158, 11, 0.13);
        border-color: rgba(245, 158, 11, 0.3);
        color: #d97706;
    }

    .status-info-sakit {
        background: rgba(239, 68, 68, 0.13);
        border-color: rgba(239, 68, 68, 0.3);
        color: #dc2626;
    }

    .fingerprint-container {
        position: relative;
        width: 180px;
        height: 180px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .fingerprint-main-btn {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: none;
        background: var(--current-color);
        color: white;
        font-size: 3rem;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .fingerprint-main-btn:disabled {
        background: #cbd5e1 !important;
        box-shadow: none !important;
        cursor: not-allowed;
    }

    .fingerprint-main-btn:not(:disabled):active {
        transform: scale(0.85);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .outer-ring-small, .outer-ring-large {
        position: absolute;
        border-radius: 50%;
        border: 10px solid var(--current-color);
        transition: all 0.5s ease;
    }

    .outer-ring-small {
        width: 135px;
        height: 135px;
        opacity: 0.2;
    }

    .outer-ring-large {
        width: 175px;
        height: 175px;
        opacity: 0.1;
    }

    .fingerprint-container.fingerprint-ready .outer-ring-small {
        animation: pulse-small 2s infinite ease-out;
    }
    .fingerprint-container.fingerprint-ready .outer-ring-large {
        animation: pulse-large 2s infinite ease-out 0.5s;
    }

    @keyframes pulse-small {
        0% { transform: scale(1); opacity: 0.4; }
        100% { transform: scale(1.15); opacity: 0; }
    }
    @keyframes pulse-large {
        0% { transform: scale(1); opacity: 0.2; }
        100% { transform: scale(1.2); opacity: 0; }
    }

    .modern-camera-box {
        width: 100%;
        height: 180px;
        border: 2px dashed #e2e8f0;
        border-radius: 2rem;
        background: #f8fafc;
        overflow: hidden;
        cursor: pointer;
        display: block;
        transition: 0.3s;
    }
    #upload-placeholder {
        position: absolute;
        inset: 0;
        z-index: 2;
    }
    .modern-camera-box.has-image #upload-placeholder {
        display: none !important;
    }
    .modern-camera-box:hover { border-color: var(--current-color); }
    .modern-camera-box input { display: none; }
    
    .icon-camera-bg {
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        font-size: 1.2rem;
    }

    .modern-preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
        position: relative;
        z-index: 1;
    }
    .modern-camera-box.has-image .modern-preview-img {
        display: block !important;
    }

    .leave-modal-body-surface {
        background: var(--bs-tertiary-bg);
    }

    [data-bs-theme="dark"] .modern-camera-box {
        border-color: #334155;
        background: #0f172a;
    }

    [data-bs-theme="dark"] .icon-camera-bg {
        background: #1e293b;
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .status-info-masuk {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
    }

    [data-bs-theme="dark"] .status-info-izin {
        background: rgba(245, 158, 11, 0.2);
        color: #fcd34d;
    }

    [data-bs-theme="dark"] .status-info-sakit {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }

    [data-bs-theme="dark"] .clock-panel {
        background: #0f172a;
        border-color: #334155;
    }

    [data-bs-theme="dark"] .clock-date,
    [data-bs-theme="dark"] .clock-location,
    [data-bs-theme="dark"] .clock-location-status {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .clock-time {
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .clock-location-status.is-detected {
        color: #86efac;
    }

    [data-bs-theme="dark"] .leave-modal-body-surface {
        background: #0f172a;
    }

    [data-bs-theme="dark"] #attendanceLeaveModal .modal-content {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border: 1px solid rgba(96, 165, 250, 0.28) !important;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] #attendanceLeaveModal .modal-header,
    [data-bs-theme="dark"] #attendanceLeaveModal .modal-footer {
        border-color: #334155 !important;
    }

    [data-bs-theme="dark"] #attendanceLeaveModal .modal-title,
    [data-bs-theme="dark"] #attendanceLeaveModal .form-label {
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] #attendanceLeaveModal .form-control,
    [data-bs-theme="dark"] #attendanceLeaveModal .form-select,
    [data-bs-theme="dark"] #attendanceLeaveModal textarea {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] #attendanceLeaveModal .form-control::placeholder,
    [data-bs-theme="dark"] #attendanceLeaveModal textarea::placeholder {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] #attendanceLeaveModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    .text-gradient { background: linear-gradient(45deg, #10b981, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
</style>
@endpush

@if(Auth::user()->hasPermission('leave.create'))
<div class="modal fade" id="attendanceLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leave-requests.store') }}" method="POST">
            @csrf
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">{{ __('Request Leave') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body leave-modal-body-surface">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="cuti">Cuti</option>
                            <option value="sakit">Izin Sakit</option>
                            <option value="keluarga">Izin Keperluan Keluarga</option>
                            <option value="mendadak">Izin Keperluan Mendadak</option>
                            <option value="lainnya">Izin Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Start Date') }}</label>
                        <input type="date" name="start_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('End Date') }}</label>
                        <input type="date" name="end_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-info rounded-4 border-0 mb-0">
                        {{ __('Maximum :count days allowed per month.', ['count' => $leaveQuota]) }}
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit Request') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js/weights';
    const faceVerificationEnabled = {{ $faceVerificationEnabled == '1' ? 'true' : 'false' }};
    const submitBtn = document.getElementById('submitBtn');
    const instructionText = document.getElementById('instruction-text');
    const fingerprintContainer = document.getElementById('fingerprintContainer');
    const uploadArea = document.getElementById('upload-area');
    const attendanceForm = document.getElementById('attendanceForm');

    function setSubmitEnabled(enabled) {
        if (!submitBtn || !fingerprintContainer) return;
        submitBtn.disabled = !enabled;
        fingerprintContainer.classList.toggle('fingerprint-ready', enabled);
    }

    if (attendanceForm && submitBtn) {
        attendanceForm.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        });
    }

    async function loadModels() {
        if (!faceVerificationEnabled) return;
        const status = document.getElementById('face-model-status');
        status.style.display = 'block';
        try {
            await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
            status.style.display = 'none';
        } catch (e) { 
            status.innerHTML = "Gagal memuat sistem deteksi wajah.";
            status.className = "alert alert-danger";
        }
    }

    if (faceVerificationEnabled) loadModels();

    async function previewImage(event) {
        const file = event.target.files[0];
        if (!file) return;

        const preview = document.getElementById('image-preview');
        preview.src = URL.createObjectURL(file);
        uploadArea.classList.add('has-image');

        if (!faceVerificationEnabled) {
            setSubmitEnabled(true);
            return;
        }

        Swal.fire({ 
            title: 'Memverifikasi Wajah...', 
            html: 'Mohon tunggu sebentar',
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });

        try {
            const img = await faceapi.bufferToImage(file);
            const detection = await faceapi.detectSingleFace(img);
            
            if (!detection) {
                Swal.fire('Gagal', 'Wajah tidak terdeteksi jelas. Pastikan pencahayaan cukup.', 'error');
                resetCamera();
            } else {
                Swal.close();
                setSubmitEnabled(true);
                instructionText.textContent = "Verifikasi Berhasil! Silahkan tekan tombol sidik jari.";
                instructionText.className = "text-success small px-5";
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal memproses gambar.', 'error');
            resetCamera();
        }
    }

    function resetCamera() {
        document.getElementById('photo').value = '';
        document.getElementById('image-preview').style.display = 'none';
        uploadArea.classList.remove('has-image');
        setSubmitEnabled(false);
    }

    setInterval(() => {
        document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    }, 1000);

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(p => {
            document.getElementById('latitude').value = p.coords.latitude;
            document.getElementById('longitude').value = p.coords.longitude;
            document.getElementById('location-status').textContent = 'Lokasi Terdeteksi';
            document.getElementById('location-status').className = 'clock-location-status is-detected';
        });
    }
</script>
@endsection
