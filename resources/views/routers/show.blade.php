@extends('layouts.app')

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
                    @if($router->vpn_account_id)
                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">VPN Bridge</div>
                                <div class="small text-muted">
                                    {{ $router->vpn_tunnel_ip ? 'Tunnel IP: '.$router->vpn_tunnel_ip : 'Belum terdeteksi' }}
                                </div>
                            </div>
                            <a href="{{ route('routers.vpn.script', $router) }}" class="btn btn-sm btn-primary">Generate Script</a>
                        </div>
                    @endif
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
                <div class="card-header  d-flex justify-content-between align-items-center">
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

        <div class="col-12">
            <div class="card shadow-sm border-0 router-stat-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">{{ __('Simple Queues') }}</span>
                    @if($mikrotikConnected)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createQueueModal">
                            <i class="fa-solid fa-plus"></i> {{ __('Buat Queue Baru') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($mikrotikConnected)
                        @if(!empty($simpleQueues))
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Nama') }}</th>
                                            <th>{{ __('Target') }}</th>
                                            <th>{{ __('Max Limit') }}</th>
                                            <th>{{ __('Limit At') }}</th>
                                            <th>{{ __('Priority') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th class="text-end">{{ __('Aksi') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($simpleQueues as $queue)
                                            <tr>
                                                <td>{{ $queue['name'] ?? '-' }}</td>
                                                <td>{{ $queue['target'] ?? '-' }}</td>
                                                <td>{{ $queue['max-limit'] ?? '-' }}</td>
                                                <td>{{ $queue['limit-at'] ?? '-' }}</td>
                                                <td>{{ $queue['priority'] ?? '-' }}</td>
                                                <td>
                                                    @if(($queue['disabled'] ?? 'false') === 'true')
                                                        <span class="badge bg-danger-subtle text-danger">{{ __('Disabled') }}</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success">{{ __('Enabled') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-secondary" onclick="toggleQueue('{{ route('routers.simple-queues.toggle', $router) }}', '{{ $queue['.id'] }}', {{ ($queue['disabled'] ?? 'false') === 'true' ? 'true' : 'false' }})">
                                                            {{ ($queue['disabled'] ?? 'false') === 'true' ? __('Enable') : __('Disable') }}
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" onclick="deleteQueue('{{ route('routers.simple-queues.destroy', $router) }}', '{{ $queue['.id'] }}')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted small text-center py-3">
                                {{ __('Tidak ada simple queue yang ditemukan.') }}
                            </div>
                        @endif
                    @else
                        <div class="text-muted small">
                            {{ __('Router tidak terhubung ke Mikrotik, tidak dapat membaca simple queues.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Create Queue Modal -->
    <div class="modal fade" id="createQueueModal" tabindex="-1" aria-labelledby="createQueueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('routers.simple-queues.store', $router) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createQueueModalLabel">{{ __('Buat Simple Queue Baru') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="queueName" class="form-label">{{ __('Nama Queue') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="queueName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="queueTarget" class="form-label">{{ __('Target') }}</label>
                            <input type="text" class="form-control" id="queueTarget" name="target">
                            <div class="form-text">{{ __('Contoh: 192.168.1.0/24 atau nama PPPoE secret') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="queueMaxLimit" class="form-label">{{ __('Max Limit') }}</label>
                            <input type="text" class="form-control" id="queueMaxLimit" name="max-limit" placeholder="10M/20M">
                            <div class="form-text">{{ __('Format: TX/RX, satuan: k, M, G') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="queueLimitAt" class="form-label">{{ __('Limit At') }}</label>
                            <input type="text" class="form-control" id="queueLimitAt" name="limit-at" placeholder="5M/10M">
                        </div>
                        <div class="mb-3">
                            <label for="queuePriority" class="form-label">{{ __('Priority') }}</label>
                            <select class="form-select" id="queuePriority" name="priority">
                                <option value="">{{ __('Pilih') }}</option>
                                @for($i=1; $i<=8; $i++)
                                    <option value="{{ $i }}">{{ $i }} - {{ $i === 1 ? __('Tertinggi') : ($i === 8 ? __('Terendah') : '') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="queueComment" class="form-label">{{ __('Komentar') }}</label>
                            <input type="text" class="form-control" id="queueComment" name="comment">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                    </div>
                </form>
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

    function toggleQueue(url, id, isDisabled) {
        var enable = isDisabled ? true : false;
        var confirmText = enable
            ? '{{ __('Enable this queue?') }}'
            : '{{ __('Disable this queue?') }}';

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
            body: JSON.stringify({ '.id': id, enable: enable })
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
                        text: '{{ __('Failed to update queue status.') }}'
                    });
                } else {
                    alert('{{ __('Failed to update queue status.') }}');
                }
            });
    }

    function deleteQueue(url, id) {
        if (!confirm('{{ __('Delete this queue?') }}')) {
            return;
        }

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ '.id': id })
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
                        text: '{{ __('Failed to delete queue.') }}'
                    });
                } else {
                    alert('{{ __('Failed to delete queue.') }}');
                }
            });
    }
</script>
@endpush
