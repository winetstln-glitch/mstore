@extends('layouts.app')

@section('title', __('ODC Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header  py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('ODC Management') }}</h5>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end align-items-center">
                        @if(Auth::user()->hasPermission('odc.view'))
                        <a href="{{ route('odcs.export.excel') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Export Excel') }}">
                            <i class="fa-solid fa-file-export me-1"></i> <span class="d-none d-sm-inline">{{ __('Export') }}</span>
                        </a>
                        @endif
                        @if(Auth::user()->hasPermission('odc.create'))
                        <a href="{{ route('odcs.create') }}" class="btn btn-primary btn-sm" title="{{ __('Add ODC') }}">
                            <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-sm-inline">{{ __('Add ODC') }}</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('odcs.index') }}" class="row g-2 g-md-3 mb-3">
                    <div class="col-12 col-md-4 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text  border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
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
                    <div class="col-6 col-md-2 col-lg-2">
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fa-solid fa-filter me-1 d-md-none"></i> {{ __('Filter') }}
                        </button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Name') }}</th>
                                <th scope="col">{{ __('Region') }}</th>
                                <th scope="col">{{ __('OLT') }}</th>
                                <th scope="col">{{ __('Location (Lat, Long)') }}</th>
                                <th scope="col">{{ __('Capacity') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($odcs as $odc)
                                <tr>
                                    <td class="ps-3 cell-truncate" title="{{ $odc->name }}">
                                        <div class="fw-bold">{{ $odc->name }}</div>
                                        <div class="small text-muted my-1">
                                            @if($odc->pon_port) <span class="badge  text-dark border me-1" title="PON Port">{{ $odc->pon_port }}</span> @endif
                                            @if($odc->area) <span class="badge  text-dark border me-1" title="Area">{{ $odc->area }}</span> @endif
                                            @if($odc->color) <span class="badge  text-dark border me-1" title="Color">{{ $odc->color }}</span> @endif
                                            @if($odc->cable_no) <span class="badge  text-dark border" title="Cable No">{{ $odc->cable_no }}</span> @endif
                                        </div>
                                        <div class="small text-muted">{{ $odc->description }}</div>
                                    </td>
                                    <td class="cell-nowrap">
                                        <div class="fw-semibold">{{ $odc->region->name ?? '-' }}</div>
                                    </td>
                                    <td class="cell-nowrap">
                                        <div class="fw-semibold">{{ $odc->olt->name ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $odc->latitude }}, {{ $odc->longitude }}</div>
                                        <a href="https://www.google.com/maps/search/?api=1&query={{ $odc->latitude }},{{ $odc->longitude }}" target="_blank" class="small text-decoration-none">
                                            <i class="fa-solid fa-map-pin me-1"></i> {{ __('View on Maps') }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $odc->capacity }} {{ __('Ports') }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            @if(Auth::user()->hasPermission('odc.edit'))
                                            <a href="{{ route('odcs.edit', $odc) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @endif
                                            
                                            @if(Auth::user()->hasPermission('odc.delete'))
                                            <form action="{{ route('odcs.destroy', $odc) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this ODC?') }}');">
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
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-info-circle me-1"></i> {{ __('No ODCs found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $odcs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
