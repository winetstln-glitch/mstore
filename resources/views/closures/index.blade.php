@extends('layouts.app')

@section('title', __('Closure Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Closure Management') }}</h5>
                <form action="{{ route('closures.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div>
                    @if(Auth::user()->hasPermission('closure.create'))
                    <a href="{{ route('closures.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Add Closure') }}
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
                                <th scope="col">{{ __('Parent') }}</th>
                                <th scope="col">{{ __('Coordinates') }}</th>
                                <th scope="col">{{ __('Description') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closures as $closure)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $closure->name }}</div>
                                    </td>
                                    <td>
                                        @if($closure->parent)
                                            @php
                                                $parentType = class_basename($closure->parent_type);
                                                $badgeClass = $parentType === 'Olt' ? 'bg-primary' : ($parentType === 'Odc' ? 'bg-success' : 'bg-secondary');
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $parentType }}</span>
                                            <span class="fw-semibold ms-1">{{ $closure->parent->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($closure->coordinates)
                                            <div>{{ $closure->coordinates }}</div>
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $closure->coordinates }}" target="_blank" class="small text-decoration-none">
                                                <i class="fa-solid fa-map-pin me-1"></i> {{ __('View on Maps') }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ Str::limit($closure->description, 50) }}
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            @if(Auth::user()->hasPermission('closure.view'))
                                            <a href="{{ route('closures.show', $closure) }}" class="btn btn-sm btn-outline-info" title="{{ __('View') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            @endif
                                            
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
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-box-open fs-1 mb-3 d-block opacity-25"></i>
                                        {{ __('No closures found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $closures->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
