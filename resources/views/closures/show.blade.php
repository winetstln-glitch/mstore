@extends('layouts.app')

@section('title', __('Closure Details'))

@section('content')
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 border-top border-4 border-info h-100">
            <div class="card-header bg-body-tertiary py-3">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Closure Information') }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">{{ __('Name') }}</label>
                    <div class="fs-5 fw-bold">{{ $closure->name }}</div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">{{ __('Parent Connection') }}</label>
                    <div>
                        @if($closure->parent)
                            @php
                                $parentType = class_basename($closure->parent_type);
                                $badgeClass = $parentType === 'Olt' ? 'bg-primary' : ($parentType === 'Odc' ? 'bg-success' : 'bg-secondary');
                                $route = $parentType === 'Olt' ? route('olt.show', $closure->parent_id) : ($parentType === 'Odc' ? '#' : '#'); // Adjust routes as needed
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $parentType }}</span>
                            <a href="{{ $route }}" class="fw-semibold ms-1 text-decoration-none">{{ $closure->parent->name }}</a>
                        @else
                            <span class="text-muted">{{ __('No Parent Connected') }}</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">{{ __('Coordinates') }}</label>
                    <div>
                        @if($closure->coordinates)
                            {{ $closure->coordinates }}
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $closure->coordinates }}" target="_blank" class="ms-2 small text-decoration-none">
                                <i class="fa-solid fa-map-pin"></i> {{ __('Maps') }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small text-uppercase fw-bold">{{ __('Description') }}</label>
                    <div class="text-muted">{{ $closure->description ?? '-' }}</div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('closures.edit', $closure) }}" class="btn btn-primary w-100">
                        <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('Edit') }}
                    </a>
                    <a href="{{ route('closures.index') }}" class="btn btn-secondary w-100">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-4">
        <!-- Connected ODCs -->
        <div class="card shadow-sm border-0 border-top border-4 border-success mb-4">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Connected ODCs') }}</h5>
                <span class="badge bg-success rounded-pill">{{ $closure->odcs->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">{{ __('Name') }}</th>
                                <th>{{ __('Area') }}</th>
                                <th>{{ __('Capacity') }}</th>
                                <th class="text-end pe-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closure->odcs as $odc)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $odc->name }}</td>
                                    <td>{{ $odc->area }}</td>
                                    <td>{{ $odc->capacity }}</td>
                                    <td class="text-end pe-3">
                                        {{-- <a href="{{ route('odcs.edit', $odc) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-solid fa-eye"></i>
                                        </a> --}}
                                        <!-- Assuming route odcs.edit exists -->
                                        <a href="#" class="btn btn-sm btn-outline-secondary disabled"><i class="fa-solid fa-link"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">{{ __('No ODCs connected via this closure.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Connected ODPs -->
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Connected ODPs') }}</h5>
                <span class="badge bg-warning text-dark rounded-pill">{{ $closure->odps->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">{{ __('Name') }}</th>
                                <th>{{ __('Region') }}</th>
                                <th>{{ __('Capacity') }}</th>
                                <th class="text-end pe-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closure->odps as $odp)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $odp->name }}</td>
                                    <td>{{ $odp->region->name ?? '-' }}</td>
                                    <td>{{ $odp->capacity }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('odps.show', $odp) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">{{ __('No ODPs connected via this closure.') }}</td>
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
