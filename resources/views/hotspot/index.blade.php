@extends('layouts.app')

@section('title', __('Hotspot Active Sessions'))

@push('styles')
<style>
/* General Styling */
.session-summary-card {
    border-radius: .85rem;
    border: 0;
    box-shadow: 0 10px 30px rgba(15,23,42,0.06);
    transition: transform 0.2s;
}
.session-summary-card:hover {
    transform: translateY(-2px);
}
.session-summary-card .card-body {
    padding: 1.2rem;
}
.session-summary-label {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6c757d;
    font-weight: 600;
}
.session-summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
}
.session-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}
.session-table thead th {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #6c757d;
    border-bottom-width: 1px;
    background-color: #f8fafc;
    white-space: nowrap;
}
.session-table tbody td {
    font-size: .9rem;
    vertical-align: middle;
    color: #334155;
}
.session-table tbody tr:last-child td {
    border-bottom: 0;
}
.session-chip {
    display: inline-flex;
    align-items: center;
    padding: .25rem .6rem;
    border-radius: 999px;
    background-color: #f1f5f9;
    font-size: .8rem;
    font-weight: 500;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
.session-chip-accent {
    background-color: #ecfdf3;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.session-chip-muted {
    background-color: #f9fafb;
    color: #4b5563;
    border: 1px solid #e5e7eb;
}
.session-uptime {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-weight: 500;
}
.session-index {
    text-align: center;
    color: #94a3b8;
    width: 50px;
    font-weight: 600;
}

/* Mobile Optimization (Card View) */
@media (max-width: 768px) {
    /* Hide Table Header */
    .session-table thead {
        display: none;
    }
    
    /* Transform Rows into Cards */
    .session-table, .session-table tbody, .session-table tr, .session-table td {
        display: block;
        width: 100%;
    }
    
    .session-table tr {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .session-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: right;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
    }

    .session-table td:last-child {
        border-bottom: none;
        background-color: #f8fafc;
        justify-content: flex-end;
    }

    /* Hide the Index # column on mobile */
    .session-index {
        display: none;
    }

    /* Create Labels using ::before */
    .session-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
        font-size: 0.85rem;
        margin-right: 1rem;
        text-align: left;
        flex: 1;
    }

    /* Specific adjustments for mobile chips */
    .session-table td .session-chip {
        max-width: 60%;
    }
    
    /* Adjust disconnect button on mobile */
    .btn-xs {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        width: 100%;
        text-align: center;
    }
    
    /* Search Input full width */
    .input-group {
        max-width: 100% !important;
    }
}

