@extends('layouts.app')

@section('content')
<style>
.table td, .table th {
    vertical-align: middle;
    white-space: nowrap;
}
.table tbody tr {
    cursor: default;
    transition: none !important;
}
.table tbody tr:hover {
    background-color: transparent !important;
}
</style>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-info">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('olt.show', $olt->id) }}" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke OLT
                    </a>
                    <h5 class="mb-0 fw-bold">
                        {{ $olt->name }} - ONUs
                        <span class="badge bg-secondary-subtle text-secondary ms-2 rounded-pill">{{ $olt->onts->count() }} {{ __('devices') }}</span>
                    </h5>
                </div>
                
                <form action="{{ route('olt.onus.sync', $olt->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-sync me-1"></i> {{ __('Sync from OLT') }}
                    </button>
                </form>
            </div>

            <div class="card-body">
                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-info-circle me-1"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">LOCATION</th>
                                <th scope="col">SERIAL / MAC</th>
                                <th scope="col">NAME</th>
                                <th scope="col">STATUS</th>
                                <th scope="col">SIGNAL</th>
                                <th scope="col">RX POWER</th>
                                <th scope="col">OLT TARGET</th>
                                <th scope="col">LAST SEEN</th>
                                <th scope="col" class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($onts as $ont)
                                <tr>
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
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('olt.onu.detail', $ont->id) }}" class="btn btn-outline-primary">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="editOnu({{ $ont->id }}, '{{ $ont->name ?? '' }}', {{ $olt->id }})">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteOnu({{ $ont->id }}, '{{ $ont->name ?? $ont->ont_id }}')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-network-wired fa-2x opacity-25"></i></div>
                                        {{ __('No ONUs found. Click "Sync from OLT" to fetch devices.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $onts->links() }}
                </div>
            </div>
        </div>
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
function editOnu(id, currentName, oltId) {
    const form = document.getElementById('editOntForm');
    form.action = '/olt/onu/' + id;
    document.getElementById('editOntName').value = currentName;
    
    const modal = new bootstrap.Modal(document.getElementById('editOntModal'));
    modal.show();
}

document.querySelector('form[action*="onus/sync"]')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Syncing...';
    }

    Swal.fire({
        title: 'Sync ONU',
        text: 'Mengambil data dari OLT...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(this.action, {
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
            btn.innerHTML = '<i class="fa-solid fa-sync me-1"></i> {{ __("Sync from OLT") }}';
        }

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sync Berhasil',
                html: `Ditemukan <b>${data.onts_found}</b> ONU dalam ${data.duration_ms}ms`,
                timer: 3000,
                showConfirmButton: false
            });
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sync Gagal',
                text: data.message || 'Terjadi kesalahan',
            });
        }
    })
    .catch(err => {
        Swal.close();
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-sync me-1"></i> {{ __("Sync from OLT") }}';
        }
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: err.message,
        });
    });
});

function deleteOnu(id, name) {
    Swal.fire({
        title: 'Hapus ONU',
        html: `Yakin ingin menghapus ONU <b>${name}</b>?<br><small class="text-danger">Data ONU akan dihapus secara permanen</small>`,
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
