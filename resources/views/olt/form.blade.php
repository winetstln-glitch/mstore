{{-- resources/views/olt/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($olt) ? 'Edit OLT - ' . $olt->name : 'Tambah OLT Baru')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">{{ isset($olt) ? 'Edit OLT' : 'Tambah OLT Baru' }}</h4>
            <p class="text-muted small mb-0">Konfigurasi koneksi SNMP ke server OLT</p>
        </div>
    </div>

    <form action="{{ isset($olt) ? route('olt.update', $olt->id) : route('olt.store') }}" 
          method="POST" id="oltForm">
        @csrf
        @if(isset($olt)) @method('PUT') @endif

        {{-- Tab Navigation --}}
        <ul class="nav nav-tabs mb-4" id="oltTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" 
                        data-bs-target="#basic" type="button" role="tab">
                    <i class="fa-solid fa-info-circle me-1"></i> Informasi Dasar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="snmp-tab" data-bs-toggle="tab" 
                        data-bs-target="#snmp" type="button" role="tab">
                    <i class="fa-solid fa-network-wired me-1"></i> SNMP Configuration
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="polling-tab" data-bs-toggle="tab" 
                        data-bs-target="#polling" type="button" role="tab">
                    <i class="fa-solid fa-clock me-1"></i> Polling Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="test-tab" data-bs-toggle="tab" 
                        data-bs-target="#test" type="button" role="tab">
                    <i class="fa-solid fa-vial me-1"></i> Test Connection
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Tab 1: Informasi Dasar --}}
            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama OLT <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $olt->name ?? '') }}" required
                                       placeholder="Contoh: OLT-Central-01">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IP Address <span class="text-danger">*</span></label>
                                <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror"
                                       value="{{ old('ip_address', $olt->ip_address ?? '') }}" required
                                       placeholder="10.0.0.1">
                                @error('ip_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor" class="form-select @error('vendor') is-invalid @enderror" required>
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach(['hsgq' => 'HSGQ', 'cdata' => 'C-Data', 'huawei' => 'Huawei', 'zte' => 'ZTE', 
                                               'fiberhome' => 'FiberHome', 'nokia' => 'Nokia/Alcatel',
                                               'cisco' => 'Cisco', 'calix' => 'Calix', 'other' => 'Lainnya'] as $val => $label)
                                        <option value="{{ $val }}" {{ old('vendor', $olt->vendor ?? '') == $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vendor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Model</label>
                                <input type="text" name="model" class="form-control"
                                       value="{{ old('model', $olt->model ?? '') }}"
                                       placeholder="HSGQ-G02ID / MA5608T / C320">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lokasi</label>
                                <input type="text" name="location" class="form-control"
                                       value="{{ old('location', $olt->location ?? '') }}"
                                       placeholder="DC Utama / Rack ODF-01">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: SNMP Configuration --}}
            <div class="tab-pane fade" id="snmp" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">SNMP Version</label>
                                <select name="snmp_version" class="form-select" id="snmpVersion">
                                    <option value="v2c" {{ old('snmp_version', $olt->snmp_version ?? 'v2c') == 'v2c' ? 'selected' : '' }}>SNMP v2c</option>
                                    <option value="v1" {{ old('snmp_version', $olt->snmp_version ?? '') == 'v1' ? 'selected' : '' }}>SNMP v1</option>
                                    <option value="v3" {{ old('snmp_version', $olt->snmp_version ?? '') == 'v3' ? 'selected' : '' }}>SNMP v3</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Read Community <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="read_community" id="readCommunity"
                                           class="form-control @error('read_community') is-invalid @enderror"
                                           value="{{ old('read_community', $olt->read_community ?? '') }}" required>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="toggleField('readCommunity')">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    @error('read_community') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Write Community</label>
                                <div class="input-group">
                                    <input type="password" name="write_community" id="writeCommunity"
                                           class="form-control"
                                           value="{{ old('write_community', $olt->write_community ?? '') }}">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="toggleField('writeCommunity')">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Kosongkan jika hanya monitoring</small>
                            </div>

                            {{-- SNMPv3 Config --}}
                            <div class="col-12 mt-3" id="snmpv3Config" 
                                 style="display: {{ old('snmp_version', $olt->snmp_version ?? '') === 'v3' ? 'block' : 'none' }}">
                                <div class="p-3 bg-warning bg-opacity-10 rounded-3">
                                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-shield me-1"></i> SNMPv3 Configuration</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="snmpv3_username" class="form-control"
                                                   value="{{ old('snmpv3_username', $olt->snmpv3_config['username'] ?? '') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Auth Protocol</label>
                                            <select name="snmpv3_auth_protocol" class="form-select">
                                                <option value="MD5">MD5</option>
                                                <option value="SHA">SHA</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Auth Password</label>
                                            <input type="password" name="snmpv3_auth_password" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Privacy Protocol</label>
                                            <select name="snmpv3_priv_protocol" class="form-select">
                                                <option value="">None</option>
                                                <option value="DES">DES</option>
                                                <option value="AES">AES</option>
                                                <option value="AES192">AES192</option>
                                                <option value="AES256">AES256</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Privacy Password</label>
                                            <input type="password" name="snmpv3_priv_password" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Polling Settings --}}
            <div class="tab-pane fade" id="polling" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Poll Interval (detik)</label>
                                <input type="number" name="poll_interval" class="form-control"
                                       value="{{ old('poll_interval', $olt->poll_interval ?? 300) }}"
                                       min="30" max="86400">
                                <small class="text-muted">Default: 300 (5 menit), Minimal: 30</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SNMP Timeout (detik)</label>
                                <input type="number" name="snmp_timeout" class="form-control"
                                       value="{{ old('snmp_timeout', $olt->snmp_timeout ?? 10) }}"
                                       min="1" max="60">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SNMP Retries</label>
                                <input type="number" name="snmp_retries" class="form-control"
                                       value="{{ old('snmp_retries', $olt->snmp_retries ?? 2) }}"
                                       min="0" max="10">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" 
                                           id="isActive" value="1"
                                           {{ old('is_active', $olt->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Aktifkan polling otomatis</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 4: Test Connection --}}
            <div class="tab-pane fade" id="test" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <div class="py-4" id="testResult">
                            <i class="fa-solid fa-vial fa-4x text-muted mb-3"></i>
                            <h5>Test Koneksi SNMP</h5>
                            <p class="text-muted small mb-3">Isi semua field di tab Informasi Dasar dan SNMP, lalu klik tombol di bawah</p>
                            <button type="button" class="btn btn-primary btn-lg px-5" 
                                    onclick="testConnection()">
                                <i class="fa-solid fa-play me-2"></i> Test Connection
                            </button>
                        </div>
                        <div id="testLoading" class="py-4" style="display: none;">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="mb-0">Testing koneksi ke OLT...</p>
                        </div>
                        <div id="testSuccess" class="py-4" style="display: none;">
                            <i class="fa-solid fa-check-circle fa-4x text-success mb-3"></i>
                            <h5 class="text-success">Koneksi Berhasil!</h5>
                            <div id="testInfo" class="mt-3 text-start small"></div>
                        </div>
                        <div id="testError" class="py-4" style="display: none;">
                            <i class="fa-solid fa-times-circle fa-4x text-danger mb-3"></i>
                            <h5 class="text-danger">Koneksi Gagal</h5>
                            <p id="testErrorMessage" class="text-muted"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Submit --}}
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('olt.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fa-solid fa-save me-1"></i> 
                {{ isset($olt) ? 'Update OLT' : 'Simpan OLT' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Toggle SNMPv3 config visibility
document.getElementById('snmpVersion').addEventListener('change', function() {
    document.getElementById('snmpv3Config').style.display = this.value === 'v3' ? 'block' : 'none';
});

// Toggle password visibility
function toggleField(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Test Connection
function testConnection() {
    const data = {
        name: document.querySelector('input[name="name"]').value,
        ip_address: document.querySelector('input[name="ip_address"]').value,
        vendor: document.querySelector('select[name="vendor"]').value,
        read_community: document.querySelector('input[name="read_community"]').value,
        write_community: document.querySelector('input[name="write_community"]').value,
        snmp_version: document.getElementById('snmpVersion').value,
    };

    if (!data.ip_address || !data.read_community) {
        Swal.fire({ icon: 'warning', title: 'Lengkapi Data', text: 'IP Address dan Read Community wajib diisi' });
        return;
    }

    document.getElementById('testResult').style.display = 'none';
    document.getElementById('testLoading').style.display = 'block';
    document.getElementById('testSuccess').style.display = 'none';
    document.getElementById('testError').style.display = 'none';

    fetch('{{ route("olt.test_connection") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('testLoading').style.display = 'none';
        
        if (data.connected) {
            document.getElementById('testSuccess').style.display = 'block';
            let html = '<table class="table table-sm mb-0">';
            for (const [key, value] of Object.entries(data.info || {})) {
                if (value) {
                    html += `<tr><td class="fw-medium">${key}</td><td>${value}</td></tr>`;
                }
            }
            html += '</table>';
            document.getElementById('testInfo').innerHTML = html;
        } else {
            document.getElementById('testError').style.display = 'block';
            document.getElementById('testErrorMessage').textContent = data.message || 'Tidak dapat terhubung ke OLT';
        }
    })
    .catch(err => {
        document.getElementById('testLoading').style.display = 'none';
        document.getElementById('testError').style.display = 'block';
        document.getElementById('testErrorMessage').textContent = err.message;
    });
}

// Auto-validate form before submit
document.getElementById('oltForm').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        document.querySelector('[data-bs-target="#basic"]').click();
    }
});
</script>
@endpush
