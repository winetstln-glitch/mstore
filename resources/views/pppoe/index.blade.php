@extends('layouts.app')

@section('title', __('PPP Management'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('PPP Management') }}</h1>
            <div class="text-muted small">
                {{ __('Manage PPP Profiles and Secrets') }}
                @if($router)
                    <span class="badge bg-info ms-2">{{ $router->name }} ({{ $router->host }})</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @if(count($routers) > 1)
                <form action="{{ route('pppoe.index') }}" method="GET" class="d-flex align-items-center">
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
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs" id="pppoeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-pane" type="button" role="tab" aria-controls="active-pane" aria-selected="true">
                        <i class="fa-solid fa-network-wired me-1"></i> {{ __('Active Connections') }}
                        <span class="badge bg-secondary ms-1">{{ count($pppoeActive) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="secrets-tab" data-bs-toggle="tab" data-bs-target="#secrets-pane" type="button" role="tab" aria-controls="secrets-pane" aria-selected="false">
                        <i class="fa-solid fa-user-lock me-1"></i> {{ __('Secrets') }}
                        <span class="badge bg-secondary ms-1">{{ count($pppoeSecrets) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profiles-tab" data-bs-toggle="tab" data-bs-target="#profiles-pane" type="button" role="tab" aria-controls="profiles-pane" aria-selected="false">
                        <i class="fa-solid fa-sliders me-1"></i> {{ __('Profiles') }}
                        <span class="badge bg-secondary ms-1">{{ count($pppoeProfiles) }}</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="pppoeTabsContent">

                {{-- Active Connections Tab --}}
                <div class="tab-pane fade show active" id="active-pane" role="tabpanel" aria-labelledby="active-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Service</th>
                                    <th>Caller ID</th>
                                    <th>Address</th>
                                    <th>Uptime</th>
                                    <th>Encoding</th>
                                    <th class="text-end">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pppoeActive as $active)
                                    <tr>
                                        <td class="fw-medium">{{ $active['name'] ?? '-' }}</td>
                                        <td><span class="badge bg-primary-subtle text-primary-emphasis">{{ $active['service'] ?? '-' }}</span></td>
                                        <td class="font-monospace small">{{ $active['caller-id'] ?? '-' }}</td>
                                        <td>
                                            @if(isset($active['address']))
                                                <a href="http://{{ $active['address'] }}" target="_blank" class="text-decoration-none">{{ $active['address'] }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $active['uptime'] ?? '-' }}</td>
                                        <td class="small text-muted">{{ $active['encoding'] ?? '-' }}</td>
                                        <td class="text-end">
                                            @if(!empty($active['name']) && $router)
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="disconnectPppoeSession('{{ route('routers.pppoe.disconnect', $router) }}', '{{ $active['name'] }}')">
                                                    <i class="fa-solid fa-power-off"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">{{ __('No active connections found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Secrets Tab --}}
                <div class="tab-pane fade" id="secrets-pane" role="tabpanel" aria-labelledby="secrets-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Profile</th>
                                    <th>Service</th>
                                    <th>Local Address</th>
                                    <th>Remote Address</th>
                                    <th>Last Logged Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pppoeSecrets as $secret)
                                    <tr>
                                        <td class="fw-medium">
                                            {{ $secret['name'] ?? '-' }}
                                            @if(isset($secret['disabled']) && $secret['disabled'] === 'true')
                                                <span class="badge bg-danger ms-1">Disabled</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $secret['profile'] ?? 'default' }}</span></td>
                                        <td>{{ $secret['service'] ?? 'any' }}</td>
                                        <td>{{ $secret['local-address'] ?? '-' }}</td>
                                        <td>{{ $secret['remote-address'] ?? '-' }}</td>
                                        <td class="small text-muted">{{ $secret['last-logged-out'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">{{ __('No secrets found.') }}</td>
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
                                    <th>Local Address</th>
                                    <th>Remote Address</th>
                                    <th>Rate Limit</th>
                                    <th>DNS Server</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pppoeProfiles as $profile)
                                    <tr>
                                        <td class="fw-bold">{{ $profile['name'] ?? '-' }}</td>
                                        <td>{{ $profile['local-address'] ?? '-' }}</td>
                                        <td>{{ $profile['remote-address'] ?? '-' }}</td>
                                        <td>{{ $profile['rate-limit'] ?? '-' }}</td>
                                        <td>{{ $profile['dns-server'] ?? '-' }}</td>
                                        <td>
                                            @if(isset($profile['default']) && $profile['default'] === 'true')
                                                <i class="fa-solid fa-check text-success"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">{{ __('No profiles found.') }}</td>
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
                alert('{{ __('Failed to disconnect PPPoE session.') }}');
            });
    }
</script>
@endpush
