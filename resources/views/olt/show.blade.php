{{-- resources/views/olt/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail OLT - ' . $olt->name)

@section('content')
<style>
.table td, .table th {
    vertical-align: middle;
    white-space: nowrap;
}
.ont-row {
    cursor: default;
    transition: none !important;
}
.ont-row:hover {
    background-color: transparent !important;
}
</style>
<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0">{{ $olt->name }}</h4>
                <span class="text-muted small">
                    <code>{{ $olt->ip_address }}</code> &middot;
                    <span class="badge bg-secondary">{{ strtoupper($olt->vendor) }}</span>
                    @if($olt->model) &middot; {{ $olt->model }} @endif
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 text-muted small">Pilih OLT:</label>
            <select id="selectOlt" class="form-select form-select-sm" style="min-width: 200px;">
                @foreach($allOlts as $o)
                    <option value="{{ $o->id }}" {{ $o->id === $olt->id ? 'selected' : '' }}>
                        {{ $o->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('olt.onus.filter', $olt->id) }}" class="btn btn-info text-white">
                <i class="fa-solid fa-list me-1"></i> Daftar ONU
            </a>
            <button class="btn btn-success" onclick="pollOlt({{ $olt->id }})">
                <i class="fa-solid fa-rotate me-1"></i> Polling
            </button>
            <a href="{{ route('olt.edit', $olt->id) }}" class="btn btn-warning">
                <i class="fa-solid fa-pen me-1"></i> Edit
            </a>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="row g-3 mb-4">
        {{-- Status OLT --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle 
                            {{ $olt->status === 'online' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10">
                            <i class="fa-solid fa-server fa-lg 
                                {{ $olt->status === 'online' ? 'text-success' : 'text-secondary' }}"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">Status OLT</p>
                            <h5 class="fw-bold mb-0">
                                @if($olt->status === 'online')
                                    <span class="text-success">Online</span>
                                @else
                                    <span class="text-secondary">Offline</span>
                                @endif
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Uptime --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle bg-info bg-opacity-10">
                            <i class="fa-regular fa-clock fa-lg text-info"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">Uptime</p>
                            <h5 class="fw-bold mb-0 small">
                                {{ $olt->uptime ?? '-' }}
                            </h5>
                            @if($olt->last_online_at)
                                <small class="text-muted">Sejak {{ $olt->last_online_at->diffForHumans() }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ONU Stats --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle bg-primary bg-opacity-10">
                            <i class="fa-solid fa-wifi fa-lg text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">ONU Terdaftar</p>
                            <h5 class="fw-bold mb-0">{{ $stats['total_onts'] }}</h5>
                            <small class="text-success">{{ $stats['online_onts'] }} Online</small>
                            <small class="text-danger ms-1">{{ $stats['offline_onts'] }} Offline</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CPU / Memory --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle bg-warning bg-opacity-10">
                            <i class="fa-solid fa-microchip fa-lg text-warning"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">CPU / Memory</p>
                            <h5 class="fw-bold mb-0">
                                @if($olt->cpu_usage !== null)
                                    {{ $olt->cpu_usage }}%
                                @else
                                    -
                                @endif
                                /
                                @if($olt->memory_usage !== null)
                                    {{ $olt->memory_usage }}%
                                @else
                                    -
                                @endif
                            </h5>
                            @if($olt->temperature !== null)
                                <small class="text-muted">{{ $olt->temperature }}&deg;C</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel ONU --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between py-3">
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-list me-2"></i> Daftar ONU Terdaftar
            </h5>
            <div class="d-flex gap-2">
                <input type="text" id="searchOnt" class="form-control form-control-sm" 
                       placeholder="Cari ONU..." style="width: 200px;">
                <select id="filterStatus" class="form-select form-select-sm" style="width: 130px;">
                    <option value="">Semua Status</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="dying_gasp">Dying Gasp</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="ontTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3">LOCATION</th>
                            <th class="py-3">SERIAL / MAC</th>
                            <th class="py-3">NAME</th>
                            <th class="py-3">STATUS</th>
                            <th class="py-3">SIGNAL</th>
                            <th class="py-3">RX POWER</th>
                            <th class="py-3">OLT TARGET</th>
                            <th class="py-3">LAST SEEN</th>
                            <th class="pe-3 py-3 text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($onts as $index => $ont)
                        <tr class="ont-row" 
                            data-status="{{ $ont->oper_status }}"
                            data-search="{{ strtolower($ont->ont_id . ' ' . ($ont->name ?? '') . ' ' . ($ont->vendor ?? '') . ' ' . ($ont->model ?? '') . ' ' . ($ont->serial_number ?? '') . ' ' . ($ont->mac_address ?? '')) }}">
                            <td class="ps-3">
                                <div class="fw-medium">{{ $ont->port->name ?? '-' }}</div>
                            </td>
                            <td>
                                @if($ont->serial_number || $ont->mac_address)
                                    <div class="fw-medium font-monospace text-sm">{{ $ont->serial_number ?? $ont->mac_address }}</div>
                                    @if($ont->vendor)
                                        <div class="text-muted text-xs">{{ $ont->vendor }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="fw-medium">{{ $ont->name ?? '-' }}</td>
                            <td>
                                @if($ont->oper_status === 'online')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        <i class="fa-solid fa-circle-check me-1"></i> online
                                    </span>
                                @elseif($ont->oper_status === 'dying_gasp')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> dying_gasp
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> offline
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($ont->rx_power !== null)
                                    @php
                                        $signalLabel = 'Poor';
                                        $signalIcon = 'fa-circle-exclamation';
                                        $signalClass = 'text-danger';
                                        if ($ont->rx_power > -23) {
                                            $signalLabel = 'Excellent';
                                            $signalIcon = 'fa-star';
                                            $signalClass = 'text-success';
                                        } elseif ($ont->rx_power > -25) {
                                            $signalLabel = 'Good';
                                            $signalIcon = 'fa-star';
                                            $signalClass = 'text-info';
                                        } elseif ($ont->rx_power > -29) {
                                            $signalLabel = 'Fair';
                                            $signalIcon = 'fa-star-half-stroke';
                                            $signalClass = 'text-warning';
                                        }
                                    @endphp
                                    <span class="{{ $signalClass }}">
                                        <i class="fa-solid {{ $signalIcon }} me-1"></i>{{ $signalLabel }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($ont->rx_power !== null)
                                    @php
                                        $rxClass = 'text-muted';
                                        if ($ont->rx_power > -23) {
                                            $rxClass = 'text-success';
                                        } elseif ($ont->rx_power > -25) {
                                            $rxClass = 'text-warning';
                                        } elseif ($ont->rx_power > -29) {
                                            $rxClass = 'text-danger';
                                        } else {
                                            $rxClass = 'text-danger-emphasis';
                                        }
                                    @endphp
                                    <span class="{{ $rxClass }}">
                                        {{ number_format($ont->rx_power, 2) }} dBm
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $olt->name }}</td>
                            <td class="text-muted small">
                                {{ $ont->last_polled_at ? $ont->last_polled_at->format('n/j/Y, g:i:s A') : '-' }}
                            </td>
                            <td class="pe-3 text-end">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="showOntDetail({{ $ont->id }})" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" 
                                        onclick="editOnt({{ $ont->id }}, '{{ $ont->name ?? '' }}', {{ $olt->id }})" title="Edit Nama">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteOnt({{ $ont->id }}, '{{ $ont->name ?? $ont->ont_id }}', {{ $olt->id }})" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-wifi fa-3x mb-3 text-secondary"></i>
                                <p class="mb-0">Belum ada ONU terdaftar</p>
                                <small>Lakukan polling untuk mendapatkan data ONU</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Port Stats --}}
    <div class="row g-3">
        @foreach($ports as $port)
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $port->name }}</h6>
                            <span class="badge bg-{{ $port->oper_status === 'up' ? 'success' : 'secondary' }}">
                                {{ strtoupper($port->oper_status) }}
                            </span>
                        </div>
                        <span class="badge bg-{{ $port->type === 'pon' ? 'info' : ($port->type === 'xge' ? 'warning' : 'secondary') }}">
                            {{ strtoupper($port->type) }}
                        </span>
                    </div>
                    <hr class="my-2">
                    <div class="small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">RX</span>
                            <span>{{ formatBytes($port->rx_bytes) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">TX</span>
                            <span>{{ formatBytes($port->tx_bytes) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Edit ONU -->
<div class="modal fade" id="editOntModal" tabindex="-1" aria-labelledby="editOntModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOntModalLabel">Edit Nama ONU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editOntForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editOntName" class="form-label">Nama ONU</label>
                        <input type="text" class="form-control" id="editOntName" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Select OLT dropdown
document.getElementById('selectOlt')?.addEventListener('change', function() {
    if (this.value) {
        window.location.href = '/olt/' + this.value;
    }
});

// Search & Filter ONU Table
document.getElementById('searchOnt')?.addEventListener('input', filterOnts);
document.getElementById('filterStatus')?.addEventListener('change', filterOnts);

function filterOnts() {
    const search = document.getElementById('searchOnt')?.value.toLowerCase() || '';
    const status = document.getElementById('filterStatus')?.value || '';
    
    document.querySelectorAll('.ont-row').forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchStatus = !status || row.dataset.status === status;
        row.style.display = matchSearch && matchStatus ? '' : 'none';
    });
}

function pollOlt(id) {
    const btn = event?.target?.closest('button');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Polling...';
    }

    Swal.fire({
        title: 'Polling OLT',
        text: 'Mengambil data dari OLT...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`/olt/${id}/poll`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate me-1"></i> Polling';
        }

        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Polling Berhasil',
                html: `Ditemukan <b>${data.onts_found}</b> ONU dalam ${data.duration_ms}ms`,
                timer: 3000,
                showConfirmButton: false
            });
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Polling Gagal',
                text: data.error || 'Terjadi kesalahan koneksi ke OLT',
            });
        }
    })
    .catch(err => {
        Swal.close();
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate me-1"></i> Polling';
        }
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: err.message,
        });
    });
}

function showOntDetail(id) {
    window.location.href = `/olt/onu/${id}/detail`;
}

function rebootOnt(id, ontId) {
    Swal.fire({
        title: 'Reboot ONU',
        html: `Yakin ingin me-reboot ONU <b>${ontId}</b>?<br><small class="text-warning">Koneksi pelanggan akan terputus sementara</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reboot',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#f59e0b',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Mengirim perintah...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(`/olt/onu/${id}/reboot`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Reboot Berhasil' : 'Reboot Gagal',
                    text: data.message,
                });
            })
            .catch(err => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message,
                });
            });
        }
    });
}

function editOnt(id, currentName, oltId) {
    const form = document.getElementById('editOntForm');
    form.action = '/olt/onu/' + id;
    document.getElementById('editOntName').value = currentName;
    
    const modal = new bootstrap.Modal(document.getElementById('editOntModal'));
    modal.show();
}

function deleteOnt(id, ontId, oltId) {
    Swal.fire({
        title: 'Hapus ONU',
        html: `Yakin ingin menghapus ONU <b>${ontId}</b>?<br><small class="text-danger">Data ONU akan dihapus secara permanen</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/olt/onu/' + id;
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrfInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush