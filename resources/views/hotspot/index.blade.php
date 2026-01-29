@extends('layouts.app')

@section('title', __('Hotspot Management'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Hotspot Management') }}</h1>
            <div class="text-muted small">
                {{ __('Manage Hotspot Vouchers and Users') }} 
                @if($router)
                    <span class="badge bg-info ms-2">{{ $router->name }} ({{ $router->host }})</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @if(count($routers) > 1)
                <form action="{{ route('hotspot.index') }}" method="GET" class="d-flex align-items-center">
                    <select name="router_id" class="form-select form-select-sm me-2" onchange="this.form.submit()" style="max-width: 200px;">
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ $router && $router->id == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                <i class="fa-solid fa-arrows-rotate me-1"></i>{{ __('Refresh') }}
            </button>
        </div>
    </div>

    @if(!$mikrotikConnected)
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            {{ __('Could not connect to Mikrotik Router. Please check the connection settings.') }}
        </div>
    @else
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold mb-1">{{ __('Active Voucher Balance') }}</div>
                        <div class="h3 mb-0 fw-bold text-gray-800">
                            Rp {{ number_format($totalActiveBalance, 0, ',', '.') }}
                        </div>
                        <div class="small text-success mt-2">
                            <i class="fa-solid fa-check-circle me-1"></i> {{ __('Real-time from Active Users') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold mb-1">{{ __('Active Sessions') }}</div>
                        <div class="h3 mb-0 fw-bold text-gray-800">{{ count($hotspotActive) }}</div>
                        <div class="small text-muted mt-2">{{ __('Online Users') }}</div>
                    </div>
                </div>
            </div>
             <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-info border-4">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-bold mb-1">{{ __('Total Users') }}</div>
                        <div class="h3 mb-0 fw-bold text-gray-800">{{ count($hotspotUsers) }}</div>
                        <div class="small text-muted mt-2">{{ __('Registered Users') }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs" id="hotspotTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-pane" type="button" role="tab" aria-controls="active-pane" aria-selected="true">
                        <i class="fa-solid fa-users-viewfinder me-1"></i> {{ __('Active Sessions') }}
                        <span class="badge bg-secondary ms-1">{{ count($hotspotActive) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-pane" type="button" role="tab" aria-controls="users-pane" aria-selected="false">
                        <i class="fa-solid fa-users me-1"></i> {{ __('Users') }}
                        <span class="badge bg-secondary ms-1">{{ count($hotspotUsers) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profiles-tab" data-bs-toggle="tab" data-bs-target="#profiles-pane" type="button" role="tab" aria-controls="profiles-pane" aria-selected="false">
                        <i class="fa-solid fa-id-card me-1"></i> {{ __('User Profiles') }}
                        <span class="badge bg-secondary ms-1">{{ count($hotspotProfiles) }}</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="hotspotTabsContent">
                
                {{-- Active Sessions Tab --}}
                <div class="tab-pane fade show active" id="active-pane" role="tabpanel" aria-labelledby="active-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>MAC Address</th>
                                    <th>Uptime</th>
                                    <th>Bytes In/Out</th>
                                    <th>Login By</th>
                                    <th class="text-end">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($hotspotActive as $active)
                                    <tr>
                                        <td class="fw-medium">{{ $active['user'] ?? '-' }}</td>
                                        <td>
                                            @if(isset($active['address']))
                                                <a href="http://{{ $active['address'] }}" target="_blank" class="text-decoration-none">{{ $active['address'] }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="font-monospace small">{{ $active['mac-address'] ?? '-' }}</td>
                                        <td>{{ $active['uptime'] ?? '-' }}</td>
                                        <td class="small text-muted">
                                            <i class="fa-solid fa-arrow-down text-success"></i> {{ isset($active['bytes-in']) ? \App\Helpers\FormatHelper::bytes($active['bytes-in']) : '-' }}<br>
                                            <i class="fa-solid fa-arrow-up text-primary"></i> {{ isset($active['bytes-out']) ? \App\Helpers\FormatHelper::bytes($active['bytes-out']) : '-' }}
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $active['login-by'] ?? 'unknown' }}</span></td>
                                        <td class="text-end">
                                            @if(!empty($active['.id']) && $router)
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="disconnectHotspotSession('{{ route('routers.hotspot.disconnect', $router) }}', '{{ $active['.id'] }}')">
                                                    <i class="fa-solid fa-power-off"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">{{ __('No active sessions found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Users Tab --}}
                <div class="tab-pane fade" id="users-pane" role="tabpanel" aria-labelledby="users-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Profile</th>
                                    <th>MAC Address</th>
                                    <th>Uptime Limit</th>
                                    <th>Data Limit</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($hotspotUsers as $user)
                                    <tr>
                                        <td class="fw-medium">{{ $user['name'] ?? '-' }}</td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $user['profile'] ?? 'default' }}</span></td>
                                        <td class="font-monospace small">{{ $user['mac-address'] ?? '-' }}</td>
                                        <td>{{ $user['limit-uptime'] ?? '-' }}</td>
                                        <td>{{ isset($user['limit-bytes-total']) ? \App\Helpers\FormatHelper::bytes($user['limit-bytes-total']) : '-' }}</td>
                                        <td class="small text-muted fst-italic">{{ $user['comment'] ?? '' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">{{ __('No users found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Profiles Tab --}}
                <div class="tab-pane fade" id="profiles-pane" role="tabpanel" aria-labelledby="profiles-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Shared Users</th>
                                    <th>Rate Limit (Rx/Tx)</th>
                                    <th>Session Timeout</th>
                                    <th>Keepalive Timeout</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($hotspotProfiles as $profile)
                                    <tr>
                                        <td class="fw-bold">{{ $profile['name'] ?? '-' }}</td>
                                        <td>{{ $profile['shared-users'] ?? '-' }}</td>
                                        <td>{{ $profile['rate-limit'] ?? '-' }}</td>
                                        <td>{{ $profile['session-timeout'] ?? '-' }}</td>
                                        <td>{{ $profile['keepalive-timeout'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">{{ __('No profiles found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
                alert('{{ __('Failed to disconnect Hotspot session.') }}');
            });
    }
</script>
@endpush
