@extends('layouts.app')

@section('title', __('Role Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">{{ __('Role Management') }}</h5>
                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Create New Role') }}
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Role Name') }}</th>
                                <th scope="col">{{ __('Label') }}</th>
                                <th scope="col">{{ __('Users Count') }}</th>
                                <th scope="col">{{ __('Permissions') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td class="ps-3 fw-medium">
                                    {{ $role->name }}
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
                                        {{ $role->permissions->count() }} {{ __('Permissions') }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        
                                        @if($role->name !== 'admin')
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
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
                            @endforeach
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
