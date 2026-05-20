@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fa-solid fa-network-wired"></i> Detail ONU: {{ $ont->name ?? $ont->ont_id }}
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('olt.show', $ont->olt_id) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke OLT
            </a>
            <button type="button" class="btn btn-danger" 
                    onclick="deleteOnu({{ $ont->id }}, '{{ $ont->name ?? $ont->ont_id }}', {{ $ont->olt_id }})">
                <i class="fa-solid fa-trash"></i> Hapus ONU
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Umum</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">OLT</td>
                            <td>{{ $ont->olt->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">PON Port</td>
                            <td>{{ $ont->port?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">ONT ID</td>
                            <td>{{ $ont->ont_id }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Nama</td>
                            <td>{{ $ont->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Status</td>
                            <td>
                                <span class="badge bg-{{ $ont->oper_status == 'online' ? 'success' : 'danger' }}">
                                    {{ ucfirst($ont->oper_status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Spesifikasi Perangkat</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Vendor</td>
                            <td>{{ $ont->vendor ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Model</td>
                            <td>{{ $ont->model ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Firmware</td>
                            <td>{{ $ont->firmware_version ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Serial Number</td>
                            <td class="font-monospace">{{ $ont->serial_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">MAC Address</td>
                            <td class="font-monospace">{{ $ont->mac_address ?? $ont->mac ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Informasi Optik</h5>
        </div>
        <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <td class="fw-bold">Rx Power</td>
                <td>
                    @if($ont->rx_power !== null)
                            @php
                                $rxClass = 'text-muted';
                                if ($ont->rx_power > -23) {
                                    $rxClass = 'text-success';
                                } elseif ($ont->rx_power > -25) {
                                    $rxClass = 'text-warning';
                                } elseif ($ont->rx_power > -29) {
                                    $rxClass = 'text-danger'; // merah muda (pink)
                                } else {
                                    $rxClass = 'text-danger-emphasis'; // merah tua
                                }
                            @endphp
                            <span class="{{ $rxClass }}">
                                <i class="fa-solid fa-signal me-1"></i>{{ number_format($ont->rx_power, 2) }} dBm
                            </span>
                        @else
                            -
                        @endif
                </td>
            </tr>
            <tr>
                <td class="fw-bold">Tx Power</td>
                <td>
                    @if($ont->tx_power !== null)
                        <span class="text-success">
                            <i class="fa-solid fa-signal me-1"></i>{{ number_format($ont->tx_power, 2) }} dBm
                        </span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td class="fw-bold">Suhu</td>
                <td>{{ $ont->temperature !== null ? number_format($ont->temperature, 2) . ' °C' : '-' }}</td>
            </tr>
            <tr>
                <td class="fw-bold">Voltase</td>
                <td>{{ $ont->voltage !== null ? number_format($ont->voltage, 2) . ' V' : '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Riwayat</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2"><span class="fw-bold">Terakhir Diperbarui:</span> {{ $ont->last_updated ? $ont->last_updated->format('d/m/Y H:i:s') : '-' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><span class="fw-bold">Terakhir Polling:</span> {{ $ont->last_polled_at ? $ont->last_polled_at->format('d/m/Y H:i:s') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteOnu(id, name, oltId) {
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
            form.action = '/olt/' + oltId + '/onus/' + id;
            
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
