@extends('layouts.app')

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
                            <label class="form-label fw-semibold">Input Kode Manual</label>
                            <div class="input-group">
                                <input type="text" id="manualCode" class="form-control" placeholder="Kode ID Card / Username">
                                <button type="submit" class="btn btn-success">Proses</button>
                            </div>
                        </form>
                        <div id="scanMessage" class="alert d-none"></div>
                        <div class="small text-muted">
                            Kamera bisa dipakai untuk barcode 1D/2D. Jika gagal scan, masukkan kode manual.
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

@push('styles')
<style>
    [data-bs-theme="dark"] #reader {
        background: #0f172a;
        border-color: #334155 !important;
    }

    [data-bs-theme="dark"] #manualCode {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] #manualCode::placeholder {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .card-header,
    [data-bs-theme="dark"] .card-body,
    [data-bs-theme="dark"] .card-body .small,
    [data-bs-theme="dark"] .card-body .form-label,
    [data-bs-theme="dark"] .card-body h5,
    [data-bs-theme="dark"] .card-body h6 {
        color: #e2e8f0 !important;
    }

    [data-bs-theme="dark"] .alert-info {
        background: rgba(30, 64, 175, 0.25);
        border-color: rgba(96, 165, 250, 0.4);
        color: #dbeafe;
    }

    [data-bs-theme="dark"] #todayLogTable {
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] #todayLogTable thead.table-light th {
        background: #1e293b;
        color: #cbd5e1;
        border-color: #334155;
    }

    [data-bs-theme="dark"] #todayLogTable tbody td {
        border-color: #334155;
    }

    [data-bs-theme="dark"] #todayLogTable .text-muted {
        color: #94a3b8 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoint = @json(route('attendance.kiosk.scan'));
    const token = @json(csrf_token());
    const messageEl = document.getElementById('scanMessage');
    const manualForm = document.getElementById('manualScanForm');
    const manualCodeInput = document.getElementById('manualCode');
    let lastScan = '';
    let scanLock = false;

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
        const row = document.createElement('tr');
        const status = (payload?.status || '').toUpperCase();
        const time = payload?.time || '-';
        row.innerHTML = `<td>${payload?.name || '-'}</td><td>${time}</td><td>${payload?.clock_out || '-'}</td><td><span class="badge bg-secondary-subtle text-secondary">${status}</span></td>`;
        tableBody.prepend(row);
    };

    const submitScan = async (cardCode) => {
        if (!cardCode || scanLock) {
            return;
        }
        scanLock = true;
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
                return;
            }
            showMessage(result.message || 'Absensi berhasil.', true);
            prependLog({
                name: result?.data?.name,
                time: result?.data?.time,
                status: result?.data?.status,
                clock_out: result?.action === 'clock_out' ? result?.data?.time : '-',
            });
            manualCodeInput.value = '';
        } catch (error) {
            showMessage('Gagal menghubungi server absensi.', false);
        } finally {
            setTimeout(() => {
                scanLock = false;
            }, 800);
        }
    };

    manualForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const code = manualCodeInput.value.trim();
        submitScan(code);
    });

    const startScanner = () => {
        if (typeof Html5Qrcode === 'undefined') {
            return;
        }
        const html5QrCode = new Html5Qrcode('reader');
        Html5Qrcode.getCameras().then((devices) => {
            if (!devices || devices.length === 0) {
                showMessage('Kamera tidak ditemukan.', false);
                return;
            }
            const cameraId = devices[0].id;
            html5QrCode.start(
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
            ).catch(() => {
                showMessage('Gagal mengaktifkan kamera scanner.', false);
            });
        }).catch(() => {
            showMessage('Akses kamera ditolak.', false);
        });
    };

    setTimeout(startScanner, 300);
});
</script>
@endpush
