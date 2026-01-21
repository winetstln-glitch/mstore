@extends('layouts.app')

@section('title', 'Kalkulator PON - MStore')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fa-solid fa-calculator me-2"></i> {{ __('Kalkulator PON (Link Budget)') }}</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Kalkulator PON') }}</li>
        </ol>
    </div>

    <div class="row">
        <!-- Input Parameters -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-sliders me-2"></i> {{ __('Parameter Link Optik') }}</h5>
                </div>
                <div class="card-body">
                    <form id="calculatorForm">
                        <!-- OLT Source -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2"><i class="fa-solid fa-server me-2"></i> Sumber Daya (OLT SFP)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tx_power" class="form-label">Daya Transmit (Tx Power)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" class="form-control" id="tx_power" value="3" required>
                                        <span class="input-group-text">dBm</span>
                                    </div>
                                    <div class="form-text">Biasanya +3 sampai +7 dBm untuk SFP C+</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="wavelength" class="form-label">Panjang Gelombang</label>
                                    <select class="form-select" id="wavelength">
                                        <option value="1310">1310 nm (Upstream)</option>
                                        <option value="1490" selected>1490 nm (Downstream)</option>
                                        <option value="1550">1550 nm (Video RF)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Passive Components -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2"><i class="fa-solid fa-network-wired me-2"></i> Komponen Pasif</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="connectors" class="form-label">Jumlah Konektor (Adapter/Patchcord)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="connectors" value="4" min="0">
                                        <span class="input-group-text">Pcs</span>
                                    </div>
                                    <div class="form-text">Loss estimasi: 0.25 - 0.5 dB/pcs</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="splices" class="form-label">Jumlah Sambungan (Splicing)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="splices" value="2" min="0">
                                        <span class="input-group-text">Titik</span>
                                    </div>
                                    <div class="form-text">Loss estimasi: 0.1 dB/titik</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="safety_margin" class="form-label">Safety Margin</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" class="form-control" id="safety_margin" value="3.0" min="0">
                                        <span class="input-group-text">dB</span>
                                    </div>
                                    <div class="form-text">Cadangan redaman (Aging/Repair)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Fiber Cable -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2"><i class="fa-solid fa-timeline me-2"></i> Kabel Fiber Optik</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="distance" class="form-label">Jarak Total (Distance)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" class="form-control" id="distance" value="1" min="0">
                                        <span class="input-group-text">km</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="cable_loss_per_km" class="form-label">Loss Kabel per KM</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="cable_loss_per_km" value="0.25" readonly>
                                        <span class="input-group-text">dB/km</span>
                                    </div>
                                    <div class="form-text">Otomatis disesuaikan berdasarkan Wavelength</div>
                                </div>
                            </div>
                        </div>

                        <!-- Splitters -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2"><i class="fa-solid fa-share-nodes me-2"></i> Splitter Ratio</h6>
                            <div id="splitter-container">
                                <div class="row g-3 mb-2 splitter-row">
                                    <div class="col-md-8">
                                        <select class="form-select splitter-select">
                                            <option value="0">Tidak Ada Splitter</option>
                                            <option value="3.7">1:2 (3.7 dB)</option>
                                            <option value="7.3">1:4 (7.3 dB)</option>
                                            <option value="10.5" selected>1:8 (10.5 dB)</option>
                                            <option value="13.7">1:16 (13.7 dB)</option>
                                            <option value="17.1">1:32 (17.1 dB)</option>
                                            <option value="20.5">1:64 (20.5 dB)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-splitter" disabled><i class="fa-solid fa-trash"></i> Hapus</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary mt-2" id="addSplitter"><i class="fa-solid fa-plus"></i> Tambah Splitter (Bertingkat)</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Result Panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 sticky-top" style="top: 80px; z-index: 900;">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-chart-pie me-2"></i> Hasil Perhitungan</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold">Estimasi Daya Terima (Rx Power)</h6>
                        <h1 class="display-4 fw-bold" id="rx_result">-24.50</h1>
                        <span class="badge bg-secondary fs-6" id="rx_unit">dBm</span>
                    </div>

                    <div class="alert alert-success d-flex align-items-center" role="alert" id="status_alert">
                        <i class="fa-solid fa-circle-check fa-2x me-3"></i>
                        <div>
                            <div class="fw-bold">SINYAL BAGUS</div>
                            <div class="small">Daya terima dalam rentang optimal (-8 s/d -27 dBm)</div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3">Rincian Redaman (Loss)</h6>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fa-solid fa-server text-muted me-2"></i> Tx Power</span>
                            <span class="fw-bold text-primary" id="detail_tx">+3.00 dBm</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fa-solid fa-timeline text-muted me-2"></i> Loss Kabel Fiber</span>
                            <span class="text-danger" id="detail_cable">-0.25 dB</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fa-solid fa-plug text-muted me-2"></i> Loss Konektor</span>
                            <span class="text-danger" id="detail_connector">-1.00 dB</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fa-solid fa-link text-muted me-2"></i> Loss Splicing</span>
                            <span class="text-danger" id="detail_splice">-0.20 dB</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fa-solid fa-share-nodes text-muted me-2"></i> Loss Splitter</span>
                            <span class="text-danger" id="detail_splitter">-10.50 dB</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-light">
                            <span class="fw-bold">Total Link Loss</span>
                            <span class="fw-bold text-danger" id="total_loss">11.95 dB</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fa-solid fa-shield-halved text-muted me-2"></i> Safety Margin</span>
                            <span class="text-warning" id="detail_margin">-3.00 dB</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Constants
    const LOSS_CONNECTOR = 0.5; // dB per connector (Max standard)
    const LOSS_SPLICE = 0.1;    // dB per splice
    
    // Elements
    const form = document.getElementById('calculatorForm');
    const wavelengthSelect = document.getElementById('wavelength');
    const cableLossInput = document.getElementById('cable_loss_per_km');
    const splitterContainer = document.getElementById('splitter-container');
    const addSplitterBtn = document.getElementById('addSplitter');
    
    // Result Elements
    const rxResult = document.getElementById('rx_result');
    const statusAlert = document.getElementById('status_alert');
    const detailTx = document.getElementById('detail_tx');
    const detailCable = document.getElementById('detail_cable');
    const detailConnector = document.getElementById('detail_connector');
    const detailSplice = document.getElementById('detail_splice');
    const detailSplitter = document.getElementById('detail_splitter');
    const detailMargin = document.getElementById('detail_margin');
    const totalLossEl = document.getElementById('total_loss');

    // Update Cable Loss based on Wavelength
    function updateCableLoss() {
        const wl = wavelengthSelect.value;
        if (wl === '1310') {
            cableLossInput.value = 0.35;
        } else if (wl === '1490') {
            cableLossInput.value = 0.25;
        } else if (wl === '1550') {
            cableLossInput.value = 0.22;
        }
        calculate();
    }

    // Add Splitter
    window.addSplitter = function(ratio = 0) {
        const row = document.createElement('div');
        row.className = 'row g-3 mb-2 splitter-row';
        row.innerHTML = `
            <div class="col-md-8">
                <select class="form-select splitter-select">
                    <option value="0" ${ratio == 0 ? 'selected' : ''}>Tidak Ada Splitter</option>
                    <option value="3.7" ${ratio == 3.7 ? 'selected' : ''}>1:2 (3.7 dB)</option>
                    <option value="7.3" ${ratio == 7.3 ? 'selected' : ''}>1:4 (7.3 dB)</option>
                    <option value="10.5" ${ratio == 10.5 ? 'selected' : ''}>1:8 (10.5 dB)</option>
                    <option value="13.7" ${ratio == 13.7 ? 'selected' : ''}>1:16 (13.7 dB)</option>
                    <option value="17.1" ${ratio == 17.1 ? 'selected' : ''}>1:32 (17.1 dB)</option>
                    <option value="20.5" ${ratio == 20.5 ? 'selected' : ''}>1:64 (20.5 dB)</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-danger w-100 remove-splitter"><i class="fa-solid fa-trash"></i> Hapus</button>
            </div>
        `;
        splitterContainer.appendChild(row);
        
        // Add event listeners to new elements
        row.querySelector('.splitter-select').addEventListener('change', calculate);
        row.querySelector('.remove-splitter').addEventListener('click', function() {
            row.remove();
            calculate();
        });
        
        calculate();
    }

    addSplitterBtn.addEventListener('click', () => addSplitter(0));

    // Load Preset Function
    window.loadPreset = function(odc, odp) {
        // Reset Form
        document.getElementById('tx_power').value = 5; // Default Standard
        document.getElementById('connectors').value = 4;
        document.getElementById('splices').value = 4; // 2 OLT-ODC, 2 ODC-ODP
        document.getElementById('distance').value = 5; // Estimasi rata-rata 5km
        
        // Clear existing splitters
        splitterContainer.innerHTML = '';

        // Add ODC Splitter
        let odcloss = 0;
        if(odc == 16) odcloss = 13.7;
        if(odc == 32) odcloss = 17.1;
        addSplitter(odcloss);

        // Add ODP Splitter
        let odploss = 0;
        if(odp == 4) odploss = 7.3;
        if(odp == 8) odploss = 10.5;
        addSplitter(odploss);

        // Notify User
        Swal.fire({
            icon: 'success',
            title: 'Standarisasi Dimuat',
            text: `Skenario ODC 1:${odc} + ODP 1:${odp} telah diterapkan.`,
            timer: 1500,
            showConfirmButton: false
        });

        calculate();
    }

    // Main Calculation Function
    function calculate() {
        // Get Inputs
        const txPower = parseFloat(document.getElementById('tx_power').value) || 0;
        const connectors = parseFloat(document.getElementById('connectors').value) || 0;
        const splices = parseFloat(document.getElementById('splices').value) || 0;
        const distance = parseFloat(document.getElementById('distance').value) || 0;
        const lossPerKm = parseFloat(cableLossInput.value) || 0;
        const safetyMargin = parseFloat(document.getElementById('safety_margin').value) || 0;

        // Calculate Losses
        const cableLoss = distance * lossPerKm;
        const connectorLoss = connectors * LOSS_CONNECTOR;
        const spliceLoss = splices * LOSS_SPLICE;
        
        let splitterLoss = 0;
        document.querySelectorAll('.splitter-select').forEach(select => {
            splitterLoss += parseFloat(select.value) || 0;
        });

        const totalLinkLoss = cableLoss + connectorLoss + spliceLoss + splitterLoss;
        const totalLossWithMargin = totalLinkLoss + safetyMargin;
        const rxPower = txPower - totalLossWithMargin;

        // Update UI
        rxResult.textContent = rxPower.toFixed(2);
        
        // Status Logic (Standard GPON Class B+ / C+)
        // Usually Sensitivity is around -28 dBm, Overload -8 dBm
        statusAlert.className = 'alert d-flex align-items-center';
        statusAlert.innerHTML = '';
        
        let icon = '';
        let title = '';
        let desc = '';
        let alertClass = '';

        if (rxPower > -8) {
            alertClass = 'alert-warning';
            icon = '<i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>';
            title = 'SINYAL TERLALU KUAT (HIGH)';
            desc = 'Berisiko merusak optik penerima ONU (Overload > -8 dBm). Gunakan Attenuator.';
        } else if (rxPower >= -27) {
            alertClass = 'alert-success';
            icon = '<i class="fa-solid fa-circle-check fa-2x me-3"></i>';
            title = 'SINYAL BAGUS (OPTIMAL)';
            desc = 'Sinyal dalam rentang kerja optimal (-8 s/d -27 dBm).';
        } else if (rxPower >= -30) {
            alertClass = 'alert-warning';
            icon = '<i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>';
            title = 'SINYAL LEMAH (WARNING)';
            desc = 'Mungkin masih connect, tapi rawan packet loss/CRC error (-27 s/d -30 dBm).';
        } else {
            alertClass = 'alert-danger';
            icon = '<i class="fa-solid fa-circle-xmark fa-2x me-3"></i>';
            title = 'SINYAL HILANG / KRITIS (LOS)';
            desc = 'Kemungkinan besar ONU tidak akan connect (LOS < -30 dBm).';
        }
        
        statusAlert.classList.add(alertClass);
        statusAlert.innerHTML = `${icon}<div><div class="fw-bold">${title}</div><div class="small">${desc}</div></div>`;

        // Update Details
        detailTx.textContent = (txPower > 0 ? '+' : '') + txPower.toFixed(2) + ' dBm';
        detailCable.textContent = '-' + cableLoss.toFixed(2) + ' dB';
        detailConnector.textContent = '-' + connectorLoss.toFixed(2) + ' dB';
        detailSplice.textContent = '-' + spliceLoss.toFixed(2) + ' dB';
        detailSplitter.textContent = '-' + splitterLoss.toFixed(2) + ' dB';
        detailMargin.textContent = '-' + safetyMargin.toFixed(2) + ' dB';
        totalLossEl.textContent = totalLinkLoss.toFixed(2) + ' dB';
    }

    // Event Listeners
    const inputs = document.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', calculate);
        input.addEventListener('change', calculate);
    });

    wavelengthSelect.addEventListener('change', updateCableLoss);

    // Initial Run
    calculate();
});
</script>
@endpush
@endsection
