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

                <div class="alert alert-primary rounded-4 border-0 shadow-sm mb-4 small">
                    <div class="fw-bold mb-1">
                        <i class="fa-solid fa-business-time me-1"></i>{{ __('Informasi Shift Hari Ini') }}
                    </div>
                    <div>{{ __('Grup: :group', ['group' => $shiftInfo['group_label'] ?? '-']) }}</div>
                    <div>{{ __('Status Jadwal: :status', ['status' => $shiftInfo['status_label'] ?? '-']) }}</div>
                    <div>{{ __('Shift: :shift', ['shift' => $shiftInfo['shift_label'] ?? '-']) }}</div>
                    <div>{{ __('Jam Shift: :start - :end WIB', ['start' => $shiftInfo['shift_start'] ?? '-', 'end' => $shiftInfo['shift_end'] ?? '-']) }}</div>
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
                        <input type="hidden" name="device_fingerprint" id="deviceFingerprint">

                        @if($isOut)
                        <div class="bg-warning-subtle text-warning-emphasis rounded-4 p-3 text-center mb-4 small">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> {!! __('Jam Masuk: :time', ['time' => '<b>' . $todayAttendance->clock_in->format('H:i') . '</b>']) !!}
                        </div>
                        @endif

                        <div class="camera-card p-3 rounded-5 shadow-sm mb-2 text-center border position-relative">
                            <h6 class="text-muted text-center small fw-bold mb-3 text-uppercase text-start ps-2">{{ __('FOTO SELFIE (OPSIONAL)') }}</h6>
                            <label class="modern-camera-box" id="upload-area">
                                <div id="upload-placeholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                    <div class="icon-camera-bg mb-2">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>
                                    <span class="text-muted small fw-bold">{{ __('Ambil Foto Selfie (Opsional)') }}</span>
                                </div>
                                <img id="image-preview" class="modern-preview-img" src="#" alt="Preview">
                                <input type="file" name="photo" id="photo" accept="image/*" capture="user" onchange="previewImage(event)">
                                <div id="photo-upload-note" class="small text-muted mt-2 px-3 text-center"></div>
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
                                    {{ __('Izinkan lokasi untuk mengaktifkan tombol absen. Foto selfie bersifat opsional.') }}
                                </p>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

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
    const attendancePhotoMaxKb = Number(@json((int) \App\Models\Setting::getValue('attendance_photo_max_kb', 2048)));
    const attendancePhotoMaxWidth = Number(@json((int) \App\Models\Setting::getValue('attendance_photo_max_width', 1280)));
    const attendancePhotoCompressQuality = Number(@json((int) \App\Models\Setting::getValue('attendance_photo_compress_quality', 78)));
    const submitBtn = document.getElementById('submitBtn');
    const instructionText = document.getElementById('instruction-text');
    const fingerprintContainer = document.getElementById('fingerprintContainer');
    const uploadArea = document.getElementById('upload-area');
    const attendanceForm = document.getElementById('attendanceForm');
    const deviceFingerprintInput = document.getElementById('deviceFingerprint');
    const photoUploadNote = document.getElementById('photo-upload-note');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    function hasValidLocation() {
        if (!latitudeInput || !longitudeInput) return false;
        const lat = String(latitudeInput.value || '').trim();
        const lng = String(longitudeInput.value || '').trim();
        return lat !== '' && lng !== '';
    }

    function refreshSubmitState() {
        setSubmitEnabled(hasValidLocation());
    }

    function setSubmitEnabled(enabled) {
        if (!submitBtn || !fingerprintContainer) return;
        submitBtn.disabled = !enabled;
        fingerprintContainer.classList.toggle('fingerprint-ready', enabled);
    }

    async function buildDeviceFingerprint() {
        const storageKey = 'mstore_attendance_device_id_v1';
        const profileKey = [
            navigator.userAgent || '',
            navigator.platform || '',
            navigator.language || '',
            String(new Date().getTimezoneOffset())
        ].join('|');

        try {
            const existing = (localStorage.getItem(storageKey) || '').trim();
            if (existing.length >= 24) {
                return existing;
            }
        } catch (error) {}

        const randomSeed = `${Date.now()}-${Math.random()}-${Math.random()}`;
        const payload = `${profileKey}|${randomSeed}`;
        let generatedId = '';

        if (window.crypto?.subtle && window.TextEncoder) {
            const buffer = new TextEncoder().encode(payload);
            const digest = await window.crypto.subtle.digest('SHA-256', buffer);
            generatedId = Array.from(new Uint8Array(digest)).map((b) => b.toString(16).padStart(2, '0')).join('');
        } else {
            let hash = 0;
            for (let i = 0; i < payload.length; i++) {
                hash = ((hash << 5) - hash) + payload.charCodeAt(i);
                hash |= 0;
            }
            generatedId = `legacy-${Math.abs(hash)}-${Date.now()}`;
        }

        try {
            localStorage.setItem(storageKey, generatedId);
        } catch (error) {}

        return generatedId;
    }

    if (attendanceForm && submitBtn) {
        refreshSubmitState();
        buildDeviceFingerprint()
            .then((fingerprint) => {
                if (deviceFingerprintInput) {
                    deviceFingerprintInput.value = fingerprint;
                }
            })
            .catch(() => {
                if (deviceFingerprintInput) {
                    deviceFingerprintInput.value = '';
                }
            });

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

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);
        if (!Number.isFinite(size) || size <= 0) return '0 KB';
        if (size < 1024 * 1024) return (size / 1024).toFixed(0) + ' KB';
        return (size / (1024 * 1024)).toFixed(2) + ' MB';
    }

    async function replaceInputFile(inputEl, file) {
        if (!inputEl || !file) return;
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        inputEl.files = dataTransfer.files;
    }

    async function optimizePhotoFile(file) {
        if (!file || !String(file.type || '').startsWith('image/')) {
            return file;
        }

        const maxKb = Number.isFinite(attendancePhotoMaxKb) && attendancePhotoMaxKb > 0 ? attendancePhotoMaxKb : 2048;
        const maxWidth = Number.isFinite(attendancePhotoMaxWidth) && attendancePhotoMaxWidth > 0 ? attendancePhotoMaxWidth : 1280;
        const qualityPercent = Number.isFinite(attendancePhotoCompressQuality) ? attendancePhotoCompressQuality : 78;
        const quality = Math.min(0.95, Math.max(0.45, qualityPercent / 100));

        const imageUrl = URL.createObjectURL(file);
        try {
            const img = new Image();
            img.src = imageUrl;
            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
            });

            let targetWidth = img.naturalWidth || img.width;
            let targetHeight = img.naturalHeight || img.height;
            if (targetWidth > maxWidth) {
                const ratio = maxWidth / targetWidth;
                targetWidth = Math.round(targetWidth * ratio);
                targetHeight = Math.round(targetHeight * ratio);
            }

            const canvas = document.createElement('canvas');
            canvas.width = targetWidth;
            canvas.height = targetHeight;
            const ctx = canvas.getContext('2d', { alpha: false });
            if (!ctx) {
                return file;
            }

            ctx.drawImage(img, 0, 0, targetWidth, targetHeight);
            const maxBytes = maxKb * 1024;
            const blobCandidates = await Promise.all([
                new Promise((resolve) => canvas.toBlob((blob) => resolve(blob), 'image/webp', quality)),
                new Promise((resolve) => canvas.toBlob((blob) => resolve(blob), 'image/jpeg', quality)),
            ]);

            const candidates = blobCandidates
                .filter((blob) => blob && blob.size > 0)
                .filter((blob) => blob.size <= maxBytes)
                .sort((a, b) => a.size - b.size);

            if (candidates.length === 0) {
                return file;
            }

            const bestBlob = candidates[0];
            if (bestBlob.size >= file.size) {
                return file;
            }

            const mime = String(bestBlob.type || 'image/jpeg').toLowerCase();
            const extension = mime.includes('webp') ? 'webp' : 'jpg';
            const originalName = String(file.name || 'attendance-photo');
            const normalizedName = originalName.replace(/\.[a-zA-Z0-9]+$/, '');
            return new File([bestBlob], normalizedName + '.' + extension, {
                type: mime,
                lastModified: Date.now(),
            });
        } catch (error) {
            return file;
        } finally {
            URL.revokeObjectURL(imageUrl);
        }
    }

    async function previewImage(event) {
        const inputEl = event.target;
        const file = inputEl.files[0];
        if (!file) {
            refreshSubmitState();
            return;
        }

        setSubmitEnabled(false);
        if (photoUploadNote) {
            photoUploadNote.textContent = 'Mengoptimalkan foto...';
        }

        const optimizedFile = await optimizePhotoFile(file);
        if (optimizedFile !== file) {
            await replaceInputFile(inputEl, optimizedFile);
        }

        const preview = document.getElementById('image-preview');
        preview.src = URL.createObjectURL(optimizedFile);
        uploadArea.classList.add('has-image');
        if (photoUploadNote) {
            photoUploadNote.textContent = 'Ukuran upload: '
                + formatFileSize(optimizedFile.size)
                + ' (asli: ' + formatFileSize(file.size) + ')'
                + ' | format: ' + (String(optimizedFile.type || file.type || '').replace('image/', '').toUpperCase() || 'IMAGE');
        }

        if (!faceVerificationEnabled) {
            refreshSubmitState();
            return;
        }

        Swal.fire({ 
            title: 'Memverifikasi Wajah...', 
            html: 'Mohon tunggu sebentar',
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });

        try {
            const img = await faceapi.bufferToImage(optimizedFile);
            const detection = await faceapi.detectSingleFace(img);
            
            if (!detection) {
                Swal.fire('Gagal', 'Wajah tidak terdeteksi jelas. Pastikan pencahayaan cukup.', 'error');
                resetCamera();
            } else {
                Swal.close();
                refreshSubmitState();
                instructionText.textContent = "Lokasi terdeteksi. Selfie berhasil diverifikasi (opsional).";
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
        if (photoUploadNote) {
            photoUploadNote.textContent = '';
        }
        refreshSubmitState();
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
            refreshSubmitState();
        }, () => {
            document.getElementById('location-status').textContent = 'Lokasi gagal dideteksi';
            refreshSubmitState();
        });
    } else {
        document.getElementById('location-status').textContent = 'Geolokasi tidak didukung';
        refreshSubmitState();
    }
</script>
@endsection
