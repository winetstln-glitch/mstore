@extends('layouts.app')

@section('title', __('Coordinators'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Coordinators') }}</h5>
                <a href="{{ route('coordinators.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Add Coordinator') }}
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">{{ __('Name') }}</th>
                                <th class="py-3">{{ __('User Account') }}</th>
                                <th class="py-3">{{ __('Region') }}</th>
                                <th class="py-3">{{ __('Router Server') }}</th>
                                <th class="py-3">{{ __('Phone Number') }}</th>
                                <th class="py-3">{{ __('Address') }}</th>
                                <th class="pe-4 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coordinators as $coordinator)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $coordinator->name }}</td>
                                    <td>
                                        @if($coordinator->user)
                                            <span class="badge bg-info-subtle text-info-emphasis rounded-pill">
                                                {{ $coordinator->user->name }}
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">{{ __('Not Linked') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($coordinator->region)
                                            <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">
                                                {{ $coordinator->region->name }}
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($coordinator->router)
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">
                                                {{ $coordinator->router->name }}
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $coordinator->phone ?: '-' }}</td>
                                    <td>{{ Str::limit($coordinator->address, 30) ?: '-' }}</td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('coordinators.edit', $coordinator) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('coordinators.destroy', $coordinator) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
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
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-user-tie fa-2x mb-3 opacity-25"></i>
                                        <p class="mb-0">{{ __('No coordinators found.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($coordinators->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $coordinators->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
