@extends('layouts.app')

@section('title', __('API Key Management'))

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('API Key Management') }}</h1>
    </div>

    <div class="row">
        <!-- Create Key & List -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Integrate external systems with your NMS') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form action="{{ route('apikeys.store') }}" method="POST" class="form-inline">
                                @csrf
                                <label class="sr-only" for="appName">{{ __('Application Description') }}</label>
                                <div class="input-group mb-2 mr-sm-2 w-100">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">{{ __('Description') }}</div>
                                    </div>
                                    <input type="text" class="form-control" id="appName" name="name" placeholder="{{ __('Example: WHMCS Billing') }}" required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">{{ __('Generate Key') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('API Key (Masked)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($keys as $key)
                                <tr>
                                    <td>{{ $key->name }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" value="{{ Str::mask($key->key, '*', 10) }}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary copy-btn" type="button" data-key="{{ $key->key }}">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($key->is_active)
                                            <span class="badge badge-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $key->created_at->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <form action="{{ route('apikeys.toggle', $key) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $key->is_active ? 'btn-warning' : 'btn-success' }}">
                                                {{ $key->is_active ? __('Deactivate') : __('Activate') }}
                                            </button>
                                        </form>
                                        <form action="{{ route('apikeys.destroy', $key) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this key? Applications using this key will lose access.') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No API Keys created yet.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Brief Documentation') }}</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Base URL:</strong> <code>{{ url('/api/integration') }}</code>
                    </div>
                    
                    <h5 class="mt-4">1. Get All Devices</h5>
                    <p>{{ __('Get list of all registered devices (OLT & Mikrotik).') }}</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=devices</code>
                    </div>

                    <h5 class="mt-4">2. Get OLT Status (Filter PON)</h5>
                    <p>{{ __('Get ONU data on a specific PON. Requires device_id & pon.') }}</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=olt/status&device_id=10&pon=1</code>
                    </div>

                    <h5 class="mt-4">3. Get OLT Status (All PONs)</h5>
                    <p>{{ __('Get ALL ONU data from all PON ports. Requires device_id only.') }}</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=olt/status&device_id=10</code>
                    </div>

                    <h5 class="mt-4">4. Get Mikrotik Status</h5>
                    <p>{{ __('Get Resource status (CPU, Uptime, Memory) and PPPoE & Hotspot user statistics.') }}</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=mikrotik/status&device_id=2</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                navigator.clipboard.writeText(key).then(() => {
                    alert("{{ __('API Key copied to clipboard!') }}");
                });
            });
        });
    });
</script>
@endsection
