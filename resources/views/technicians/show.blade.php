@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Technician Details') }}: {{ $technician->name }}</h5>
                <div class="btn-group">
                    <a href="{{ route('technicians.edit', $technician) }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-edit me-1"></i> {{ __('Edit') }}
                    </a>
                    <a href="{{ route('technicians.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <!-- Personal Info -->
                    <div class="col-md-6">
                        <div class="card h-100 border bg-body-tertiary">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">{{ __('Personal Information') }}</h6>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Email') }}</small>
                                        <span class="fw-medium">{{ $technician->email }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Phone') }}</small>
                                        <span class="fw-medium">{{ $technician->phone ?? 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Telegram Chat ID') }}</small>
                                        <span class="fw-medium">{{ $technician->telegram_chat_id ?? 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Status') }}</small>
                                        <span class="badge {{ $technician->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                            {{ $technician->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">{{ __('Joined') }}</small>
                                        <span class="fw-medium">{{ $technician->created_at->translatedFormat('d M Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats/Summary -->
                    <div class="col-md-6">
                        <div class="card h-100 border bg-body-tertiary">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">{{ __('Performance Summary') }}</h6>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded border text-center">
                                            <small class="text-muted d-block mb-1">{{ __('Total Tickets') }}</small>
                                            <span class="h4 fw-bold text-primary mb-0">{{ $technician->tickets()->count() }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded border text-center">
                                            <small class="text-muted d-block mb-1">{{ __('Total Installations') }}</small>
                                            <span class="h4 fw-bold text-success mb-0">{{ $technician->installations()->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Assignments -->
                <div class="mt-4">
                    <h5 class="fw-bold mb-3">{{ __('Active Assignments') }}</h5>
                    
                    @if($technician->tickets()->whereIn('status', ['assigned', 'in_progress'])->count() > 0 || $technician->installations()->whereIn('status', ['assigned', 'survey', 'installation'])->count() > 0)
                        <div class="row g-4">
                            <!-- Active Tickets -->
                            <div class="col-lg-6">
                                <div class="card border">
                                    <div class="card-header bg-body-tertiary py-2">
                                        <h6 class="mb-0 fw-bold">{{ __('Active Tickets') }}</h6>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        @forelse($technician->tickets()->whereIn('status', ['assigned', 'in_progress'])->get() as $ticket)
                                            <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 text-primary text-truncate" style="max-width: 70%;">{{ $ticket->subject }}</h6>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle text-uppercase" style="font-size: 0.7rem;">
                                                        {{ $ticket->status }}
                                                    </span>
                                                </div>
                                                <small class="text-muted">{{ $ticket->customer->name ?? 'Unknown Customer' }}</small>
                                            </a>
                                        @empty
                                            <div class="list-group-item text-center text-muted py-3">{{ __('No active tickets.') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <!-- Active Installations -->
                            <div class="col-lg-6">
                                <div class="card border">
                                    <div class="card-header bg-body-tertiary py-2">
                                        <h6 class="mb-0 fw-bold">{{ __('Active Installations') }}</h6>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        @forelse($technician->installations()->whereIn('status', ['assigned', 'survey', 'installation'])->get() as $installation)
                                            <a href="{{ route('installations.show', $installation) }}" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 text-primary text-truncate" style="max-width: 70%;">{{ $installation->customer->name ?? 'Unknown Customer' }}</h6>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle text-uppercase" style="font-size: 0.7rem;">
                                                        {{ $installation->status }}
                                                    </span>
                                                </div>
                                                <small class="text-muted">{{ $installation->service_package ?? 'N/A' }}</small>
                                            </a>
                                        @empty
                                            <div class="list-group-item text-center text-muted py-3">{{ __('No active installations.') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle me-1"></i> {{ __('No active assignments currently.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
