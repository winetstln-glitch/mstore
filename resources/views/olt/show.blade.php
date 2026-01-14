@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-server me-2 text-primary"></i> {{ $olt->name }}</h5>
                    <span class="badge {{ $olt->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} ms-3">
                        {{ $olt->is_active ? __('Active') : __('Inactive') }}
                    </span>
                </div>
                <div class="btn-group">
                    <button onclick="testConnection()" class="btn btn-outline-info btn-sm">
                        <i class="fa-solid fa-plug me-1"></i> {{ __('Test Connection') }}
                    </button>
                    <a href="{{ route('olt.edit', $olt) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('Edit') }}
                    </a>
                    <a href="{{ route('olt.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Status Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary-subtle border-0 h-100">
                            <div class="card-body text-center">
                                <h6 class="text-primary-emphasis text-uppercase small fw-bold">{{ __('Total ONUs') }}</h6>
                                <h2 class="display-6 fw-bold text-primary mb-0">{{ $totalOnus }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success-subtle border-0 h-100">
                            <div class="card-body text-center">
                                <h6 class="text-success-emphasis text-uppercase small fw-bold">{{ __('Online') }}</h6>
                                <h2 class="display-6 fw-bold text-success mb-0">{{ $onlineOnus }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger-subtle border-0 h-100">
                            <div class="card-body text-center">
                                <h6 class="text-danger-emphasis text-uppercase small fw-bold">{{ __('Offline / LOS') }}</h6>
                                <h2 class="display-6 fw-bold text-danger mb-0">{{ $offlineOnus + $losOnus }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning-subtle border-0 h-100">
                            <div class="card-body text-center">
                                <h6 class="text-warning-emphasis text-uppercase small fw-bold">{{ __('Low Signal') }}</h6>
                                <h2 class="display-6 fw-bold text-warning mb-0">{{ $badSignal }}</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light fw-bold">
                                <i class="fa-solid fa-circle-info me-2 text-muted"></i> {{ __('System Information') }}
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-secondary">{{ __('Host / IP Address') }}</dt>
                                    <dd class="col-sm-8 fw-medium font-monospace">{{ $olt->host }}:{{ $olt->port }}</dd>

                                    <dt class="col-sm-4 text-secondary">{{ __('Brand') }} / {{ __('Type') }}</dt>
                                    <dd class="col-sm-8 text-uppercase">{{ $olt->brand }} / {{ $olt->type }}</dd>

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

                    <!-- Actions & Quick Links -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light fw-bold">
                                <i class="fa-solid fa-bolt me-2 text-muted"></i> Management Actions
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('olt.onus.index', $olt) }}" class="btn btn-outline-primary text-start">
                                        <i class="fa-solid fa-list me-2"></i> View All ONUs List
                                        <small class="d-block text-muted">Manage, search, and edit individual ONUs</small>
                                    </a>
                                    
                                    <form action="{{ route('olt.onus.sync', $olt) }}" method="POST" class="d-block">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success text-start w-100">
                                            <i class="fa-solid fa-sync me-2"></i> Sync ONUs from Device
                                            <small class="d-block text-muted">Fetch latest connected devices and status</small>
                                        </button>
                                    </form>

                                    <button disabled class="btn btn-outline-secondary text-start">
                                        <i class="fa-solid fa-terminal me-2"></i> Remote Terminal (Coming Soon)
                                        <small class="d-block text-muted">Direct Telnet/SSH access via web</small>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch System Info on Load
        fetchSystemInfo();
    });

    function fetchSystemInfo() {
        fetch('{{ route('olt.system_info', $olt) }}')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                 document.getElementById('sys-uptime').innerHTML = '<span class="text-danger" title="' + data.error + '">Error</span>';
                 document.getElementById('sys-temp').innerHTML = '<span class="text-danger">Error</span>';
                 document.getElementById('sys-version').innerHTML = '<span class="text-danger">' + data.error + '</span>';
            } else {
                document.getElementById('sys-uptime').innerText = data.uptime;
                document.getElementById('sys-temp').innerText = data.temp;
                document.getElementById('sys-version').innerText = data.version;
            }
        })
        .catch(error => {
            console.error('Error fetching system info:', error);
            document.getElementById('sys-uptime').innerHTML = '<span class="text-danger">Failed</span>';
            document.getElementById('sys-temp').innerHTML = '<span class="text-danger">Failed</span>';
            document.getElementById('sys-version').innerHTML = '<span class="text-danger">Failed to fetch</span>';
        });
    }

    function testConnection() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Testing...';
        btn.disabled = true;

        fetch('{{ route('olt.test_connection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id: {{ $olt->id }} })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('{{ __('Connection Successful') }}: ' + data.message);
            } else {
                alert('{{ __('Connection Failed') }}: ' + data.message);
            }
        })
        .catch(error => {
            alert('{{ __('Error') }}: ' + error.message);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
</script>
@endsection
