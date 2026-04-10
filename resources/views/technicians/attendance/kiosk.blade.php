@extends('layouts.app')

@push('styles')
<style>
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 border-top border-4 border-success mb-3">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Absensi Barcode Karyawan & Teknisi</h5>
                <span class="badge bg-success-subtle text-success">Mode Kiosk</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    Gunakan 1 HP kantor, scan barcode/QR di ID Card karyawan untuk absen masuk/pulang otomatis.
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div id="reader" class="border rounded p-2" style="min-height: 260px;"></div>
                    </div>
                    <div class="col-md-6">
                        <form id="manualScanForm" class="mb-3">
                            @csrf
                            <label class="form-label fw-semibold">Scanner / Input Kode</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white"><i class="fa-solid fa-barcode"></i></span>
                                <input type="text" id="manualCode" class="form-control form-control-lg" placeholder="Scan Barcode / Tap Kartu..." autocomplete="off">
                                <button type="submit" class="btn btn-success d-none">Proses</button>
                            </div>
                            <div class="mt-1 small text-success fw-bold animate-pulse" id="autoStatus">
                                <i class="fa-solid fa-bolt-lightning me-1"></i> Mode Scan Otomatis Aktif
                            </div>
                        </form>
                        <div id="scanMessage" class="alert d-none"></div>
                        <div class="mb-3">
                            <label for="cameraSelect" class="form-label fw-semibold">Pilih Kamera Scanner</label>
                            <div class="input-group">
                                <select id="cameraSelect" class="form-select">
                                    <option value="">Memuat daftar kamera...</option>
                                </select>
                                <button type="button" id="switchCameraBtn" class="btn btn-outline-secondary">Ganti</button>
                            </div>
                        </div>
                        <div class="small text-muted">
                            Kamera bisa dipakai untuk barcode 1D/2D. Untuk mode tap kartu RFID (reader HID), cukup tempel kartu lalu sistem akan kirim otomatis.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">Log Absensi Hari Ini</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="todayLogTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayLogs as $row)
                                <tr>
                                    <td>{{ $row->user->name ?? '-' }}</td>
                                    <td>{{ $row->clock_in?->format('H:i:s') }}</td>
                                    <td>{{ $row->clock_out?->format('H:i:s') ?? '-' }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($row->status) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Belum ada log hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoint = @json(route('attendance.kiosk.scan'));
    const token = @json(csrf_token());
    const messageEl = document.getElementById('scanMessage');
    const manualForm = document.getElementById('manualScanForm');
    const manualCodeInput = document.getElementById('manualCode');
    const cameraSelect = document.getElementById('cameraSelect');
    const switchCameraBtn = document.getElementById('switchCameraBtn');
    let lastScan = '';
    let scanLock = false;
    let html5QrCode = null;
    let availableCameras = [];
    let currentCameraId = '';
    let scannerStarted = false;
    let hidBuffer = '';
    let hidBufferTimer = null;
    let autoSubmitTimer = null;

    const isBackCamera = (label) => /(back|rear|environment|belakang)/i.test(String(label || ''));
    const isFrontCamera = (label) => /(front|user|depan)/i.test(String(label || ''));
    const focusManualInput = () => {
        if (!manualCodeInput) {
            return;
        }
        setTimeout(() => {
            try {
                manualCodeInput.focus({ preventScroll: true });
            } catch (_) {
                manualCodeInput.focus();
            }
        }, 60);
    };

    const showMessage = (text, success = true) => {
        messageEl.classList.remove('d-none', 'alert-success', 'alert-danger');
        messageEl.classList.add(success ? 'alert-success' : 'alert-danger');
        messageEl.textContent = text;
    };

    const prependLog = (payload) => {
        const tableBody = document.querySelector('#todayLogTable tbody');
        if (!tableBody) {
            return;
        }
        
        // Remove "Belum ada log" row if it exists
        const emptyRow = tableBody.querySelector('tr td[colspan="4"]');
        if (emptyRow) {
            tableBody.innerHTML = '';
        }

        const row = document.createElement('tr');
        row.classList.add('table-success'); // Flash success color
        const status = (payload?.status || '').toUpperCase();
        const timeIn = payload?.time || '-';
        const timeOut = payload?.clock_out || '-';
        
        row.innerHTML = `
            <td><span class="fw-bold text-dark">${payload?.name || '-'}</span></td>
            <td>${timeIn}</td>
            <td>${timeOut !== '-' ? `<span class="text-primary fw-bold">${timeOut}</span>` : '-'}</td>
            <td><span class="badge bg-secondary-subtle text-secondary">${status}</span></td>
        `;
        
        tableBody.prepend(row);
        
        // Remove success color after a few seconds
        setTimeout(() => {
            row.classList.remove('table-success');
        }, 3000);
    };

    const submitScan = async (cardCode) => {
        if (!cardCode || scanLock) {
            return;
        }
        scanLock = true;
        
        // Show temporary "Processing" state
        const originalMessage = messageEl.textContent;
        const originalClass = messageEl.className;
        showMessage('Memproses kode: ' + cardCode + '...', true);
        messageEl.classList.add('alert-warning');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ card_code: cardCode }),
            });
            const result = await response.json();
            
            if (!response.ok || !result.success) {
                showMessage(result.message || 'Proses absensi gagal.', false);
                // Optional: Error sound
                return;
            }
            
            showMessage(result.message || 'Absensi berhasil.', true);
            
            prependLog({
                name: result?.data?.name,
                time: result?.data?.time,
                status: result?.data?.status,
                clock_out: result?.action === 'clock_out' ? (result?.data?.clock_out || result?.data?.time) : '-',
            });
            
            manualCodeInput.value = '';
            
            // Optional: Success sound (beep)
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
                gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                oscillator.start();
                oscillator.stop(audioCtx.currentTime + 0.1);
            } catch (_) {}

        } catch (error) {
            showMessage('Gagal menghubungi server absensi.', false);
        } finally {
            focusManualInput();
            setTimeout(() => {
                scanLock = false;
            }, 1000);
        }
    };

    manualCodeInput.addEventListener('input', function() {
        if (autoSubmitTimer) {
            clearTimeout(autoSubmitTimer);
        }
        
        const code = this.value.trim();
        if (code === '') {
            return;
        }

        // Jika panjang input >= 4, asumsi itu barcode/RFID, tunggu sejenak lalu submit otomatis
        // 400ms adalah jeda yang pas antara selesai scan dan proses
        autoSubmitTimer = setTimeout(() => {
            submitScan(this.value.trim());
        }, 400);
    });

    manualForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const code = manualCodeInput.value.trim();
        submitScan(code);
    });

    manualCodeInput.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== 'Tab') {
            return;
        }
        const code = manualCodeInput.value.trim();
        if (code === '') {
            return;
        }
        event.preventDefault();
        submitScan(code);
    });

    document.addEventListener('keydown', function (event) {
        const target = event.target;
        const tagName = target?.tagName?.toUpperCase();
        const isTypingField = tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT' || target?.isContentEditable;
        if (isTypingField && target !== manualCodeInput) {
            return;
        }
        if (event.ctrlKey || event.altKey || event.metaKey) {
            return;
        }

        if (event.key === 'Enter' || event.key === 'Tab') {
            if (hidBuffer.trim() !== '') {
                event.preventDefault();
                submitScan(hidBuffer.trim());
                hidBuffer = '';
            }
            return;
        }

        if (event.key.length === 1) {
            hidBuffer += event.key;
            if (hidBufferTimer) {
                clearTimeout(hidBufferTimer);
            }
            hidBufferTimer = setTimeout(() => {
                hidBuffer = '';
            }, 180);
        }
    });

    document.addEventListener('click', function (event) {
        const target = event.target;
        if (target?.closest('#cameraSelect') || target?.closest('#switchCameraBtn')) {
            return;
        }
        focusManualInput();
    });

    window.addEventListener('focus', focusManualInput);
    focusManualInput();

    const startScanner = () => {
        if (typeof Html5Qrcode === 'undefined') {
            return;
        }
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('reader');
        }

        const fillCameraOptions = (devices) => {
            cameraSelect.innerHTML = '';
            devices.forEach((camera, index) => {
                const option = document.createElement('option');
                const name = camera.label || `Kamera ${index + 1}`;
                option.value = camera.id;
                option.textContent = name;
                cameraSelect.appendChild(option);
            });
        };

        const startWithCamera = async (cameraId) => {
            if (!cameraId || !html5QrCode) {
                return;
            }
            try {
                if (scannerStarted) {
                    await html5QrCode.stop();
                    scannerStarted = false;
                }
                await html5QrCode.start(
                    cameraId,
                    { fps: 10, qrbox: { width: 220, height: 140 } },
                    (decodedText) => {
                        const code = String(decodedText || '').trim();
                        if (code === '' || code === lastScan) {
                            return;
                        }
                        lastScan = code;
                        submitScan(code);
                        setTimeout(() => { lastScan = ''; }, 1200);
                    }
                );
                scannerStarted = true;
                currentCameraId = cameraId;
                cameraSelect.value = cameraId;
            } catch (error) {
                showMessage('Gagal mengaktifkan kamera scanner.', false);
            }
        };

        Html5Qrcode.getCameras().then(async (devices) => {
            if (!devices || devices.length === 0) {
                showMessage('Kamera tidak ditemukan.', false);
                return;
            }
            availableCameras = devices;
            fillCameraOptions(devices);

            const preferredBack = devices.find((camera) => isBackCamera(camera.label));
            const preferredFront = devices.find((camera) => isFrontCamera(camera.label));
            const initialCamera = preferredBack?.id || preferredFront?.id || devices[0].id;
            await startWithCamera(initialCamera);

            cameraSelect.addEventListener('change', function () {
                const selectedId = cameraSelect.value;
                if (selectedId && selectedId !== currentCameraId) {
                    startWithCamera(selectedId);
                }
            });

            switchCameraBtn.addEventListener('click', function () {
                if (availableCameras.length < 2) {
                    showMessage('Kamera lain tidak tersedia.', false);
                    return;
                }
                const currentIndex = availableCameras.findIndex((camera) => camera.id === currentCameraId);
                const nextIndex = currentIndex >= 0
                    ? (currentIndex + 1) % availableCameras.length
                    : 0;
                const nextCameraId = availableCameras[nextIndex].id;
                startWithCamera(nextCameraId);
            });
        }).catch(() => {
            showMessage('Akses kamera ditolak.', false);
        });
    };

    setTimeout(startScanner, 300);
});
</script>
@endpush
