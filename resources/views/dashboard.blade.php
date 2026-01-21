@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-0 fw-bold text-body-emphasis">
                    {{ __('Dashboard Overview') }}
                    <span class="visually-hidden">Dashboard Overview</span>
                </h4>
                <p class="text-body-secondary small mb-0">{{ __('Welcome back, :name!', ['name' => Auth::user()->name]) }}</p>
            </div>
            <a href="{{ route('finance.index') }}" class="btn btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-2"></i> {{ __('New Report') }}
            </a>
        </div>
    </div>
</div>

@if(Auth::user()->hasPermission('attendance.view'))
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-4 border-primary">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">{{ __('My Attendance Today') }}</h5>
                    <p class="mb-0 text-muted small">
                        @if($todayAttendance)
                            <span class="badge bg-{{ $todayAttendance->status == 'present' ? 'success' : ($todayAttendance->status == 'late' ? 'warning' : 'secondary') }}">
                                {{ __(ucfirst($todayAttendance->status)) }}
                            </span>
                            <span class="ms-2">
                                <i class="fa-solid fa-clock me-1"></i> {{ __('In') }}: {{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }}
                                @if($todayAttendance->clock_out)
                                    | <i class="fa-solid fa-clock me-1"></i> {{ __('Out') }}: {{ \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') }}
                                @endif
                            </span>
                        @else
                            <span class="badge bg-secondary">{{ __('Not Present Yet') }}</span>
                            <span class="ms-2 text-muted">{{ __('You have not clocked in today.') }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm">
                        @if(!$todayAttendance)
                            <i class="fa-solid fa-sign-in-alt"></i> <span class="d-none d-md-inline ms-1">{{ __('Clock In') }}</span>
                        @elseif(!$todayAttendance->clock_out)
                            <i class="fa-solid fa-sign-out-alt"></i> <span class="d-none d-md-inline ms-1">{{ __('Clock Out') }}</span>
                        @else
                            <i class="fa-solid fa-fingerprint"></i> <span class="d-none d-md-inline ms-1">{{ __('Attendance') }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <!-- Customers -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Customers') }}</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_customers'] }}</h3>
                <div class="small text-success">
                    <i class="fa-solid fa-arrow-trend-up me-1"></i>
                    <span>+{{ $stats['new_customers_this_month'] }} {{ __('this month') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Active Tickets') }}</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['open_tickets'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ $stats['tickets_today'] }} {{ __('new today') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Installations -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Pending Installs') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['pending_installations'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Scheduled for this week') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue (Placeholder) -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted small fw-bold mb-0">{{ __('Network Status') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-server"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">98.9%</h3>
                <div class="small text-success">
                    <i class="fa-solid fa-check me-1"></i>
                    <span>{{ __('Operational') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Recent Tickets -->
    <div class="col-lg-8">
        <!-- Monthly Recap Section -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold">{{ __('Monthly Ticket Recap') }} ({{ date('Y') }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="ps-3">{{ __('Month') }}</th>
                                <th class="text-center">{{ __('Total') }}</th>
                                <th class="text-center">{{ __('Resolved') }}</th>
                                <th class="text-center">{{ __('Open/Pending') }}</th>
                                <th class="text-end pe-3">{{ __('Completion Rate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticketRecap as $recap)
                                @if($recap['total'] > 0 || $loop->iteration <= date('n'))
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $recap['month'] }}</td>
                                    <td class="text-center">{{ $recap['total'] }}</td>
                                    <td class="text-center text-success">{{ $recap['resolved'] }}</td>
                                    <td class="text-center text-warning">{{ $recap['open'] }}</td>
                                    <td class="text-end pe-3">
                                        @if($recap['total'] > 0)
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div class="progress" style="width: 60px; height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($recap['resolved'] / $recap['total']) * 100 }}%"></div>
                                                </div>
                                                <small class="text-body-secondary">{{ round(($recap['resolved'] / $recap['total']) * 100) }}%</small>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">{{ __('Recent Support Tickets') }}</h6>
                <a href="{{ route('tickets.index') }}" class="btn btn-link btn-sm text-decoration-none">{{ __('View All') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-tertiary">
                        <tr>
                            <th class="ps-4">{{ __('Ticket ID') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Subject') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTickets as $ticket)
                        <tr>
                            <td class="ps-4"><span class="fw-medium">#{{ $ticket->ticket_number }}</span></td>
                            <td>{{ $ticket->customer->name }}</td>
                            <td>{{ Str::limit($ticket->subject, 30) }}</td>
                            <td>
                                @if($ticket->status === 'open')
                                    <span class="badge bg-danger-subtle text-danger">{{ __('Open') }}</span>
                                @elseif($ticket->status === 'closed')
                                    <span class="badge bg-success-subtle text-success">{{ __('Closed') }}</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">{{ ucfirst($ticket->status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $ticket->created_at->format('d M H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">{{ __('No recent tickets found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Info & Installations -->
    <div class="col-lg-4">
        <!-- Info Card -->
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">{{ __('System Information') }}</h5>
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3 opacity-75"><i class="fa-regular fa-clock fa-2x"></i></div>
                    <div>
                        <div class="small opacity-75 text-uppercase">{{ __('Server Time') }}</div>
                        <div class="fw-bold">{{ now()->format('H:i:s') }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3 opacity-75"><i class="fa-regular fa-calendar fa-2x"></i></div>
                    <div>
                        <div class="small opacity-75 text-uppercase">{{ __('Date') }}</div>
                        <div class="fw-bold">{{ now()->format('l, d F Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Installations -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold">{{ __('Upcoming Installations') }}</h6>
            </div>
            <div class="list-group list-group-flush">
                @forelse($upcomingInstallations as $install)
                <div class="list-group-item px-4 py-3 border-0 d-flex align-items-start">
                    <div class="bg-body-secondary rounded-circle p-2 me-3 text-center" style="width: 40px; height: 40px;">
                        <span class="fw-bold text-primary">{{ $install->plan_date ? $install->plan_date->format('d') : '-' }}</span>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 text-body-emphasis fw-semibold">{{ $install->customer->name }}</h6>
                        <p class="mb-1 small text-body-secondary"><i class="fa-solid fa-location-dot me-1"></i> {{ Str::limit($install->customer->address, 30) }}</p>
                        <span class="badge bg-info-subtle text-info">{{ $install->plan_date ? $install->plan_date->format('H:i') : 'TBD' }} WIB</span>
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-muted small">
                    {{ __('No upcoming installations.') }}
                </div>
                @endforelse
            </div>
            <div class="card-footer bg-transparent border-0 text-center pb-3">
                @if (Route::has('installations.index'))
                    <a href="{{ route('installations.index') }}" class="text-decoration-none small fw-bold">{{ __('View Calendar') }} <i class="fa-solid fa-arrow-right ms-1"></i></a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
