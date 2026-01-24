@extends('layouts.app')

@section('title', __('ODC Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('ODC Management') }}</h5>
                <form action="{{ route('odcs.index') }}" method="GET" class="d-flex gap-2">
                    <select name="region_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                        <option value="">{{ __('All Regions') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region_id') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div>
                    @if(Auth::user()->hasPermission('odc.view'))
                    <a href="{{ route('odcs.export.excel') }}" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-file-excel me-1"></i> {{ __('Export Excel') }}
                    </a>
                    @endif
                    @if(Auth::user()->hasPermission('odc.create'))
                    <a href="{{ route('odcs.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Add ODC') }}
                    </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-body-tertiary">
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
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $odc->name }}</div>
                                        <div class="small text-muted my-1">
                                            @if($odc->pon_port) <span class="badge bg-light text-dark border me-1" title="PON Port">{{ $odc->pon_port }}</span> @endif
                                            @if($odc->area) <span class="badge bg-light text-dark border me-1" title="Area">{{ $odc->area }}</span> @endif
                                            @if($odc->color) <span class="badge bg-light text-dark border me-1" title="Color">{{ $odc->color }}</span> @endif
                                            @if($odc->cable_no) <span class="badge bg-light text-dark border" title="Cable No">{{ $odc->cable_no }}</span> @endif
                                        </div>
                                        <div class="small text-muted">{{ $odc->description }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $odc->region->name ?? '-' }}</div>
                                    </td>
                                    <td>
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
