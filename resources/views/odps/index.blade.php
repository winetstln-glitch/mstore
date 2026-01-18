@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('ODP Management') }}</h5>
                @if(Auth::user()->hasPermission('map.manage'))
                <a href="{{ route('odps.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Add ODP') }}
                </a>
                @endif
            </div>

            <div class="card-body">
                {{-- Alerts handled by SweetAlert in Layout --}}

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Location (Lat, Long)') }}</th>
                                <th scope="col">{{ __('Capacity') }}</th>
                                <th scope="col">{{ __('Filled') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($odps as $odp)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $odp->name }}</div>
                                        <div class="small text-muted">{{ $odp->description }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $odp->latitude }}, {{ $odp->longitude }}</div>
                                        <a href="https://www.google.com/maps/search/?api=1&query={{ $odp->latitude }},{{ $odp->longitude }}" target="_blank" class="small text-decoration-none">
                                            <i class="fa-solid fa-map-pin me-1"></i> {{ __('View on Maps') }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $odp->capacity ?? 'Unlimited' }} {{ $odp->capacity ? __('Ports') : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            {{ $odp->filled }} {{ __('Used') }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            @if(Auth::user()->hasPermission('map.manage'))
                                            <a href="{{ route('odps.edit', $odp) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            
                                            <form action="{{ route('odps.destroy', $odp) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this ODP?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-info-circle me-1"></i> {{ __('No ODPs found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $odps->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
