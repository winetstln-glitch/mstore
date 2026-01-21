@extends('layouts.app')

@section('title', __('Router Management'))

@push('styles')
<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.content-header-title {
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}
.content-header-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
}
.vpn-badge {
    font-size: 0.75rem;
    text-transform: uppercase;
}
.vpn-stat-card {
    border-radius: 0.75rem;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    display: inline-block;
    margin-right: 6px;
}
.status-dot-online {
    background-color: #28a745;
}
.status-dot-offline {
    background-color: #dc3545;
}
.vpn-table-actions {
    white-space: nowrap;
}
</style>
@endpush

@section('content')
    @php
        $totalRouters = \App\Models\Router::count();
        $activeRouters = \App\Models\Router::where('is_active', true)->count();
        $inactiveRouters = $totalRouters - $activeRouters;
    @endphp

    <div class="content-header">
        <div>
            <h1 class="content-header-title mb-1">
                {{ __('VPN Management') }}
                <span class="badge bg-primary-subtle text-primary vpn-badge ms-2">Mikrotik</span>
            </h1>
            <div class="content-header-subtitle">
                {{ __('Kelola router Mikrotik untuk layanan VPN pelanggan dan site-to-site.') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshRouters">
                <i class="fa-solid fa-arrows-rotate me-1"></i>{{ __('Refresh Status') }}
            </button>
            <a href="{{ route('routers.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>{{ __('Tambah Router VPN') }}
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 vpn-stat-card">
                <div class="card-body">
                    <div class="text-muted small mb-1">{{ __('Total Router') }}</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="display-6 fw-semibold">{{ $totalRouters }}</div>
                        <div class="text-primary">
                            <i class="fa-solid fa-shield-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 vpn-stat-card">
                <div class="card-body">
                    <div class="text-muted small mb-1">{{ __('Router Aktif') }}</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="display-6 fw-semibold text-success">{{ $activeRouters }}</div>
                        <div class="text-success">
                            <i class="fa-solid fa-circle-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 vpn-stat-card">
                <div class="card-body">
                    <div class="text-muted small mb-1">{{ __('Router Nonaktif') }}</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="display-6 fw-semibold text-danger">{{ $inactiveRouters }}</div>
                        <div class="text-danger">
                            <i class="fa-solid fa-circle-xmark fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">{{ __('Daftar Router VPN') }}</div>
                <div class="text-muted small">{{ __('Router Mikrotik yang terhubung ke sistem ini.') }}</div>
            </div>
            <div class="d-flex gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-0">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" class="form-control border-0" id="routerSearch" placeholder="{{ __('Cari nama atau host router...') }}">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Nama Router') }}</th>
                            <th>{{ __('Host / IP') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody id="routerTableBody">
                        @forelse ($routers as $router)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $router->name }}
                                </td>
                                <td class="text-muted">
                                    {{ $router->host }}:{{ $router->port }}
                                </td>
                                <td>
                                    @if($router->is_active)
                                        <span class="badge bg-success-subtle text-success">
                                            <span class="status-dot status-dot-online"></span>{{ __('Aktif') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">
                                            <span class="status-dot status-dot-offline"></span>{{ __('Nonaktif') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end vpn-table-actions">
                                    <a href="{{ route('routers.show', $router) }}" class="btn btn-outline-secondary btn-sm me-1">
                                        <i class="fa-solid fa-circle-info me-1"></i>{{ __('Detail') }}
                                    </a>
                                    <button type="button"
                                        class="btn btn-outline-success btn-sm me-1"
                                        onclick="testConnection('{{ route('routers.test-connection', $router) }}', this)">
                                        <i class="fa-solid fa-plug-circle-bolt me-1"></i>{{ __('Test') }}
                                    </button>
                                    <a href="{{ route('routers.edit', $router) }}" class="btn btn-outline-primary btn-sm me-1">
                                        <i class="fa-solid fa-pen-to-square me-1"></i>{{ __('Edit') }}
                                    </a>
                                    <form action="{{ route('routers.destroy', $router) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Are you sure?') }}')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    {{ __('Tidak ada router yang terdaftar.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($routers instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="card-footer bg-body">
                {{ $routers->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function testConnection(url, button) {
        if (!confirm('{{ __('Test connection to this router?') }}')) {
            return;
        }

        var originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>{{ __('Testing...') }}';

        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '{{ csrf_token() }}';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (window.Swal) {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? '{{ __('Berhasil') }}' : '{{ __('Gagal') }}',
                        text: data.message
                    });
                } else {
                    alert(data.message);
                }
            })
            .catch(function () {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: '{{ __('An error occurred while testing connection.') }}'
                    });
                } else {
                    alert('{{ __('An error occurred while testing connection.') }}');
                }
            })
            .finally(function () {
                button.disabled = false;
                button.innerHTML = originalHtml;
            });
    }

    document.getElementById('refreshRouters').addEventListener('click', function () {
        window.location.reload();
    });

    document.getElementById('routerSearch').addEventListener('input', function (e) {
        var filter = e.target.value.toLowerCase();
        var rows = document.querySelectorAll('#routerTableBody tr');

        rows.forEach(function (row) {
            var name = row.cells[0].innerText.toLowerCase();
            var host = row.cells[1].innerText.toLowerCase();
            if (name.includes(filter) || host.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endpush
