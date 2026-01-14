@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">{{ __('Regions') }}</h1>
            <a href="{{ route('regions.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Region') }}
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">{{ __('Name') }}</th>
                                <th class="py-3">{{ __('Description') }}</th>
                                <th class="py-3 text-center">{{ __('Coordinators') }}</th>
                                <th class="py-3 text-center">{{ __('ODPs') }}</th>
                                <th class="pe-4 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($regions as $region)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $region->name }}</td>
                                    <td>{{ Str::limit($region->description, 50) ?: '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis rounded-pill">
                                            {{ $region->coordinators_count }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">
                                            {{ $region->odps_count }}
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('regions.edit', $region) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('regions.destroy', $region) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-map-location-dot fa-2x mb-3 opacity-25"></i>
                                        <p class="mb-0">{{ __('No regions found.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($regions->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $regions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