/* Dark Mode Overrides */
[data-bs-theme="dark"] .session-summary-card {
    box-shadow: 0 10px 30px rgba(15,23,42,0.6);
    background-color: #1e293b;
}
[data-bs-theme="dark"] .session-chip {
    background-color: #0f172a;
    border-color: #334155;
}
[data-bs-theme="dark"] .session-chip-accent {
    background-color: #064e3b;
    color: #bbf7d0;
    border-color: #065f46;
}
[data-bs-theme="dark"] .session-chip-muted {
    background-color: #0f172a;
    color: #cbd5e1;
}
[data-bs-theme="dark"] .session-table tr {
    background-color: #1e293b;
}
[data-bs-theme="dark"] .session-table td {
    color: #e2e8f0;
    border-color: #334155;
}
[data-bs-theme="dark"] .session-table td:last-child {
    background-color: #0f172a;
}
[data-bs-theme="dark"] .session-table tbody tr {
    border-color: #334155;
}
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 fw-bold">
                {{ __('Hotspot Active Sessions') }}
            </h1>
            <div class="text-muted small">
                {{ __('Active Hotspot users from Mikrotik.') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            @if(isset($routers) && count($routers) > 0 && !(Auth::user()->coordinator && Auth::user()->coordinator->router_id))
                <form method="GET" action="{{ route('hotspot.index') }}" class="d-flex align-items-center">
                    <select name="router_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ $router && $router->id == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->host }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center" onclick="window.location.reload()">
                <i class="fa-solid fa-arrows-rotate me-1"></i> {{ __('Refresh') }}
            </button>
        </div>
    </div>

    @if($router)
        <div class="row g-3 mb-4">
            <div class="col-lg-8 col-md-7">
                <div class="card session-summary-card h-100">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="text-center text-md-start w-100">
                            <div class="session-summary-label mb-1">{{ __('Router Info') }}</div>
                            <div class="h5 mb-1 fw-bold">{{ $router->name }}</div>
                            <div class="text-muted small"><i class="fa-solid fa-server me-1"></i>{{ $router->host }}:{{ $router->port }}</div>
                        </div>
                        <div class="text-center text-md-end w-100 border-start border-md-0 border-secondary-subtle ps-md-3">
                            <div class="session-summary-label mb-2">{{ __('Status Mikrotik') }}</div>
                            @if($mikrotikConnected)
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">
                                    <i class="fa-solid fa-circle-check me-1"></i> {{ __('Online') }}
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> {{ __('Offline') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-5">
                <div class="card session-summary-card bg-primary bg-opacity-10 border-primary border-opacity-25 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <div class="session-summary-label mb-1">{{ __('Total Active') }}</div>
                        <div class="session-summary-value text-primary mb-0">{{ count($hotspotActiveSessions) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-body border-bottom-0 pt-3 pb-0 px-3">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="fw-bold mb-0 text-dark">{{ __('User List') }}</h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 rounded-start-pill">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-0 bg-light rounded-end-pill" id="hotspotSearch" placeholder="{{ __('Cari user, IP, atau MAC...') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0 pt-3">
                @if($mikrotikConnected && !empty($hotspotActiveSessions))
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle session-table" id="hotspotTable">
                            <thead class="bg-body-tertiary">
                                <tr>
                                    <th class="session-index">#</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('IP Address') }}</th>
                                    <th>{{ __('MAC Address') }}</th>
                                    <th>{{ __('Server') }}</th>
                                    <th>{{ __('Login By') }}</th>
                                    <th>{{ __('Uptime') }}</th>
                                    <th class="text-end">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hotspotActiveSessions as $index => $session)
                                    @php
                                        $user = $session['user'] ?? '-';
                                        $ip = $session['address'] ?? '-';
                                        $mac = $session['mac-address'] ?? '-';
                                    @endphp
                                    <tr>
                                        <td class="session-index" data-label="#">{{ $index + 1 }}</td>
                                        <td data-label="{{ __('Username') }}">
                                            <span class="fw-semibold text-dark">{{ $user }}</span>
                                        </td>
                                        <td data-label="{{ __('IP Address') }}">
                                            @if($ip != '-')
                                                <a href="http://{{ $ip }}" target="_blank" class="session-chip session-chip-accent text-decoration-none">
                                                    {{ $ip }} <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7em;"></i>
                                                </a>
                                            @else
                                                <span class="text-muted small">{{ $ip }}</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('MAC Address') }}">
                                            <span class="session-chip session-chip-muted text-break">
                                                {{ $mac }}
                                            </span>
                                        </td>
                                        <td data-label="{{ __('Server') }}">
                                            <span class="small text-muted">{{ $session['server'] ?? '-' }}</span>
                                        </td>
                                        <td data-label="{{ __('Login By') }}">
                                            <span class="badge bg-light text-dark border">
                                                {{ $session['login-by'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td data-label="{{ __('Uptime') }}">
                                            <span class="session-uptime text-primary"><i class="fa-regular fa-clock me-1"></i>{{ $session['uptime'] ?? '-' }}</span>
                                        </td>
                                        <td class="text-end" data-label="{{ __('Aksi') }}">
                                            @if(!empty($session['.id']))
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-xs rounded-pill fw-semibold"
                                                    onclick="disconnectHotspotSession('{{ route('routers.hotspot.disconnect', $router) }}', '{{ $session['.id'] }}')">
                                                    <i class="fa-solid fa-power-off me-1"></i> {{ __('Disconnect') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5 text-center">
                        <div class="mb-3 text-muted">
                            <i class="fa-solid fa-wifi fa-3x opacity-25"></i>
                        </div>
                        <div class="text-muted small fw-medium">
                            @if(!$mikrotikConnected)
                                {{ __('Router tidak terhubung. Cek koneksi Mikrotik.') }}
                            @else
                                {{ __('Tidak ada user Hotspot aktif saat ini.') }}
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">
            <i class="fa-solid fa-triangle-exclamation me-3 fs-4"></i>
            <div>
                <strong>{{ __('Peringatan') }}</strong>
                <div class="small">{{ __('No active router found or assigned.') }}</div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function disconnectHotspotSession(url, id) {
        if (!confirm('{{ __('Disconnect Hotspot session for this user?') }}')) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (window.Swal) {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? '{{ __('Berhasil') }}' : '{{ __('Gagal') }}',
                        text: data.message || '',
                        confirmButtonColor: '#0d6efd',
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
        const searchInput = document.getElementById('hotspotSearch');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                const value = this.value.toLowerCase();
                const table = document.getElementById('hotspotTable');
                if(!table) return;
                
                const rows = table.getElementsByTagName('tr');
                
                // Skip header row
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    // Get text content specifically for mobile card view or desktop table view
                    const textContent = row.innerText.toLowerCase();
                    
                    if (textContent.indexOf(value) > -1) {
                        row.style.display = (window.innerWidth <= 768) ? 'block' : ''; // Handle display type based on view
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
        
        // Handle resize to reset display property when switching between mobile/desktop
        window.addEventListener('resize', function() {
             const rows = document.querySelectorAll('#hotspotTable tbody tr');
             const isMobile = window.innerWidth <= 768;
             rows.forEach(row => {
                 if(row.style.display !== 'none') {
                     row.style.display = isMobile ? 'block' : '';
                 }
             });
        });
    });
</script>
@endpush