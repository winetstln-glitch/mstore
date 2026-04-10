@extends('layouts.app')

@section('title', __('User Management'))

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">{{ __('User Management') }}</h4>
            <p class="text-muted small mb-0">{{ __('Manage system users and their roles.') }}</p>
        </div>
        <div class="d-flex flex-column flex-xl-row gap-2 w-100 justify-content-xl-end align-items-stretch align-items-xl-center">
            <form action="{{ route('users.index') }}" method="GET" class="row g-2 w-100 w-xl-auto align-items-stretch">
                <div class="col-12 col-lg">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search users...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-auto">
                    <select name="role_id" class="form-select form-select-sm">
                        <option value="">{{ __('All Roles') }}</option>
                        @foreach(($roles ?? collect()) as $role)
                            <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-auto d-grid">
                    <button class="btn btn-sm btn-primary text-nowrap" type="submit">
                        <i class="fa-solid fa-search me-1"></i>{{ __('Search') }}
                    </button>
                </div>
                @if(request()->filled('search') || request()->filled('role_id'))
                    <div class="col-6 col-sm-auto d-grid">
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                            <i class="fa-solid fa-rotate-left me-1"></i>{{ __('Reset') }}
                        </a>
                    </div>
                @endif
            </form>

            <div class="d-flex flex-wrap gap-2 w-100 w-xl-auto">
                <a href="{{ route('users.export', request()->query()) }}" class="btn btn-sm btn-outline-success text-nowrap flex-fill flex-sm-grow-0">
                    <i class="fa-solid fa-file-excel me-1"></i> {{ __('Export Excel') }}
                </a>
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-outline-primary text-nowrap flex-fill flex-sm-grow-0">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Create New User') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                {{-- Alerts handled by SweetAlert in Layout --}}

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-responsive-mobile">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 text-uppercase small text-muted border-0">{{ __('Name') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Email') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Role') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Status') }}</th>
                                <th class="text-end pe-3 text-uppercase small text-muted border-0">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td class="ps-3 fw-medium">
                                    {{ $user->name }}
                                    <div class="small text-muted">{{ $user->attendance_card_code ?: $user->username }}</div>
                                </td>
                                <td>
                                    {{ $user->email }}
                                </td>
                                <td>
                                    @if($user->role)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ $user->role->label }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            {{ __('No Role') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('users.id-card', $user) }}" class="btn btn-sm btn-outline-dark" title="ID Card">
                                            <i class="fa-solid fa-id-card"></i>
                                        </a>
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this user?') }}');">
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

                <!-- Pagination -->
                @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $users->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
