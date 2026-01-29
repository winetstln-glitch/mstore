@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')

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
                    <a href="{{ route('attendance.create') }}" class="btn btn-primary">
                        <span class="visually-hidden">{{ __('Clock In/Out') }}</span>
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

    <!-- Active Sessions -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Active Sessions') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-network-wired"></i>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end mb-1">
                    <div>
                        <div class="small text-muted">{{ __('PPPoE') }}</div>
                        <h4 class="fw-bold mb-0">{{ $stats['pppoe_active'] }}</h4>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">{{ __('Hotspot') }}</div>
                        <h4 class="fw-bold mb-0">{{ $stats['hotspot_active'] }}</h4>
                    </div>
                </div>
                <div class="small {{ $stats['router_status'] == 'online' ? 'text-success' : 'text-danger' }} mt-2">
                    <i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i>
                    <span>{{ $stats['router_status'] == 'online' ? __('Router Online') : __('Router Offline') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GenieACS Monitoring Section -->
<div class="row g-4 mb-4">
    <!-- Total Devices -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Total Devices') }}</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="fa-solid fa-server"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0">{{ $stats['genie_total'] }}</h3>
                <small class="text-muted">{{ __('Registered in ACS') }}</small>
            </div>
        </div>
    </div>
    <!-- Online Devices -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Online Devices') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-signal"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0">{{ $stats['genie_online'] }}</h3>
                <small class="text-success"><i class="fa-solid fa-check-circle me-1"></i>{{ __('Connected') }}</small>
            </div>
        </div>
    </div>
    <!-- Offline Devices -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Offline Devices') }}</h6>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0">{{ $stats['genie_offline'] }}</h3>
                <small class="text-danger"><i class="fa-solid fa-times-circle me-1"></i>{{ __('Disconnected') }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Inventory & Finance Section -->
<div class="row g-4 mb-4">
    <!-- Inventory Summary -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold">{{ __('Stok Barang') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4">
                    <div class="text-center w-50 border-end">
                        <h3 class="fw-bold text-primary mb-0">{{ $inventoryItems->count() }}</h3>
                        <small class="text-muted">{{ __('Items') }}</small>
                    </div>
                    <div class="text-center w-50">
                        <h4 class="fw-bold text-success mb-0">{{ number_format($totalInventoryValue, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ __('Total Value') }}</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Item') }}</th>
                                <th class="text-end">{{ __('Stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventoryItems as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-1 me-2">
                                            @if($item->type_group == 'tool')
                                                <i class="fa-solid fa-screwdriver-wrench text-warning"></i>
                                            @else
                                                <i class="fa-solid fa-box text-info"></i>
                                            @endif
                                        </div>
                                        <span class="small fw-medium">{{ Str::limit($item->name, 20) }}</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $item->stock < 5 ? 'bg-danger' : 'bg-success' }}">{{ $item->stock }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('inventory.index') }}" class="btn btn-link btn-sm text-decoration-none">{{ __('View All Inventory') }}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">{{ __('Pendapatan & Pengeluaran') }} ({{ date('Y') }})</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        {{ __('This Year') }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item active" href="#">{{ __('This Year') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 300px; width: 100%;">
                    <canvas id="financialChart"></canvas>
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

<!-- Deployed Tools Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold">{{ __('Alat yang Dipakai Teknisi & Pengurus') }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-tertiary">
                        <tr>
                            <th class="ps-4">{{ __('Asset Code') }}</th>
                            <th>{{ __('Item Name') }}</th>
                            <th>{{ __('Holder') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Condition') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deployedAssets as $asset)
                        <tr>
                            <td class="ps-4 font-monospace small">{{ $asset->asset_code }}</td>
                            <td class="fw-medium">{{ $asset->item->name ?? '-' }}</td>
                            <td>
                                @if($asset->holder)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            {{ substr($asset->holder->name, 0, 1) }}
                                        </div>
                                        <span>{{ $asset->holder->name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($asset->holder_type == 'App\Models\User')
                                    <span class="badge bg-info-subtle text-info">{{ __('Technician') }}</span>
                                @elseif($asset->holder_type == 'App\Models\Coordinator')
                                    <span class="badge bg-warning-subtle text-warning">{{ __('Coordinator') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-success">{{ ucfirst($asset->status) }}</span></td>
                            <td>{{ ucfirst($asset->condition) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">{{ __('No tools currently deployed to staff.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('financialChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($financialData['labels']),
                datasets: [
                    {
                        label: '{{ __("Pendapatan") }}',
                        data: @json($financialData['income']),
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                    },
                    {
                        label: '{{ __("Pengeluaran") }}',
                        data: @json($financialData['expense']),
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumSignificantDigits: 3 }).format(value);
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
