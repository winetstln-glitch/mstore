@extends('layouts.app')

@section('title', __('HTB Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('HTB Management') }}</h5>
                @can('htb.create')
                <a href="{{ route('htbs.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Add HTB') }}
                </a>
                @endcan
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Uplink') }}</th>
                                <th scope="col">{{ __('Location') }}</th>
                                <th scope="col">{{ __('Capacity') }}</th>
                                <th scope="col">{{ __('Filled') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($htbs as $htb)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $htb->name }}</div>
                                        <div class="small text-muted">{{ $htb->description }}</div>
                                    </td>
                                    <td>
                                        @if($htb->parent_htb_id)
                                            <div class="small text-muted" style="font-size: 0.75rem;">{{ __('From HTB') }}</div>
                                            <a href="{{ route('htbs.show', $htb->parent_htb_id) }}" class="text-decoration-none fw-bold">
                                                <i class="fa-solid fa-sitemap me-1 text-secondary"></i>{{ $htb->parent->name ?? '-' }}
                                            </a>
                                        @elseif($htb->odp_id)
                                            <div class="small text-muted" style="font-size: 0.75rem;">{{ __('From ODP') }}</div>
                                            <a href="{{ route('odps.show', $htb->odp_id) }}" class="text-decoration-none fw-bold">
                                                <i class="fa-solid fa-server me-1 text-secondary"></i>{{ $htb->odp->name ?? '-' }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($htb->latitude && $htb->longitude)
                                            <div>{{ number_format($htb->latitude, 6) }}, {{ number_format($htb->longitude, 6) }}</div>
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $htb->latitude }},{{ $htb->longitude }}" target="_blank" class="small text-decoration-none">
                                                <i class="fa-solid fa-map-pin me-1"></i> {{ __('View on Maps') }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $htb->capacity ?? 'Unlimited' }} {{ $htb->capacity ? __('Ports') : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            {{ $htb->filled }} {{ __('Used') }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            @can('htb.view')
                                            <a href="{{ route('htbs.show', $htb) }}" class="btn btn-sm btn-outline-info" title="{{ __('View') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            @endcan
                                            @can('htb.edit')
                                            <a href="{{ route('htbs.edit', $htb) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @endcan
                                            @can('htb.delete')
                                            <form action="{{ route('htbs.destroy', $htb) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this HTB?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-info-circle me-1"></i> {{ __('No HTBs found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $htbs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
