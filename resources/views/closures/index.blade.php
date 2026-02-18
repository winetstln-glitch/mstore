@extends('layouts.app')

@section('title', __('Closure Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Closure Management') }}</h5>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end align-items-center">
                        @if(Auth::user()->hasPermission('closure.create'))
                        <a href="{{ route('closures.create') }}" class="btn btn-primary btn-sm" title="{{ __('Add') }}">
                            <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-sm-inline">{{ __('Add') }}</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('closures.index') }}" class="row g-2 g-md-3 mb-3">
                    <div class="col-12 col-md-4 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('Search...') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="region_id" class="form-select">
                            <option value="">{{ __('All Regions') }}</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" {{ request('region_id') == $region->id ? 'selected' : '' }}>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="odc_id" class="form-select">
                            <option value="">{{ __('All ODCs') }}</option>
                            @foreach($odcs as $odc)
                                <option value="{{ $odc->id }}" {{ request('odc_id') == $odc->id ? 'selected' : '' }}>
                                    {{ $odc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-lg-2">
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fa-solid fa-filter me-1 d-md-none"></i> {{ __('Filter') }}
                        </button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Image') }}</th>
                                <th scope="col">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Region') }}</th>
                                <th scope="col">{{ __('ODC') }}</th>
                                <th scope="col">{{ __('Location') }}</th>
                                <th scope="col">{{ __('Capacity') }}</th>
                                <th scope="col">{{ __('Filled') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closures as $closure)
                                <tr>
                                    <td class="ps-3">
                                        @if($closure->image)
                                            <img src="{{ Storage::url($closure->image) }}" alt="{{ $closure->name }}" class="img-thumbnail img-thumb">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center text-muted border rounded" style="width: 50px; height: 50px;">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-medium cell-truncate" title="{{ $closure->name }}">{{ $closure->name }}</td>
                                    <td class="cell-nowrap">{{ $closure->region->name ?? '-' }}</td>
                                    <td class="cell-nowrap">{{ $closure->odc->name ?? '-' }}</td>
                                    <td>
                                        @if($closure->latitude && $closure->longitude)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $closure->latitude }},{{ $closure->longitude }}" target="_blank" class="text-decoration-none">
                                                <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                                {{ number_format($closure->latitude, 6) }}, {{ number_format($closure->longitude, 6) }}
                                            </a>
                                        @else
                                            <span class="text-muted"><i class="fa-solid fa-ban me-1"></i> {{ __('Not set') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $closure->capacity ?? 'Unlimited' }} {{ $closure->capacity ? __('Ports') : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            {{ $closure->filled }} {{ __('Used') }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            @if(Auth::user()->hasPermission('closure.edit'))
                                            <a href="{{ route('closures.edit', $closure) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @endif
                                            
                                            @if(Auth::user()->hasPermission('closure.delete'))
                                            <form action="{{ route('closures.destroy', $closure) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this Closure?') }}');">
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
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                                        {{ __('No Closures found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $closures->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
