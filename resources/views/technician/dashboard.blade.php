@extends('layouts.app')

@section('content')

@if(Auth::user()->hasPermission('attendance.view'))
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-4 border-primary">
            <div class="card-body d-flex justify-content-between align-items-center">
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
                            <i class="fa-solid fa-sign-in-alt"></i> <span class="d-none d-md-inline ms-1">{{ __('Absen Masuk') }}</span>
                        @elseif(!$todayAttendance->clock_out)
                            <i class="fa-solid fa-sign-out-alt"></i> <span class="d-none d-md-inline ms-1">{{ __('Absen Pulang') }}</span>
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

<!-- Top Stats Cards Row -->
<div class="row g-4 mb-4">
    <!-- Solved Today -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Terselesaikan Hari Ini') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['completed_tickets_today'] }}</h3>
                <div class="small text-body-secondary">{{ __('Pencapaian Hari Ini') }}</div>
            </div>
            <div class="card-footer bg-transparent border-0 text-center py-2">
                <a href="#" class="text-decoration-none small fw-bold text-success">
                    {{ __('Lihat Detail') }} <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Active Tickets -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Tiket Aktif') }}</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['assigned_tickets'] }}</h3>
                <div class="small text-body-secondary">{{ __('Tugas Menunggu') }}</div>
            </div>
            <div class="card-footer bg-transparent border-0 text-center py-2">
                <a href="{{ route('tickets.index') }}" class="text-decoration-none small fw-bold text-warning">
                    {{ __('Lihat Detail') }} <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Installations -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Pemasangan') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['assigned_installations'] }}</h3>
                <div class="small text-body-secondary">{{ __('Jadwal Berikutnya') }}</div>
            </div>
            <div class="card-footer bg-transparent border-0 text-center py-2">
                @if (Route::has('installations.index'))
                    <a href="{{ route('installations.index') }}" class="text-decoration-none small fw-bold text-info">
                        {{ __('Lihat Detail') }} <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- High Priority -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-body">
                @php
                    $highPriorityCount = $activeTickets->where('priority', 'high')->count();
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Prioritas Tinggi') }}</h6>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $highPriorityCount }}</h3>
                <div class="small text-body-secondary">{{ __('Perlu Tindakan') }}</div>
            </div>
            <div class="card-footer bg-transparent border-0 text-center py-2">
                <a href="{{ route('tickets.index') }}" class="text-decoration-none small fw-bold text-danger">
                    {{ __('Lihat Detail') }} <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Info Bar -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-start border-4 border-secondary">
            <div class="card-body p-2 d-flex align-items-center">
                <div class="me-3 text-secondary"><i class="fa-solid fa-calendar fa-lg"></i></div>
                <div>
                    <div class="small fw-bold text-body-secondary text-uppercase">{{ __('Hari Ini') }}</div>
                    <div class="small fw-semibold">{{ now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body p-2 d-flex align-items-center">
                <div class="me-3 text-success"><i class="fa-solid fa-wifi fa-lg"></i></div>
                <div>
                    <div class="small fw-bold text-body-secondary text-uppercase">{{ __('Status') }}</div>
                    <div class="small fw-semibold text-success">{{ __('Aktif') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body p-2 d-flex align-items-center">
                <div class="me-3 text-primary"><i class="fa-solid fa-list-check fa-lg"></i></div>
                <div>
                    <div class="small fw-bold text-body-secondary text-uppercase">{{ __('Menunggu') }}</div>
                    <div class="small fw-semibold">{{ $stats['assigned_tickets'] + $stats['assigned_installations'] }} {{ __('Tugas') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body p-2 d-flex align-items-center">
                <div class="me-3 text-info"><i class="fa-solid fa-chart-line fa-lg"></i></div>
                <div>
                    <div class="small fw-bold text-body-secondary text-uppercase">{{ __('Kinerja') }}</div>
                    <div class="small fw-semibold">{{ __('Baik') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="small text-uppercase text-body-secondary fw-bold mb-2">{{ __('Teknisi Hadir Hari Ini') }}</div>
                <h3 class="fw-bold mb-1">{{ $attendanceOverview['present'] ?? 0 }}</h3>
                <div class="small text-body-secondary">{{ __('Dari total :total teknisi aktif', ['total' => $attendanceOverview['total'] ?? 0]) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-secondary">
            <div class="card-body">
                <div class="small text-uppercase text-body-secondary fw-bold mb-2">{{ __('Teknisi Tidak Hadir') }}</div>
                <h3 class="fw-bold mb-1">{{ $attendanceOverview['not_present'] ?? 0 }}</h3>
                <div class="small text-body-secondary">{{ __('Belum absen atau tidak masuk hari ini') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="small text-uppercase text-body-secondary fw-bold mb-2">{{ __('Jadwal Shift Hari Ini') }}</div>
                <h3 class="fw-bold mb-1">{{ $shiftSchedule?->status ? __(ucfirst($shiftSchedule->status)) : __('Belum Diatur') }}</h3>
                <div class="small text-body-secondary">{{ $shiftSchedule?->notes ?: __('Belum ada catatan jadwal untuk hari ini.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Priority Tickets -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 border-top border-4 border-primary">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold d-flex align-items-center">
                    <i class="fa-solid fa-clipboard-list text-primary me-2"></i> {{ __('Tiket Prioritas') }}
                </h6>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary active">{{ __('Aktif') }}</button>
                    <button class="btn btn-outline-secondary">{{ __('Riwayat') }}</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($activeTickets as $ticket)
                    <div class="list-group-item px-4 py-3 border-0 border-bottom">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle  d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">
                                    <span class="fw-bold text-body-secondary">{{ substr($ticket->customer->name ?? 'U', 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-1 fw-bold">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-decoration-none text-body-emphasis stretched-link">{{ $ticket->subject }}</a>
                                    </h6>
                                    @if($ticket->priority === 'high')
                                        <span class="badge bg-danger-subtle text-danger">{{ __('Tinggi') }}</span>
                                    @elseif($ticket->priority === 'medium')
                                        <span class="badge bg-warning-subtle text-warning">{{ __('Sedang') }}</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">{{ __('Rendah') }}</span>
                                    @endif
                                </div>
                                <p class="mb-1 text-body-secondary small text-truncate" style="max-width: 90%;">{{ $ticket->description }}</p>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <div class="small text-body-secondary">
                                        <i class="fa-regular fa-user me-1"></i> {{ $ticket->customer->name ?? __('Tidak Diketahui') }}
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-muted">
                        <i class="fa-regular fa-folder-open fa-2x mb-3 opacity-50"></i>
                        <p class="mb-0">{{ __('Tidak ada tiket aktif.') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Installations -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 border-top border-4 border-info">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center">
                    <i class="fa-solid fa-screwdriver-wrench text-info me-2"></i> {{ __('Pemasangan') }}
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($pendingInstallations as $install)
                    <div class="list-group-item px-4 py-3 border-0 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-info-subtle text-info">{{ $install->status }}</span>
                            <small class="text-muted">{{ $install->plan_date ? $install->plan_date->format('d M') : 'TBD' }}</small>
                        </div>
                        <h6 class="mb-1 fw-bold text-body-emphasis">{{ $install->customer->name }}</h6>
                        <p class="mb-2 small text-body-secondary"><i class="fa-solid fa-location-dot me-1"></i> {{ Str::limit($install->customer->address, 30) }}</p>
                        @if (Route::has('installations.show'))
                            <a href="{{ route('installations.show', $install) }}" class="btn btn-sm btn-outline-info w-100">{{ __('Lihat Detail') }}</a>
                        @endif
                    </div>
                    @empty
                    <div class="p-4 text-center text-muted">
                        <p class="mb-0 small">{{ __('Tidak ada pemasangan yang menunggu.') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
