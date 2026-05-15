@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-server text-primary fs-4"></i>
                    <h5 class="mb-0 fw-bold">{{ $olt->name }}</h5>
                    <span class="badge {{ $olt->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                        {{ $olt->is_active ? __('Active') : __('Inactive') }}
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="syncOLT()" class="btn btn-primary">
                        <i class="fa-solid fa-sync-alt me-1"></i> {{ __('Sync OLT Data') }}
                    </button>
                    <a href="{{ route('olt.edit', $olt) }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('Edit') }}
                    </a>
                    <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary-subtle border-0 h-100 cursor-pointer" style="border-left: 4px solid #3b82f6;">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="bg-white rounded-2 p-3">
                                    <i class="fa-solid fa-microchip text-primary fs-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-primary-emphasis text-uppercase small fw-bold mb-0">{{ __('Total ONUs') }}</h6>
                                    <h2 class="display-6 fw-bold text-primary mb-0">{{ $totalOnus }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success-subtle border-0 h-100 cursor-pointer" style="border-left: 4px solid #10b981;">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="bg-white rounded-2 p-3">
                                    <i class="fa-solid fa-wifi text-success fs-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-success-emphasis text-uppercase small fw-bold mb-0">{{ __('Online') }}</h6>
                                    <h2 class="display-6 fw-bold text-success mb-0">{{ $onlineOnus }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger-subtle border-0 h-100 cursor-pointer" style="border-left: 4px solid #ef4444;">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="bg-white rounded-2 p-3">
                                    <i class="fa-solid fa-power-off text-danger fs-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-danger-emphasis text-uppercase small fw-bold mb-0">{{ __('Offline / LOS') }}</h6>
                                    <h2 class="display-6 fw-bold text-danger mb-0">{{ $offlineOnus + $losOnus }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning-subtle border-0 h-100 cursor-pointer" style="border-left: 4px solid #f59e0b;">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="bg-white rounded-2 p-3">
                                    <i class="fa-solid fa-triangle-exclamation text-warning fs-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-warning-emphasis text-uppercase small fw-bold mb-0">{{ __('Low Signal') }} (&lt; -25dBm)</h6>
                                    <h2 class="display-6 fw-bold text-warning mb-0">{{ $badSignal }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="card border-0">
                    <div class="card-header bg-light border-bottom-0 p-0">
                        <ul class="nav nav-tabs" id="oltTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="monitor-tab" data-bs-toggle="tab" data-bs-target="#monitor" type="button" role="tab" aria-controls="monitor" aria-selected="true">
                                    <i class="fa-solid fa-desktop me-2"></i> {{ __('Monitor ONU') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="false">
                                    <i class="fa-solid fa-info-circle me-2"></i> {{ __('System Info') }}
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-4">
                        <div class="tab-content" id="oltTabsContent">
                            <!-- Monitor Tab -->
                            <div class="tab-pane fade show active" id="monitor" role="tabpanel" aria-labelledby="monitor-tab">
                                <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
                                    <div class="position-relative" style="max-width: 400px;">
                                        <i class="fa-solid fa-search position-absolute text-muted" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                                        <input type="text" id="onuSearch" class="form-control ps-5" placeholder="{{ __('Search ONU...') }}">
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="onuTable" class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Index') }}</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('SN') }}</th>
                                                <th>{{ __('MAC') }}</th>
                                                <th>{{ __('TX Power') }}</th>
                                                <th>{{ __('RX Signal') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Last Sync') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="onuTableBody">
                                            @forelse($onus as $onu)
                            <tr class="onu-row">
                                <td><code class="text-body">{{ $onu->onu_index }}</code></td>
                                <td class="fw-bold">{{ $onu->name }}</td>
                                <td class="font-monospace text-muted small">{{ $onu->sn ?? '-' }}</td>
                                <td class="font-monospace text-muted small">{{ $onu->mac ?? '-' }}</td>
                                <td>{{ $onu->tx_power ?? '-' }} dBm</td>
                                <td>
                                    @php
                                        $rx = floatval($onu->rx_power);
                                        $sigClass = $rx < -27 ? 'text-danger' : ($rx < -25 ? 'text-warning' : 'text-success');
                                    @endphp
                                    @if($onu->rx_power)
                                        <span class="fw-bold {{ $sigClass }}">{{ $onu->rx_power }} dBm</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $onu->status == 'online' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        <i class="fa-solid fa-{{ $onu->status == 'online' ? 'check-circle' : 'times-circle' }} me-1"></i>
                                        {{ ucfirst($onu->status) }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    @if($onu->last_updated)
                                        {{ \Carbon\Carbon::parse($onu->last_updated)->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}" onclick="editName('{{ $onu->id }}', '{{ $onu->name }}')">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="{{ __('Reboot') }}" onclick="rebootOnu('{{ $onu->id }}', '{{ $onu->onu_index }}')">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="fa-solid fa-plug-circle-xmark fa-4x text-warning mb-3"></i>
                                        <h5 class="text-muted">{{ __('No ONU devices found yet') }}</h5>
                                    </div>
                                    <div class="text-start mx-auto" style="max-width: 500px;">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-info-circle me-2"></i>{{ __('Important Notes') }}</h6>
                                                <ul class="mb-0 text-muted small">
                                                    <li class="mb-2">
                                                        <strong>Status "Online" hanya cek port</strong> - bukan koneksi SNMP
                                                    </li>
                                                    <li class="mb-2">
                                                        <strong>Butuh PHP SNMP Extension!</strong> - Aktifkan di <code>php.ini</code>:<br>
                                                        <code class="text-danger">extension=snmp</code> (hilangkan tanda <code>;</code> di depan)
                                                    </li>
                                                    <li class="mb-2">
                                                        <strong>Setelah mengaktifkan SNMP</strong> - Klik tombol "Sync OLT Data" di atas
                                                    </li>
                                                    <li>
                                                        <strong>Alternatif</strong> - Anda bisa menambahkan ONU secara manual di halaman lain
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if(method_exists($onus, 'links'))
                                    <div class="mt-4">
                                        {{ $onus->links() }}
                                    </div>
                                @endif
                            </div>

                            <!-- Info Tab -->
                            <div class="tab-pane fade" id="info" role="tabpanel" aria-labelledby="info-tab">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header fw-bold">
                                                <i class="fa-solid fa-circle-info me-2 text-muted"></i> {{ __('Basic Information') }}
                                            </div>
                                            <div class="card-body">
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-4 text-secondary">{{ __('Host / IP Address') }}</dt>
                                                    <dd class="col-sm-8 fw-medium font-monospace">{{ $olt->host }}:{{ $olt->port }}</dd>

                                                    <dt class="col-sm-4 text-secondary">{{ __('Brand') }} / {{ __('Model') }}</dt>
                                                    <dd class="col-sm-8 text-uppercase">{{ $olt->brand }} / {{ $olt->model ?? 'N/A' }}</dd>

                                                    <dt class="col-sm-4 text-secondary">{{ __('Username') }}</dt>
                                                    <dd class="col-sm-8">{{ $olt->username ?? 'N/A' }}</dd>

                                                    <dt class="col-sm-4 text-secondary">{{ __('Uptime') }}</dt>
                                                    <dd class="col-sm-8" id="sys-uptime">
                                                        <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                                    </dd>

                                                    <dt class="col-sm-4 text-secondary">{{ __('Temperature') }}</dt>
                                                    <dd class="col-sm-8" id="sys-temp">
                                                        <span class="placeholder-glow"><span class="placeholder col-4"></span></span>
                                                    </dd>

                                                    <dt class="col-sm-4 text-secondary">{{ __('Firmware') }}</dt>
                                                    <dd class="col-sm-8 small text-muted" id="sys-version">
                                                        <span class="placeholder-glow"><span class="placeholder col-8"></span></span>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header fw-bold">
                                                <i class="fa-solid fa-bolt me-2 text-muted"></i> {{ __('Management Actions') }}
                                            </div>
                                            <div class="card-body d-flex flex-column gap-2">
                                                <button onclick="testConnection()" class="btn btn-outline-info text-start">
                                                    <i class="fa-solid fa-plug me-2"></i> {{ __('Test Connection') }}
                                                </button>
                                                <button onclick="syncOLT()" class="btn btn-outline-success text-start">
                                                    <i class="fa-solid fa-sync me-2"></i> {{ __('Sync ONUs from Device') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="editNameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit ONU Name') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editOnuId">
                <div class="mb-3">
                    <label for="newOnuName" class="form-label">{{ __('Name') }}</label>
                    <input type="text" class="form-control" id="newOnuName">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="saveOnuName()">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fa-solid fa-info-circle me-2 text-primary"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetchSystemInfo();
    
    document.getElementById('onuSearch').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.onu-row').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });
});

function syncOLT() {
    const btn = event.currentTarget;
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Syncing...';
    showToast('{{ __('Pulling SNMP data...') }}', 'info');
    
    fetch('{{ route('olt.onus.sync', $olt) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('{{ __('Sync failed') }}: ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    });
}

function editName(id, name) {
    document.getElementById('editOnuId').value = id;
    document.getElementById('newOnuName').value = name;
    new bootstrap.Modal(document.getElementById('editNameModal')).show();
}

function saveOnuName() {
    const id = document.getElementById('editOnuId').value;
    const name = document.getElementById('newOnuName').value;
    
    fetch('/olt/onu/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name: name })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('{{ __('Name updated successfully!') }}', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editNameModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || '{{ __('Error updating name') }}', 'error');
        }
    })
    .catch(error => {
        showToast('{{ __('Error') }}: ' + error.message, 'error');
    });
}

function rebootOnu(id, index) {
    if (!confirm('{{ __('Are you sure you want to reboot ONU') }} ' + index + '?')) return;
    
    showToast('{{ __('Sending reboot command...') }}', 'info');
    
    fetch('/olt/onu/' + id + '/reboot', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.message || '{{ __('Reboot command sent') }}', data.success ? 'success' : 'error');
    })
    .catch(error => {
        showToast('{{ __('Error') }}: ' + error.message, 'error');
    });
}

function fetchSystemInfo() {
    fetch('{{ route('olt.system_info', $olt) }}')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            document.getElementById('sys-uptime').innerHTML = '<span class="text-danger" title="' + data.error + '">Error</span>';
            document.getElementById('sys-temp').innerHTML = '<span class="text-danger">Error</span>';
            document.getElementById('sys-version').innerHTML = '<span class="text-danger">' + data.error + '</span>';
        } else {
            document.getElementById('sys-uptime').innerText = data.uptime || 'N/A';
            document.getElementById('sys-temp').innerText = data.temp || 'N/A';
            document.getElementById('sys-version').innerText = data.version || 'N/A';
        }
    })
    .catch(error => {
        document.getElementById('sys-uptime').innerHTML = '<span class="text-danger">Failed</span>';
        document.getElementById('sys-temp').innerHTML = '<span class="text-danger">Failed</span>';
        document.getElementById('sys-version').innerHTML = '<span class="text-danger">Failed to fetch</span>';
    });
}

function testConnection() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __('Testing...') }}';
    btn.disabled = true;

    fetch('{{ route('olt.test_connection') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ id: {{ $olt->id }} })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('{{ __('Success!') }}: ' + data.message, 'success');
        } else {
            showToast('{{ __('Error!') }}: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('{{ __('Error!') }}: ' + error.message, 'error');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function showToast(message, type = 'info') {
    const toastEl = document.getElementById('toast');
    const toastBody = document.getElementById('toastBody');
    const toastTitle = document.getElementById('toastTitle');
    
    toastBody.textContent = message;
    
    const header = toastEl.querySelector('.toast-header');
    header.classList.remove('bg-primary', 'bg-success', 'bg-danger', 'bg-warning');
    
    switch(type) {
        case 'success':
            toastTitle.innerHTML = '<i class="fa-solid fa-check-circle me-2 text-success"></i> Success';
            break;
        case 'error':
            toastTitle.innerHTML = '<i class="fa-solid fa-times-circle me-2 text-danger"></i> Error';
            break;
        case 'warning':
            toastTitle.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-2 text-warning"></i> Warning';
            break;
        default:
            toastTitle.innerHTML = '<i class="fa-solid fa-info-circle me-2 text-primary"></i> Info';
    }
    
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}
</script>
@endsection
