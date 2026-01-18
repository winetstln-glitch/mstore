@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">{{ __('OLT Management') }}</h5>
                @if(Auth::user()->hasPermission('olt.create'))
                <a href="{{ route('olt.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Add OLT') }}
                </a>
                @endif
            </div>

            <div class="card-body">
                {{-- Alerts handled by SweetAlert in Layout --}}

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Host / IP Address') }}</th>
                                <th scope="col">{{ __('Connection') }}</th>
                                <th scope="col">{{ __('Type') }}</th>
                                <th scope="col">{{ __('Brand') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($olts as $olt)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">
                                            <a href="{{ route('olt.show', $olt) }}" class="text-decoration-none text-dark">
                                                {{ $olt->name }}
                                            </a>
                                        </div>
                                        <div class="small text-muted">{{ $olt->description }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $olt->host }}:{{ $olt->port }}</div>
                                        <button onclick="testConnection({{ $olt->id }})" class="btn btn-link btn-sm p-0 text-decoration-none">Test Login</button>
                                    </td>
                                    <td>
                                        <span id="status-{{ $olt->id }}" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            Checking...
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle text-uppercase">
                                            {{ $olt->type }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-uppercase">{{ $olt->brand }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $olt->is_active ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' }} border">
                                            {{ $olt->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            @if(Auth::user()->hasPermission('olt.view'))
                                            <a href="{{ route('olt.onus.index', $olt) }}" class="btn btn-sm btn-outline-success" title="ONUs">
                                                <i class="fa-solid fa-network-wired"></i>
                                            </a>
                                            @endif
                                            
                                            @if(Auth::user()->hasPermission('olt.edit'))
                                            <a href="{{ route('olt.edit', $olt) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @endif
                                            
                                            @if(Auth::user()->hasPermission('olt.delete'))
                                            <form action="{{ route('olt.destroy', $olt) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this OLT?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa-solid fa-server fa-3x mb-3"></i>
                                            <p class="mb-0">{{ __('No OLT devices found.') }}</p>
                                        </div>
                                    </td>
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
    document.addEventListener('DOMContentLoaded', function () {
        var oltIds = @json($olts->pluck('id'));

        oltIds.forEach(function (id) {
            var badge = document.getElementById('status-' + id);
            if (!badge) {
                return;
            }

            fetch('{{ url('olt') }}/' + id + '/check-status')
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    var status = data.status || 'offline';
                    var message = data.message || status;

                    badge.textContent = message;

                    badge.classList.remove(
                        'bg-secondary-subtle',
                        'text-secondary',
                        'border-secondary-subtle',
                        'bg-success-subtle',
                        'text-success',
                        'border-success-subtle',
                        'bg-danger-subtle',
                        'text-danger',
                        'border-danger-subtle'
                    );

                    if (status === 'online') {
                        badge.classList.add('bg-success-subtle', 'text-success', 'border-success-subtle');
                    } else {
                        badge.classList.add('bg-danger-subtle', 'text-danger', 'border-danger-subtle');
                    }
                })
                .catch(function () {
                    badge.textContent = 'Error';
                    badge.classList.remove(
                        'bg-secondary-subtle',
                        'text-secondary',
                        'border-secondary-subtle'
                    );
                    badge.classList.add('bg-danger-subtle', 'text-danger', 'border-danger-subtle');
                });
        });
    });

    function testConnection(id) {
        var btn = event.target.closest('button');
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Testing...';
        btn.disabled = true;

        fetch('{{ route('olt.test_connection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id: id })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                alert('Connection Successful: ' + data.message);
            } else {
                alert('Connection Failed: ' + data.message);
            }
        })
        .catch(function (error) {
            alert('Error: ' + error.message);
        })
        .finally(function () {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
</script>
@endpush
