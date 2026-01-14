@extends('layouts.app')

@section('content')
<div class="row">
    <!-- Main Ticket Info -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">{{ $ticket->subject }}</h4>
                        <p class="text-muted small mb-0">Ticket #{{ $ticket->ticket_number }}</p>
                    </div>
                    <div class="btn-group">
                        @can('ticket.edit')
                        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-warning text-white btn-sm">
                            <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('Edit') }}
                        </a>
                        @endcan
                        <a href="{{ route('tickets.index') }}" class="btn btn-light border btn-sm">
                            {{ __('Back') }}
                        </a>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Status') }}</small>
                        @php
                            $statusClass = match($ticket->status) {
                                'open' => 'bg-danger-subtle text-danger',
                                'solved' => 'bg-success-subtle text-success',
                                'closed' => 'bg-secondary-subtle text-secondary',
                                'in_progress' => 'bg-info-subtle text-info',
                                default => 'bg-warning-subtle text-warning'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }} mt-1">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </span>
                    </div>
                    <div class="col-md-3 col-6">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Priority</small>
                        @php
                            $priorityClass = match($ticket->priority) {
                                'high' => 'bg-danger-subtle text-danger',
                                'medium' => 'bg-warning-subtle text-warning',
                                default => 'bg-primary-subtle text-primary'
                            };
                        @endphp
                        <span class="badge {{ $priorityClass }} mt-1">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </div>
                    <div class="col-md-3 col-6">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Type</small>
                        <span class="d-block mt-1 fw-medium">{{ ucfirst(str_replace('_', ' ', $ticket->type)) }}</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Created At</small>
                        <span class="d-block mt-1 fw-medium">{{ $ticket->created_at->format('d M Y H:i') }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">{{ __('Description') }}</h6>
                    <div class="bg-body-tertiary p-3 rounded text-body-secondary" style="white-space: pre-line;">
                        {{ $ticket->description ?? __('No description provided.') }}
                    </div>
                </div>

                @if($ticket->location)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0">{{ __('Location / Notes') }}</h6>
                            @if(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete')))
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                                    <i class="fa-solid fa-pen-to-square"></i> {{ __('Edit') }}
                                </button>
                            @endif
                        </div>
                        <p class="text-body-secondary">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket->location) }}" target="_blank" class="text-decoration-none">
                                <i class="fa-solid fa-map-location-dot me-1"></i> {{ $ticket->location }} <i class="fa-solid fa-arrow-up-right-from-square ms-1 small"></i>
                            </a>
                        </p>
                    </div>
                @elseif(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete')))
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0">{{ __('Location / Notes') }}</h6>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                                <i class="fa-solid fa-plus"></i> {{ __('Add Location') }}
                            </button>
                        </div>
                        <p class="text-muted small fst-italic">{{ __('No location set.') }}</p>
                    </div>
                @endif

                <!-- Photo Proof Section -->
                <div class="mb-4">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">{{ __('Completion Photos') }}</h6>
                    
                    @if($ticket->photo_before || $ticket->photo_proof)
                        <div class="row g-3 mb-3">
                            @if($ticket->photo_before)
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light py-2">
                                        <small class="fw-bold text-uppercase">{{ __('Before') }}</small>
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <img src="{{ Storage::url($ticket->photo_before) }}" class="img-fluid rounded border shadow-sm" alt="Photo Before Work" style="max-height: 300px;">
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($ticket->photo_proof)
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light py-2">
                                        <small class="fw-bold text-uppercase">{{ __('After') }}</small>
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <img src="{{ Storage::url($ticket->photo_proof) }}" class="img-fluid rounded border shadow-sm" alt="Photo After Work" style="max-height: 300px;">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @elseif(in_array($ticket->status, ['solved', 'closed']))
                        <div class="alert alert-secondary py-2 small">
                            <i class="fa-solid fa-info-circle me-1"></i> {{ __('Ticket is :status. No photos available.', ['status' => $ticket->status]) }}
                        </div>
                    @endif

                    @if(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete')))
                        <div class="bg-light p-3 rounded border">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-check-circle text-success me-1"></i> {{ __('Mark as Completed') }}</h6>
                            <form action="{{ route('tickets.complete', $ticket) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2 mb-md-0">
                                        <label for="photo_before" class="form-label small fw-bold">{{ __('Photo Before') }} <span class="text-muted small fw-normal">({{ __('Optional') }})</span></label>
                                        <input type="file" class="form-control form-control-sm" id="photo_before" name="photo_before" accept="image/*">
                                        @error('photo_before')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="photo_proof" class="form-label small fw-bold">{{ __('Photo After') }} <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control form-control-sm" id="photo_proof" name="photo_proof" required accept="image/*">
                                        @error('photo_proof')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label small fw-bold">{{ __('Completion Notes') }} ({{ __('Optional') }})</label>
                                    <textarea class="form-control form-control-sm" id="description" name="description" rows="2" placeholder="{{ __('Describe the solution...') }}"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('{{ __('Are you sure you want to mark this ticket as completed?') }}')">
                                    <i class="fa-solid fa-check me-1"></i> {{ __('Complete Ticket') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Activity Logs -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Activity Log') }}</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush border-start border-3 ms-2">
                    @forelse($ticket->logs->sortByDesc('created_at') as $log)
                        <li class="list-group-item border-0 ps-4 py-3 position-relative">
                            <div class="position-absolute top-0 start-0 translate-middle-x mt-4 bg-body border border-2 border-primary rounded-circle" style="width: 12px; height: 12px; left: -1.5px;"></div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold text-body-emphasis">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</h6>
                                    <p class="mb-1 text-body-secondary small">{{ $log->description }}</p>
                                    <small class="text-body-secondary fst-italic">by {{ $log->user->name ?? 'System' }}</small>
                                </div>
                                <small class="text-body-secondary">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item border-0 text-body-secondary fst-italic">{{ __('No activity recorded.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <!-- Customer Info -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Customer Details') }}</h6>
            </div>
            <div class="card-body">
                @if($ticket->customer)
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                            <i class="fa-solid fa-user fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $ticket->customer->name }}</h6>
                            <small class="text-body-secondary">{{ __('Customer') }}</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket->customer->address) }}" target="_blank" class="text-decoration-none text-body-secondary">
                                <i class="fa-solid fa-location-dot me-2"></i> {{ $ticket->customer->address }}
                            </a>
                        </li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-body-secondary"></i> {{ $ticket->customer->phone }}</li>
                        <li class="mb-2"><i class="fa-solid fa-box me-2 text-body-secondary"></i> {{ $ticket->customer->package }}</li>
                    </ul>
                    <div class="d-grid mt-3">
                        <a href="{{ route('customers.edit', $ticket->customer) }}" class="btn btn-outline-primary btn-sm">{{ __('View Customer') }}</a>
                    </div>
                @else
                    <p class="text-body-secondary small mb-0">{{ __('No customer assigned.') }}</p>
                @endif
            </div>
        </div>

        <!-- Network Info -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Network Details') }}</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('ODP') }}</small>
                        @if($ticket->odp)
                            <span class="fw-medium">{{ $ticket->odp->name }}</span>
                            @if($ticket->odp->region)
                                <br><small class="text-muted">{{ $ticket->odp->region->name }}</small>
                            @endif
                        @else
                            <span class="text-muted small fst-italic">{{ __('Not assigned') }}</span>
                        @endif
                    </li>
                    <li>
                        <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.7rem;">{{ __('Coordinator') }}</small>
                        @if($ticket->coordinator)
                            <div class="fw-medium">{{ $ticket->coordinator->name }}</div>
                            @if($ticket->coordinator->phone)
                                <small class="d-block"><i class="fa-solid fa-phone me-1 text-muted"></i> {{ $ticket->coordinator->phone }}</small>
                            @endif
                            @if($ticket->coordinator->region)
                                <small class="d-block text-muted">{{ $ticket->coordinator->region->name }}</small>
                            @endif
                        @else
                            <span class="text-muted small fst-italic">{{ __('Not assigned') }}</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        <!-- Technician Info -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold">{{ __('Assigned Technicians') }}</h6>
            </div>
            <div class="card-body">
                @if($ticket->technicians->count() > 0)
                    @foreach($ticket->technicians as $tech)
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                <i class="fa-solid fa-screwdriver-wrench fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $tech->name }}</h6>
                                <small class="text-body-secondary">{{ __('Technician') }}</small>
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <a href="mailto:{{ $tech->email }}" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-envelope me-1"></i> {{ __('Email Technician') }}</a>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3 text-body-secondary">
                        <i class="fa-solid fa-user-xmark fa-2x mb-2 opacity-25"></i>
                        <p class="small mb-0">{{ __('No technicians assigned.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Location Modal -->
@if(!in_array($ticket->status, ['solved', 'closed']) && (Auth::user()->can('ticket.edit') || Auth::user()->can('ticket.complete')))
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLocationModalLabel">{{ __('Edit Location') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tickets.updateLocation', $ticket) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="location" class="form-label">{{ __('Coordinates (Latitude, Longitude)') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="location" name="location" value="{{ $ticket->location }}" placeholder="-6.200000, 106.816666">
                            <button class="btn btn-outline-secondary" type="button" id="getCurrentLocation">
                                <i class="fa-solid fa-crosshairs"></i>
                            </button>
                        </div>
                        <div class="form-text">{{ __('Click the crosshair button to get your current location.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const getCurrentLocationBtn = document.getElementById('getCurrentLocation');
        const locationInput = document.getElementById('location');

        if (getCurrentLocationBtn) {
            getCurrentLocationBtn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    getCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        locationInput.value = `${lat}, ${lng}`;
                        getCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-crosshairs"></i>';
                    }, function(error) {
                        alert('Error getting location: ' + error.message);
                        getCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-crosshairs"></i>';
                    });
                } else {
                    alert('Geolocation is not supported by this browser.');
                }
            });
        }
    });
</script>
@endif
@endsection
