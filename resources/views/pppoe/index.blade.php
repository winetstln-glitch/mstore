@extends('layouts.app')

@section('title', __('PPPoE Active Sessions'))

@push('styles')
<style>
.session-summary-card {
    border-radius: .85rem;
    border: 0;
    box-shadow: 0 10px 30px rgba(15,23,42,0.06);
}
.session-summary-card .card-body {
    padding: .9rem 1.1rem;
}
.session-summary-label {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6c757d;
}
.session-summary-value {
    font-size: 1.35rem;
    font-weight: 600;
}
.session-table {
    border-collapse: separate;
    border-spacing: 0;
}
.session-table thead th {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #6c757d;
    border-bottom-width: 1px;
    white-space: nowrap;
}
.session-table tbody td {
    font-size: .85rem;
    vertical-align: middle;
}
.session-table tbody tr:last-child td {
    border-bottom: 0;
}
.session-chip {
    display: inline-flex;
    align-items: center;
    padding: .15rem .5rem;
    border-radius: 999px;
    background-color: #f1f5f9;
    font-size: .78rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
.session-chip-accent {
    background-color: #ecfdf3;
    color: #15803d;
}
.session-chip-muted {
    background-color: #f9fafb;
    color: #4b5563;
}
.session-uptime {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.session-index {
    text-align: center;
    color: #6c757d;
    width: 40px;
}
[data-bs-theme="dark"] .session-summary-card {
    box-shadow: 0 10px 30px rgba(15,23,42,0.6);
}
[data-bs-theme="dark"] .session-chip {
    background-color: #020617;
}
[data-bs-theme="dark"] .session-chip-accent {
    background-color: #022c22;
    color: #bbf7d0;
}
[data-bs-theme="dark"] .session-chip-muted {
    background-color: #020617;
    color: #e5e7eb;
}
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">
                {{ __('PPPoE Active Sessions') }}
            </h1>
            <div class="text-muted small">
                {{ __('Active PPPoE sessions from Mikrotik.') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                <i class="fa-solid fa-arrows-rotate me-1"></i>{{ __('Refresh') }}
            </button>
        </div>
    </div>

    @if($router)
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <div class="card session-summary-card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small mb-1">{{ __('Router') }}</div>
                            <div class="fw-semibold">{{ $router->name }}</div>
                            <div class="text-muted small">{{ $router->host }}:{{ $router->port }}</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small mb-1">{{ __('Status Mikrotik') }}</div>
                            @if($mikrotikConnected)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fa-solid fa-circle-check me-1"></i>{{ __('Online') }}
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>{{ __('Offline') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card session-summary-card">
                    <div class="card-body text-center">
                        <div class="session-summary-label mb-1">{{ __('PPPoE Aktif') }}</div>
                        <div class="session-summary-value mb-0">{{ count($pppoeActiveSessions) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-body d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ __('PPPoE Active List') }}</span>
                <div class="input-group input-group-sm" style="max-width: 260px;">
                    <span class="input-group-text bg-light border-0">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" class="form-control border-0" id="pppoeSearch" placeholder="{{ __('Cari username atau IP...') }}">
                </div>
            </div>
            <div class="card-body p-0">
                @if($mikrotikConnected && !empty($pppoeActiveSessions))
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle session-table" id="pppoeTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="session-index">#</th>
                                    <th>{{ __('Username') }}</th>
                                    <th>{{ __('IP Address') }}</th>
                                    <th>{{ __('MAC Address') }}</th>
                                    <th>{{ __('Service') }}</th>
                                    <th>{{ __('Uptime') }}</th>
                                    <th class="text-end">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pppoeActiveSessions as $index => $session)
                                    @php
                                        $username = $session['name'] ?? '';
                                        $ip = $session['address'] ?? '';
                                        $mac = $session['caller-id'] ?? '';
                                    @endphp
                                    <tr>
                                        <td class="session-index">{{ $index + 1 }}</td>
                                        <td>{{ $username ?: '-' }}</td>
                                        <td>
                                            @if($ip)
                                                <span class="session-chip session-chip-accent">
                                                    <a href="http://{{ $ip }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                        {{ $ip }}
                                                    </a>
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($mac)
                                                <span class="session-chip session-chip-muted">
                                                    {{ $mac }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $session['service'] ?? '-' }}</td>
                                        <td class="session-uptime">{{ $session['uptime'] ?? '-' }}</td>
                                        <td class="text-end">
                                            @if($username)
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-xs"
                                                    onclick="disconnectPppoeSession('{{ route('routers.pppoe.disconnect', $router) }}', '{{ $username }}')">
                                                    <i class="fa-solid fa-power-off me-1"></i>{{ __('Disconnect') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3 text-center text-muted small">
                        @if(!$mikrotikConnected)
                            {{ __('Router tidak terhubung ke Mikrotik, tidak dapat membaca sesi PPPoE aktif.') }}
                        @else
                            {{ __('Tidak ada sesi PPPoE aktif saat ini.') }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            {{ __('No active router found or assigned.') }}
        </div>
    @endif
@endsection

@push('scripts')
<script>
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function disconnectPppoeSession(url, name) {
        if (!confirm('{{ __('Disconnect PPPoE session for this user?') }}')) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name: name })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (window.Swal) {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? '{{ __('Berhasil') }}' : '{{ __('Gagal') }}',
                        text: data.message || ''
                    }).then(function () {
                        if (data.success) {
                            window.location.reload();
                        }
                    });
                } else {
                    alert(data.message || '');
                    if (data.success) {
                        window.location.reload();
                    }
                }
            })
            .catch(function () {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: '{{ __('Terjadi kesalahan saat memproses permintaan.') }}'
                    });
                } else {
                    alert('{{ __('Terjadi kesalahan saat memproses permintaan.') }}');
                }
            });
    }

    // Simple search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('pppoeSearch');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                const value = this.value.toLowerCase();
                const table = document.getElementById('pppoeTable');
                if(!table) return;
                
                const rows = table.getElementsByTagName('tr');
                
                // Skip header row
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        if (cellText.indexOf(value) > -1) {
                            found = true;
                            break;
                        }
                    }
                    
                    row.style.display = found ? '' : 'none';
                }
            });
        }
    });
</script>
@endpush
