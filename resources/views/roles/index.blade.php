@extends('layouts.app')

@section('title', __('Role Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">{{ __('Role Management') }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('roles.index') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="{{ __('Search roles...') }}" value="{{ request('search') }}">
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fa-solid fa-search"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Create New Role') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                @php
                                    $currentSort = request('sort', 'created_at');
                                    $currentDirection = request('direction', 'desc');
                                @endphp
                                <th scope="col" class="ps-3">
                                    <a href="{{ route('roles.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $currentSort === 'name' && $currentDirection === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                        {{ __('Role Name') }}
                                        @if($currentSort === 'name')
                                            <i class="fa-solid fa-sort-{{ $currentDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col">
                                    <a href="{{ route('roles.index', array_merge(request()->query(), ['sort' => 'label', 'direction' => $currentSort === 'label' && $currentDirection === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                        {{ __('Label') }}
                                        @if($currentSort === 'label')
                                            <i class="fa-solid fa-sort-{{ $currentDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col">
                                    <a href="{{ route('roles.index', array_merge(request()->query(), ['sort' => 'users_count', 'direction' => $currentSort === 'users_count' && $currentDirection === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                        {{ __('Users Count') }}
                                        @if($currentSort === 'users_count')
                                            <i class="fa-solid fa-sort-{{ $currentDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col">
                                    <a href="{{ route('roles.index', array_merge(request()->query(), ['sort' => 'permissions_count', 'direction' => $currentSort === 'permissions_count' && $currentDirection === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                        {{ __('Permissions') }}
                                        @if($currentSort === 'permissions_count')
                                            <i class="fa-solid fa-sort-{{ $currentDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fa-solid fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                            <tr>
                                <td class="ps-3 fw-medium">
                                    {{ $role->name }}
                                    @if(in_array($role->name, ['kasir-wash', 'karyawan-wash']))
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">
                                            {{ __('Teknisi + Wash') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $role->label }}
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        {{ $role->users_count }} {{ __('Users') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        {{ $role->permissions_count }} {{ __('Permissions') }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        
                                        @if(!in_array($role->name, ['admin', 'customer']))
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this role?') }}');">
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
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                    {{ __('No roles found.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection