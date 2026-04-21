@extends('layouts.app')

@section('content')
<div class="container-fluid wash-dashboard-page py-2 py-md-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
        <h1 class="h3 mb-0 text-body">{{ __('Dasbor Wash') }}</h1>
        @if(Auth::user()->hasPermission('wash.pos'))
        <a href="{{ route('wash.pos') }}" class="btn btn-primary">
            <i class="fas fa-cash-register me-2"></i>{{ __('Buka POS') }}
        </a>
        @endif
    </div>

    @if(Auth::user()->hasPermission('attendance.view'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-start border-4 border-primary wash-panel">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">{{ __('Absensi Saya Hari Ini') }}</h5>
                        <p class="mb-0 text-muted small">
                            @if($todayAttendance)
                                <span class="badge bg-{{ $todayAttendance->status == 'present' ? 'success' : ($todayAttendance->status == 'late' ? 'warning' : 'secondary') }}">
                                    {{ __(ucfirst($todayAttendance->status)) }}
                                </span>
                                <span class="ms-2">
                                    <i class="fa-solid fa-clock me-1"></i> {{ __('Masuk') }}: {{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }}
                                    @if($todayAttendance->clock_out)
                                        | <i class="fa-solid fa-clock me-1"></i> {{ __('Pulang') }}: {{ \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') }}
                                    @endif
                                </span>
                            @else
                                <span class="badge bg-secondary">{{ __('Belum Hadir') }}</span>
                                <span class="ms-2 text-muted">{{ __('Anda belum melakukan absen masuk hari ini.') }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('attendance.create') }}" class="btn btn-primary">
                            @if(!$todayAttendance)
                                <i class="fa-solid fa-fingerprint"></i> <span class="d-none d-md-inline ms-1">{{ __('Absen Masuk') }}</span>
                            @elseif(!$todayAttendance->clock_out)
                                <i class="fa-solid fa-fingerprint"></i> <span class="d-none d-md-inline ms-1">{{ __('Absen Pulang') }}</span>
                            @else
                                <i class="fa-solid fa-fingerprint"></i> <span class="d-none d-md-inline ms-1">{{ __('Absensi') }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Earnings (Daily) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Pendapatan Harian') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($dailySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Pendapatan Bulanan') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($monthlySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Count Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Transaksi (Hari Ini)') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $transactionCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Karyawan Hadir (Hari Ini)') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($dailyAttendanceCount, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Hadir Hari Ini') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceOverview['present'] ?? 0 }}</div>
                    <div class="small text-muted mt-2">{{ __('Total anggota peran ini: :total', ['total' => $attendanceOverview['total'] ?? 0]) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">{{ __('Tidak Hadir Hari Ini') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceOverview['not_present'] ?? 0 }}</div>
                    <div class="small text-muted mt-2">{{ __('Belum absen atau tidak masuk hari ini') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card border-left-info shadow h-100 py-2 wash-stat-card">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Jadwal Shift Hari Ini') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $shiftSchedule?->status ? __(ucfirst($shiftSchedule->status)) : __('Belum Diatur') }}</div>
                    <div class="small text-muted mt-2">{{ $shiftSchedule?->notes ?: __('Belum ada catatan jadwal untuk hari ini.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 wash-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Tren Layanan Harian (7 Hari)') }}</h6>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="washServiceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 wash-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Layanan Teratas') }}</h6>
                </div>
                <div class="card-body">
                    @if($topServices->isEmpty())
                        <p class="text-center">{{ __('Belum ada transaksi.') }}</p>
                    @else
                        <ul class="list-group">
                            @foreach($topServices as $service)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $service->service_name }}
                                    <span class="badge bg-primary rounded-pill">{{ $service->total_qty }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-dashboard-page .wash-panel {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1.2rem;
        overflow: hidden;
    }

    .wash-dashboard-page .wash-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.18);
    }

    .wash-dashboard-page .wash-stat-card {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1rem;
    }

    .wash-dashboard-page .text-gray-800 {
        color: var(--bs-body-color) !important;
    }

    [data-bs-theme="dark"] .wash-dashboard-page .wash-panel {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .wash-dashboard-page .wash-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-bottom-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .wash-dashboard-page .wash-stat-card {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .wash-dashboard-page .wash-stat-card .text-gray-300 {
        color: #64748b !important;
    }

    [data-bs-theme="dark"] .wash-dashboard-page .list-group-item {
        background-color: rgba(15, 23, 42, 0.6);
        border-color: #334155;
        color: #e2e8f0;
    }

    @media (max-width: 767.98px) {
        .wash-dashboard-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .wash-dashboard-page .btn {
            width: 100%;
        }

        .wash-dashboard-page .wash-panel {
            border-radius: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('washServiceTrendChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($serviceTrendLabels),
                datasets: [{
                    label: '{{ __("Jumlah Layanan") }}',
                    data: @json($serviceTrendData),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.15)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
