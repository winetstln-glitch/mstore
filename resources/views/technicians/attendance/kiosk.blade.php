@extends('layouts.app')

@push('styles')
<style>
    .animate-pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    #reader { width: 100%; background: #f8f9fa; border-radius: 8px; overflow: hidden; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 border-top border-4 border-success mb-4">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div id="reader"></div>
                        <div class="input-group mt-2">
                            <select id="cameraSelect" class="form-select form-select-sm"></select>
                            <button id="switchCameraBtn" class="btn btn-sm btn-outline-secondary">Ganti</button>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <h5 class="fw-bold">Kiosk Absensi <span class="badge bg-success float-end">Online</span></h5>
                        <hr>
                        <form id="manualScanForm">
                            <label class="form-label fw-bold">Scan ID Card / Input Kode</label>
                            <input type="text" id="manualCode" class="form-control form-control-lg border-success" placeholder="Tempel kartu atau scan..." autofocus autocomplete="off">
                            <div id="autoStatus" class="small text-success mt-1 animate-pulse">
                                <i class="fa-solid fa-bolt"></i> Sensor Siap...
                            </div>
                        </form>

                        <div id="scanMessage" class="alert d-none mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-history me-2"></i>Log Absensi Hari Ini</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" id="todayLogTable">
                    <thead class="table-light">
                        <tr><th>Nama</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($todayLogs as $row)
                        <tr>
                            <td>{{ $row->user->name ?? '-' }}</td>
                            <td>{{ $row->clock_in?->format('H:i:s') }}</td>
                            <td>{{ $row->clock_out?->format('H:i:s') ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper($row->status) }}</span></td>
                        </tr>
                        @empty
                        <tr id="emptyRow"><td colspan="4" class="text-center py-4 text-muted">Belum ada aktivitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = {
        input: document.getElementById('manualCode'),
        msg: document.getElementById('scanMessage'),
        table: document.querySelector('#todayLogTable tbody'),
        camSelect: document.getElementById('cameraSelect')
    };

    let scanLock = false;
    let html5QrCode = null;

    // --- Core Logic: Submit Data ---
    const submitScan = async (code) => {
        if (!code || scanLock) return;
        scanLock = true;
        
        // Tampilkan loading swal
        Swal.fire({
            title: 'Memproses...',
            text: `ID: ${code}`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const resp = await fetch("{{ route('attendance.kiosk.scan') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                body: JSON.stringify({ card_code: code })
            });
            
            const res = await resp.json();
            
            if (res.success) {
                updateTable(res.data, res.action);
                beep(880);
                
                // Popup Sukses
                Swal.fire({
                    icon: 'success',
                    title: 'Absensi Berhasil!',
                    html: `<strong>${res.data.name}</strong><br>${res.message}`,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            } else {
                // Popup Gagal
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: res.message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Koneksi terputus atau terjadi kesalahan server.',
                timer: 3000,
                showConfirmButton: false
            });
        } finally {
            el.input.value = '';
            el.input.focus();
            setTimeout(() => { scanLock = false; }, 1500);
        }
    };

    // --- UI Helpers ---
    const updateTable = (d, action) => {
        const empty = document.getElementById('emptyRow');
        if (empty) empty.remove();

        const row = el.table.insertRow(0);
        row.className = 'table-success';
        row.innerHTML = `
            <td><strong>${d.name}</strong></td>
            <td>${d.time}</td>
            <td>${action === 'clock_out' ? d.time : '-'}</td>
            <td><span class="badge bg-light text-dark border">${d.status.toUpperCase()}</span></td>
        `;
        setTimeout(() => row.classList.remove('table-success'), 2000);
    };

    const beep = (freq) => {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = freq; gain.gain.value = 0.1;
        osc.start(); osc.stop(ctx.currentTime + 0.1);
    };

    // --- Event Listeners ---
    // Input manual & RFID Scanner (HID)
    el.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') submitScan(el.input.value.trim());
    });

    // Auto-focus tetap terjaga
    document.addEventListener('click', () => el.input.focus());

    // --- Kamera / QR Scanner ---
    const initScanner = async () => {
        html5QrCode = new Html5Qrcode("reader");
        const devices = await Html5Qrcode.getCameras();
        
        if (devices.length) {
            devices.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id; opt.text = d.label;
                el.camSelect.add(opt);
            });

            const start = (id) => {
                html5QrCode.start(id, { fps: 10, qrbox: 200 }, (txt) => submitScan(txt));
            };

            start(devices[0].id);
            el.camSelect.onchange = (e) => start(e.target.value);
            document.getElementById('switchCameraBtn').onclick = () => {
                const next = (el.camSelect.selectedIndex + 1) % devices.length;
                el.camSelect.selectedIndex = next;
                start(devices[next].id);
            };
        }
    };

    setTimeout(initScanner, 500);
});
</script>
@endpush
