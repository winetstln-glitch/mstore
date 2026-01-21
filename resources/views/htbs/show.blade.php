@extends('layouts.app')

@section('title', $htb->name)

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-top border-4 border-primary mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">{{ $htb->name }}</h5>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('Uplink') }}</span>
                        <span class="fw-medium text-end">
                            @if($htb->parent_htb_id)
                                <div class="small text-muted">{{ __('HTB') }}</div>
                                <a href="{{ route('htbs.show', $htb->parent_htb_id) }}" class="text-decoration-none">{{ $htb->parent->name ?? '-' }}</a>
                            @elseif($htb->odp_id)
                                <div class="small text-muted">{{ __('ODP') }}</div>
                                <a href="{{ route('odps.show', $htb->odp_id) }}" class="text-decoration-none">{{ $htb->odp->name ?? '-' }}</a>
                            @else
                                -
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('Capacity') }}</span>
                        <span class="fw-medium">{{ $htb->capacity ?? 'Unlimited' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('Filled') }}</span>
                        <span class="fw-medium">{{ $htb->filled }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('Coordinates') }}</span>
                        <span class="fw-medium text-end">
                            @if($htb->latitude && $htb->longitude)
                                {{ number_format($htb->latitude, 6) }},<br>{{ number_format($htb->longitude, 6) }}
                            @else
                                -
                            @endif
                        </span>
                    </li>
                    @if($htb->description)
                    <li class="list-group-item px-0">
                        <div class="text-muted mb-1">{{ __('Description') }}</div>
                        <div class="small">{{ $htb->description }}</div>
                    </li>
                    @endif
                </ul>
                
                <div class="d-grid gap-2 mt-3">
                    @if($htb->latitude && $htb->longitude)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $htb->latitude }},{{ $htb->longitude }}" target="_blank" class="btn btn-outline-primary">
                        <i class="fa-solid fa-map-location-dot me-2"></i> {{ __('Open in Maps') }}
                    </a>
                    @endif
                    
                    @can('htb.edit')
                    <a href="{{ route('htbs.edit', $htb) }}" class="btn btn-primary">
                        <i class="fa-solid fa-pen-to-square me-2"></i> {{ __('Edit HTB') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-body-tertiary py-3">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Child HTBs') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th>{{ __('Capacity') }}</th>
                                <th class="text-end">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($htb->children as $child)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $child->name }}</div>
                                        <div class="small text-muted">{{ $child->description }}</div>
                                    </td>
                                    <td>
                                        @if($child->latitude && $child->longitude)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $child->latitude }},{{ $child->longitude }}" target="_blank" class="small text-decoration-none">
                                                <i class="fa-solid fa-map-pin me-1"></i> {{ __('Maps') }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $child->capacity ?? 'Unlimited' }}
                                        </span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                                            {{ $child->filled }} {{ __('Used') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('htbs.show', $child) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">
                                        {{ __('No child HTBs.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-body-tertiary py-3">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Connected Customers') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Address') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($htb->customers as $customer)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $customer->name }}</div>
                                        <div class="small text-muted">{{ $customer->phone }}</div>
                                    </td>
                                    <td>{{ Str::limit($customer->address, 30) }}</td>
                                    <td>
                                        @if($customer->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                                        @elseif($customer->status === 'suspend')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('Suspend') }}</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ ucfirst($customer->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        {{ __('No customers connected to this HTB.') }}
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
