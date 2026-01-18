@extends('layouts.app')

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
                {{ __('Sesi VPN Aktif') }}
            </h1>
            <div class="text-muted small">
                {{ __('Daftar sesi PPPoE dan Hotspot aktif pada router ini.') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('routers.show', $router) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left-long me-1"></i>{{ __('Kembali ke Detail Router') }}
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                <i class="fa-solid fa-arrows-rotate me-1"></i>{{ __('Refresh') }}
            </button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
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
        <div class="col-md-3">
            <div class="card session-summary-card">
                <div class="card-body text-center">
                    <div class="session-summary-label mb-1">{{ __('PPPoE Aktif') }}</div>
                    <div class="session-summary-value mb-0">{{ count($pppoeActiveSessions) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card session-summary-card">
                <div class="card-body text-center">
                    <div class="session-summary-label mb-1">{{ __('Hotspot Aktif') }}</div>
                    <div class="session-summary-value mb-0">{{ count($hotspotActiveSessions) }}</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button
                class="nav-link active"
                id="pppoe-tab"
                data-bs-toggle="tab"
                data-bs-target="#pppoe-pane"
                type="button"
                role="tab"
                aria-controls="pppoe-pane"
                aria-selected="true"
            >
                <i class="fa-solid fa-user-lock me-1"></i>{{ __('Detail PPPoE') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button
                class="nav-link"
                id="hotspot-tab"
                data-bs-toggle="tab"
                data-bs-target="#hotspot-pane"
                type="button"
                role="tab"
                aria-controls="hotspot-pane"
                aria-selected="false"
            >
                <i class="fa-solid fa-wifi me-1"></i>{{ __('Detail Hotspot') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="pppoe-pane" role="tabpanel" aria-labelledby="pppoe-tab">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ __('PPPoE Aktif Detail') }}</span>
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
        </div>

        <div class="tab-pane fade" id="hotspot-pane" role="tabpanel" aria-labelledby="hotspot-tab">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ __('Hotspot Aktif Detail') }}</span>
                    <div class="input-group input-group-sm" style="max-width: 260px;">
                        <span class="input-group-text bg-light border-0">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control border-0" id="hotspotSearch" placeholder="{{ __('Cari user atau IP...') }}">
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($mikrotikConnected && !empty($hotspotActiveSessions))
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle session-table" id="hotspotTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="session-index">#</th>
                                        <th>{{ __('Username') }}</th>
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
                                            $user = $session['user'] ?? '';
                                            $ip = $session['address'] ?? '';
                                            $mac = $session['mac-address'] ?? '';
                                        @endphp
                                        <tr>
                                            <td class="session-index">{{ $index + 1 }}</td>
                                            <td>{{ $user ?: '-' }}</td>
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
                                            <td>{{ $session['server'] ?? '-' }}</td>
                                            <td>{{ $session['login-by'] ?? '-' }}</td>
                                            <td class="session-uptime">{{ $session['uptime'] ?? '-' }}</td>
                                            <td class="text-end">
                                                @if(!empty($session['.id']))
                                                    <button type="button"
                                                        class="btn btn-outline-danger btn-xs"
                                                        onclick="disconnectHotspotSession('{{ route('routers.hotspot.disconnect', $router) }}', '{{ $session['.id'] }}')">
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
                                {{ __('Router tidak terhubung ke Mikrotik, tidak dapat membaca user Hotspot aktif.') }}
                            @else
                                {{ __('Tidak ada user Hotspot aktif saat ini.') }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
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
                        text: '{{ __('Failed to disconnect PPPoE session.') }}'
                    });
                } else {
                    alert('{{ __('Failed to disconnect PPPoE session.') }}');
                }
            });
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
                        text: '{{ __('Failed to disconnect Hotspot session.') }}'
                    });
                } else {
                    alert('{{ __('Failed to disconnect Hotspot session.') }}');
                }
            });
    }

    function setupFilter(inputId, tableId) {
        var input = document.getElementById(inputId);
        var table = document.getElementById(tableId);
        if (!input || !table) return;

        input.addEventListener('input', function (e) {
            var filter = e.target.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr');

            rows.forEach(function (row) {
                var text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    setupFilter('pppoeSearch', 'pppoeTable');
    setupFilter('hotspotSearch', 'hotspotTable');

    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            var targetSelector = button.getAttribute('data-bs-target');
            if (!targetSelector) return;

            document.querySelectorAll('.nav-tabs .nav-link').forEach(function (nav) {
                nav.classList.remove('active');
                nav.setAttribute('aria-selected', 'false');
            });

            document.querySelectorAll('.tab-content .tab-pane').forEach(function (pane) {
                pane.classList.remove('show', 'active');
            });

            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');

            var targetPane = document.querySelector(targetSelector);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
</script>
@endpush
