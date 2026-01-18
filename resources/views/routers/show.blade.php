@extends('layouts.app')

@push('styles')
<style>
.router-hero {
    background: linear-gradient(135deg, #16a34a, #f97316);
    border-radius: 1rem;
    padding: 1.5rem 1.75rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    color: #fff;
    box-shadow: 0 0.46875rem 2.1875rem rgba(4,9,20,0.03), 0 0.9375rem 1.40625rem rgba(4,9,20,0.03), 0 0.25rem 0.53125rem rgba(4,9,20,0.05);
    position: relative;
    overflow: hidden;
}
.router-hero::after {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: radial-gradient(circle at center, rgba(255,255,255,0.18), transparent 60%);
    right: -60px;
    top: -80px;
}
.router-hero-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: .25rem;
}
.router-hero-subtitle {
    font-size: .9rem;
    opacity: .9;
}
.router-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: .75rem;
}
.router-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .6rem;
    border-radius: 999px;
    background-color: rgba(15,23,42,0.18);
    font-size: .78rem;
}
.router-chip i {
    font-size: .8rem;
}
.router-hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .5rem;
}
.router-hero-status {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .2rem .6rem;
    border-radius: 999px;
    font-size: .8rem;
    background-color: rgba(15,23,42,0.28);
}
.router-hero-status-icon {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background-color: #22c55e;
}
.router-hero-status-icon.offline {
    background-color: #ef4444;
}
.router-hero-avatar {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    background-color: rgba(15,23,42,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: .5rem;
}
.router-stat-card {
    border-radius: .85rem;
    border: 0;
    box-shadow: 0 10px 30px rgba(15,23,42,0.06);
}
.router-stat-label {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6c757d;
}
.router-stat-value {
    font-size: 1.4rem;
    font-weight: 600;
}
.router-stat-badge {
    font-size: .75rem;
}
@media (max-width: 768px) {
    .router-hero {
        flex-direction: column;
        gap: 1rem;
    }
    .router-hero-actions {
        align-items: flex-start;
    }
}
.metric-bar {
    width: 100%;
    height: 6px;
    border-radius: 999px;
    background-color: #e9ecef;
    overflow: hidden;
}
.metric-bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #16a34a, #f97316);
}
@media (max-width: 768px) {
    .router-hero {
        flex-direction: column;
        gap: 1rem;
    }
    .router-hero-actions {
        align-items: flex-start;
    }
}
[data-bs-theme="dark"] .router-hero {
    background: linear-gradient(135deg, #14532d, #9a3412);
}
[data-bs-theme="dark"] .metric-bar {
    background-color: #2c3034;
}
</style>
@endpush

@section('content')
    <div class="router-hero mb-3">
        <div class="position-relative" style="z-index: 1;">
            <div class="router-hero-title d-flex align-items-center gap-2">
                <span>{{ $router->name }}</span>
                @if($router->is_active)
                    <span class="badge bg-success-subtle text-success router-stat-badge">{{ __('Aktif') }}</span>
                @else
                    <span class="badge bg-danger-subtle text-danger router-stat-badge">{{ __('Nonaktif') }}</span>
                @endif
            </div>
            <div class="router-hero-subtitle">
                {{ __('Detail Router VPN dan status monitoring Mikrotik.') }}
            </div>
            <div class="router-hero-meta">
                <span class="router-chip">
                    <i class="fa-solid fa-location-arrow"></i>
                    <span>{{ $router->host }}:{{ $router->port }}</span>
                </span>
                <span class="router-chip">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>{{ $router->username }}</span>
                </span>
                @if($mikrotikConnected && is_array($systemResource))
                    <span class="router-chip">
                        <i class="fa-solid fa-microchip"></i>
                        <span>{{ $systemResource['board-name'] ?? 'RouterOS' }}</span>
                    </span>
                    <span class="router-chip">
                        <i class="fa-solid fa-code-branch"></i>
                        <span>v{{ $systemResource['version'] ?? 'N/A' }}</span>
                    </span>
                @endif
            </div>
        </div>
        <div class="router-hero-actions">
            <div class="router-hero-status">
                <span class="router-hero-status-icon {{ $mikrotikConnected ? '' : 'offline' }}"></span>
                <span>{{ $mikrotikConnected ? __('Mikrotik Online') : __('Mikrotik Offline') }}</span>
            </div>
            <div class="router-hero-meta">
                <a href="{{ route('routers.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-arrow-left-long me-1"></i>{{ __('Kembali') }}
                </a>
                <a href="{{ route('routers.edit', $router) }}" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-pen-to-square me-1"></i>{{ __('Edit Router') }}
                </a>
                <a href="{{ route('routers.sessions', $router) }}" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-up-right-from-square me-1"></i>{{ __('Halaman Sesi Lengkap') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-lg-3">
                    <div class="card router-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="router-stat-label">{{ __('PPPoE Aktif') }}</div>
                                <span class="badge bg-success-subtle text-success router-stat-badge">
                                    <i class="fa-solid fa-user-lock me-1"></i>PPPoE
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="router-stat-value">{{ $pppoeActiveCount }}</div>
                                @if($mikrotikConnected)
                                    <span class="text-success small">{{ __('Live') }}</span>
                                @else
                                    <span class="text-muted small">{{ __('No data') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card router-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="router-stat-label">{{ __('Hotspot Aktif') }}</div>
                                <span class="badge bg-warning-subtle text-warning router-stat-badge">
                                    <i class="fa-solid fa-wifi me-1"></i>Hotspot
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="router-stat-value">{{ $hotspotActiveCount }}</div>
                                @if($mikrotikConnected)
                                    <span class="text-success small">{{ __('Live') }}</span>
                                @else
                                    <span class="text-muted small">{{ __('No data') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-6">
            <div class="card shadow-sm border-0 mb-3 router-stat-card">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ __('Informasi Router') }}</span>
                    @if($router->is_active)
                        <span class="badge bg-success-subtle text-success">{{ __('Aktif') }}</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger">{{ __('Nonaktif') }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">{{ __('Nama Router') }}</dt>
                        <dd class="col-7">{{ $router->name }}</dd>

                        <dt class="col-5 text-muted">{{ __('Host / IP') }}</dt>
                        <dd class="col-7">{{ $router->host }}:{{ $router->port }}</dd>

                        <dt class="col-5 text-muted">{{ __('Lokasi') }}</dt>
                        <dd class="col-7">{{ $router->location ?: '-' }}</dd>

                        <dt class="col-5 text-muted">{{ __('Username') }}</dt>
                        <dd class="col-7">{{ $router->username }}</dd>

                        <dt class="col-5 text-muted">{{ __('Deskripsi') }}</dt>
                        <dd class="col-7">
                            {{ $router->description ?: '-' }}
                        </dd>

                        <dt class="col-5 text-muted">{{ __('Dibuat') }}</dt>
                        <dd class="col-7">
                            {{ optional($router->created_at)->format('d M Y H:i') }}
                        </dd>

                        <dt class="col-5 text-muted">{{ __('Diperbarui') }}</dt>
                        <dd class="col-7">
                            {{ optional($router->updated_at)->format('d M Y H:i') }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-6">
            <div class="card shadow-sm border-0 router-stat-card">
                <div class="card-header bg-body fw-semibold">
                    {{ __('Status Koneksi Mikrotik') }}
                </div>
                <div class="card-body small">
                    @if($mikrotikConnected && is_array($systemResource))
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success-subtle text-success me-2">
                                <i class="fa-solid fa-circle-check me-1"></i>{{ __('Terhubung') }}
                            </span>
                            <span class="text-muted">
                                {{ __('RouterOS') }} {{ $systemResource['version'] ?? 'N/A' }}
                            </span>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-1">
                                <span class="text-muted">{{ __('Board') }}:</span>
                                <span>{{ $systemResource['board-name'] ?? 'N/A' }}</span>
                            </li>
                            <li class="mb-1">
                                <span class="text-muted">{{ __('Uptime') }}:</span>
                                <span>{{ $systemResource['uptime'] ?? 'N/A' }}</span>
                            </li>
                            <li class="mb-1">
                                <span class="text-muted">{{ __('CPU Load') }}:</span>
                                <span>{{ isset($systemResource['cpu-load']) ? $systemResource['cpu-load'] . '%' : 'N/A' }}</span>
                            </li>
                            <li class="mb-1">
                                <span class="text-muted">{{ __('Memory') }}:</span>
                                <span>{{ $memoryUsage ?? 'N/A' }}</span>
                            </li>
                            @if(isset($systemResource['cpu-load']) || $memoryPercent !== null)
                                <li class="mt-2">
                                    <div class="mb-1 d-flex justify-content-between">
                                        <span class="text-muted">{{ __('CPU') }}</span>
                                        <span>{{ isset($systemResource['cpu-load']) ? $systemResource['cpu-load'] . '%' : '0%' }}</span>
                                    </div>
                                    <div class="metric-bar mb-2">
                                        <div class="metric-bar-fill" style="width: {{ isset($systemResource['cpu-load']) ? (int)$systemResource['cpu-load'] : 0 }}%"></div>
                                    </div>
                                    <div class="mb-1 d-flex justify-content-between">
                                        <span class="text-muted">{{ __('Memory') }}</span>
                                        <span>{{ $memoryPercent !== null ? $memoryPercent . '%' : '0%' }}</span>
                                    </div>
                                    <div class="metric-bar">
                                        <div class="metric-bar-fill" style="width: {{ $memoryPercent !== null ? $memoryPercent : 0 }}%"></div>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    @else
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger-subtle text-danger me-2">
                                <i class="fa-solid fa-circle-xmark me-1"></i>{{ __('Tidak Terhubung') }}
                            </span>
                            <span class="text-muted">
                                {{ __('Tidak dapat mengambil informasi sistem dari Mikrotik.') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0 router-stat-card">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ __('Traffic Interface (Snapshot)') }}</span>
                    <span class="text-muted small">
                        {{ $mikrotikConnected ? __('Kecepatan RX/TX per interface saat halaman dimuat.') : __('Router offline.') }}
                    </span>
                </div>
                <div class="card-body">
                    @if($mikrotikConnected && !empty($interfacesTraffic))
                        @php
                            $maxRate = 0;
                            foreach ($interfacesTraffic as $iface) {
                                $maxRate = max($maxRate, $iface['rx'], $iface['tx']);
                            }
                            $maxRate = $maxRate ?: 1;
                        @endphp
                        <div class="small text-muted mb-2">
                            {{ __('Satuan dalam bit per detik (bps), snapshot sekali baca.') }}
                        </div>
                        @foreach($interfacesTraffic as $iface)
                            @php
                                $rxPercent = (int) round(($iface['rx'] / $maxRate) * 100);
                                $txPercent = (int) round(($iface['tx'] / $maxRate) * 100);
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold">{{ $iface['name'] }}</span>
                                    <span class="text-muted small">
                                        {{ number_format($iface['rx']) }} bps ↓ / {{ number_format($iface['tx']) }} bps ↑
                                    </span>
                                </div>
                                <div class="mb-1 d-flex justify-content-between">
                                    <span class="text-muted small">{{ __('RX') }}</span>
                                    <span class="text-muted small">{{ $rxPercent }}%</span>
                                </div>
                                <div class="metric-bar mb-2">
                                    <div class="metric-bar-fill" style="width: {{ $rxPercent }}%;"></div>
                                </div>
                                <div class="mb-1 d-flex justify-content-between">
                                    <span class="text-muted small">{{ __('TX') }}</span>
                                    <span class="text-muted small">{{ $txPercent }}%</span>
                                </div>
                                <div class="metric-bar">
                                    <div class="metric-bar-fill" style="width: {{ $txPercent }}%; background: linear-gradient(90deg,#0ea5e9,#6366f1);"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-muted small">
                            @if(!$mikrotikConnected)
                                {{ __('Router tidak terhubung ke Mikrotik, tidak dapat membaca traffic interface.') }}
                            @else
                                {{ __('Tidak ada data interface yang terbaca.') }}
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

    function togglePppoeSecret(url, name, isDisabled) {
        var enable = isDisabled ? true : false;
        var confirmText = enable
            ? '{{ __('Unblock this PPPoE user?') }}'
            : '{{ __('Block this PPPoE user?') }}';

        if (!confirm(confirmText)) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name: name, enable: enable })
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
                        text: '{{ __('Failed to update PPPoE user status.') }}'
                    });
                } else {
                    alert('{{ __('Failed to update PPPoE user status.') }}');
                }
            });
    }
</script>
@endpush
