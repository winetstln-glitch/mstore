@extends('layouts.app')

@section('title', __('Ticket Management'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-danger">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Ticket Management') }}</h5>
                <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Create Ticket') }}
                </a>
            </div>

            <div class="card-body">
                <!-- Search and Filter -->
                <form method="GET" action="{{ route('tickets.index') }}" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary border-end-0"><i class="fa-solid fa-search text-body-secondary"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="{{ __('Search ID or subject...') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                            <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>{{ __('Assigned') }}</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                            <option value="solved" {{ request('status') == 'solved' ? 'selected' : '' }}>{{ __('Solved') }}</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="priority" class="form-select">
                            <option value="">{{ __('All Priority') }}</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100">{{ __('Filter') }}</button>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Ticket Info') }}</th>
                                <th scope="col">{{ __('Customer') }}</th>
                                <th scope="col">{{ __('Assigned To') }}</th>
                                <th scope="col">{{ __('Status/Priority') }}</th>
                                <th scope="col" class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">#{{ $ticket->ticket_number }}</div>
                                        <div class="fw-medium">{{ $ticket->subject }}</div>
                                        <div class="small text-body-secondary">{{ $ticket->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $ticket->customer->name ?? __('Unknown') }}</div>
                                        <div class="small text-body-secondary">
                                            @if($ticket->customer && $ticket->customer->address)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket->customer->address) }}" target="_blank" class="text-decoration-none text-body-secondary">
                                                    <i class="fa-solid fa-location-dot me-1"></i> {{ Str::limit($ticket->customer->address, 20) }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($ticket->technicians->count() > 0)
                                            @foreach($ticket->technicians as $tech)
                                                <div class="d-flex align-items-center mb-1">
                                                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                        {{ substr($tech->name, 0, 1) }}
                                                    </div>
                                                    <span class="small">{{ $tech->name }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">{{ __('Unassigned') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @php
                                                $statusClass = match($ticket->status) {
                                                    'open' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                    'solved' => 'bg-success-subtle text-success border-success-subtle',
                                                    'closed' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                                    'in_progress' => 'bg-info-subtle text-info border-info-subtle',
                                                    default => 'bg-warning-subtle text-warning border-warning-subtle'
                                                };
                                                
                                                $priorityClass = match($ticket->priority) {
                                                    'high' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                    'medium' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                    default => 'bg-primary-subtle text-primary border-primary-subtle'
                                                };
                                            @endphp
                                            <span class="badge border {{ $statusClass }} w-auto align-self-start">
                                                {{ __(ucfirst(str_replace('_', ' ', $ticket->status))) }}
                                            </span>
                                            <span class="badge border {{ $priorityClass }} w-auto align-self-start">
                                                {{ __(ucfirst($ticket->priority)) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @if(Auth::user()->hasPermission('ticket.delete'))
                                                <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this ticket?') }}');">
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
                                    <td colspan="5" class="text-center py-5 text-body-secondary">
                                        <div class="mb-2"><i class="fa-solid fa-ticket fa-2x opacity-25"></i></div>
                                        {{ __('No tickets found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($tickets instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $tickets->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
