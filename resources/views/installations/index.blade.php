@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Installation Management') }}</h5>
                <a href="{{ route('installations.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Add Installation') }}
                </a>
            </div>

            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" action="{{ route('installations.index') }}" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary border-end-0"><i class="fa-solid fa-search text-body-secondary"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('Search customer...') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach(['registered', 'survey', 'approved', 'installation', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ __(ucfirst($status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="technician_id" class="form-select">
                            <option value="">{{ __('All Technicians') }}</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-dark w-100">{{ __('Filter') }}</button>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th scope="col" class="ps-3">ID</th>
                                <th scope="col">{{ __('Customer') }}</th>
                                <th scope="col">{{ __('Plan Date') }}</th>
                                <th scope="col">{{ __('Technician') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installations as $installation)
                                <tr>
                                    <td class="ps-3 text-body-secondary fw-medium">#{{ $installation->id }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $installation->customer->name }}</div>
                                        <div class="small text-body-secondary">{{ Str::limit($installation->customer->address, 30) }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $installation->plan_date ? $installation->plan_date->translatedFormat('d M Y') : __('Not Set') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $installation->technician ? $installation->technician->name : __('Unassigned') }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($installation->status) {
                                                'completed' => 'bg-success-subtle text-success border-success-subtle',
                                                'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                'installation' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                'survey' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                'approved' => 'bg-info-subtle text-info border-info-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                        @endphp
                                        <span class="badge border {{ $statusClass }}">
                                            {{ __(ucfirst($installation->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            <a href="{{ route('installations.show', $installation) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('installations.edit', $installation) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('installations.destroy', $installation) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this installation?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="{{ __('Delete') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-network-wired fa-2x opacity-25"></i></div>
                                        {{ __('No installations found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($installations instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $installations->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
