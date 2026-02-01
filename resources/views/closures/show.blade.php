@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Closure Details') }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('closures.edit', $closure->id) }}" class="btn btn-warning btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('Edit') }}
                    </a>
                    <a href="{{ route('closures.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        @if($closure->image)
                            <img src="{{ Storage::url($closure->image) }}" alt="{{ $closure->name }}" class="img-fluid rounded shadow-sm border">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center text-muted border rounded" style="width: 100%; height: 200px;">
                                <div class="text-center">
                                    <i class="fa-solid fa-image fa-3x mb-2"></i>
                                    <p class="mb-0 small">{{ __('No Image Available') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th class="ps-0" style="width: 150px;">{{ __('Name') }}</th>
                                    <td>: <span class="fw-bold">{{ $closure->name }}</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">{{ __('Region') }}</th>
                                    <td>: {{ $closure->region ? $closure->region->name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">{{ __('ODC') }}</th>
                                    <td>: 
                                        @if($closure->odc)
                                            <a href="{{ route('odcs.index', ['search' => $closure->odc->name]) }}" class="text-decoration-none">
                                                {{ $closure->odc->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">{{ __('Capacity') }}</th>
                                    <td>: {{ $closure->capacity }} Ports</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">{{ __('Filled') }}</th>
                                    <td>: 
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 10px; max-width: 100px;">
                                                <div class="progress-bar {{ $closure->filled >= $closure->capacity ? 'bg-danger' : 'bg-success' }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $closure->capacity > 0 ? ($closure->filled / $closure->capacity) * 100 : 0 }}%">
                                                </div>
                                            </div>
                                            <span>{{ $closure->filled }} / {{ $closure->capacity }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">{{ __('Coordinates') }}</th>
                                    <td>: 
                                        @if($closure->latitude && $closure->longitude)
                                            <a href="https://www.google.com/maps?q={{ $closure->latitude }},{{ $closure->longitude }}" target="_blank" class="text-decoration-none">
                                                {{ $closure->latitude }}, {{ $closure->longitude }} <i class="fa-solid fa-up-right-from-square small ms-1"></i>
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">{{ __('Description') }}</th>
                                    <td>: {{ $closure->description ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
