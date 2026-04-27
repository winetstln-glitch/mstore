@extends('layouts.app')

@section('title', __('Dasbor'))

@section('content')

<div class="mb-4">
    <h4 class="fw-bold text-primary mb-1">{{ __('Dasbor') }}</h4>
    <p class="text-muted small mb-0">{{ __('Selamat datang kembali di ringkasan dasbor Anda.') }}</p>
</div>

@if(Auth::user()->hasPermission('attendance.view'))
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-4 border-primary">
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
                    <a href="{{ route('attendance.create') }}" class="btn {{ !$todayAttendance ? 'btn-primary' : (!$todayAttendance->clock_out ? 'btn-danger' : 'btn-primary') }}">
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
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Teknisi Masuk') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['technician_present_today'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Dari total') }} {{ $stats['technician_total'] }} {{ __('teknisi aktif') }}</span>
                </div>
            </div>
            <a class="stretched-link" href="{{ route('dashboard', ['attendance_role' => 'technician', 'attendance_state' => 'present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}" aria-label="Lihat teknisi masuk"></a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Teknisi Tidak Masuk') }}</h6>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                        <i class="fa-solid fa-user-xmark"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['technician_not_present_today'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Belum melakukan absensi hari ini') }}</span>
                </div>
            </div>
            <a class="stretched-link" href="{{ route('dashboard', ['attendance_role' => 'technician', 'attendance_state' => 'not_present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}" aria-label="Lihat teknisi tidak masuk"></a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Karyawan Wash Masuk') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="fa-solid fa-soap"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['wash_employee_present_today'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Dari total') }} {{ $stats['wash_employee_total'] }} {{ __('karyawan wash aktif') }}</span>
                </div>
            </div>
            <a class="stretched-link" href="{{ route('dashboard', ['attendance_role' => 'karyawan-wash', 'attendance_state' => 'present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}" aria-label="Lihat karyawan wash masuk"></a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Karyawan Wash Tidak Masuk') }}</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="fa-solid fa-user-clock"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['wash_employee_not_present_today'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Belum melakukan absensi hari ini') }}</span>
                </div>
            </div>
            <a class="stretched-link" href="{{ route('dashboard', ['attendance_role' => 'karyawan-wash', 'attendance_state' => 'not_present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}" aria-label="Lihat karyawan wash tidak masuk"></a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold">{{ __('Data Absensi Karyawan') }}</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('dashboard') }}" method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                        <input type="hidden" name="attendance_role" value="{{ $attendanceRole }}">
                        <input type="hidden" name="attendance_state" value="{{ $attendanceState }}">
                        <input type="date" name="attendance_date" value="{{ $attendanceDate ?? now()->toDateString() }}" class="form-control form-control-sm" style="max-width: 170px;">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Terapkan</button>
                    </form>
                    <a href="{{ route('dashboard', ['attendance_role' => 'technician', 'attendance_state' => 'present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}"
                       class="btn btn-sm {{ $attendanceRole === 'technician' && $attendanceState === 'present' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Teknisi Masuk
                    </a>
                    <a href="{{ route('dashboard', ['attendance_role' => 'technician', 'attendance_state' => 'not_present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}"
                       class="btn btn-sm {{ $attendanceRole === 'technician' && $attendanceState === 'not_present' ? 'btn-danger' : 'btn-outline-danger' }}">
                        Teknisi Tidak Masuk
                    </a>
                    <a href="{{ route('dashboard', ['attendance_role' => 'karyawan-wash', 'attendance_state' => 'present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}"
                       class="btn btn-sm {{ $attendanceRole === 'karyawan-wash' && $attendanceState === 'present' ? 'btn-info' : 'btn-outline-info' }}">
                        Karyawan Wash Masuk
                    </a>
                    <a href="{{ route('dashboard', ['attendance_role' => 'karyawan-wash', 'attendance_state' => 'not_present', 'attendance_date' => $attendanceDate ?? now()->toDateString()]) }}"
                       class="btn btn-sm {{ $attendanceRole === 'karyawan-wash' && $attendanceState === 'not_present' ? 'btn-warning' : 'btn-outline-warning' }}">
                        Karyawan Wash Tidak Masuk
                    </a>
                    <span class="badge bg-primary-subtle text-primary">
                        {{ $attendanceRole === 'technician' ? 'Peran: Teknisi' : 'Peran: Karyawan Wash' }}
                    </span>
                    <span class="badge {{ $attendanceState === 'present' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                        {{ $attendanceState === 'present' ? 'Status: Masuk' : 'Status: Tidak Masuk' }}
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary">
                        {{ __('Tanggal') }}: {{ $attendanceDateLabel ?? now()->translatedFormat('d M Y') }}
                    </span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-uppercase small text-muted border-0">{{ __('Nama') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Peran') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Status Hari Ini') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Jam Masuk') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Jam Pulang') }}</th>
                            @if($attendanceRole === 'technician')
                                <th class="text-uppercase small text-muted border-0">{{ __('Status Tugas') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Tugas Aktif') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceEmployees as $employee)
                            @php
                                $attendanceRow = $attendanceByUser->get($employee->id);
                                $taskSummary = $technicianTaskSummary->get($employee->id, [
                                    'label' => 'Standby',
                                    'total_active' => 0,
                                    'active_ticket_id' => 0,
                                    'ticket_active' => 0,
                                    'installation_active' => 0,
                                ]);
                            @endphp
                            <tr>
                                <td class="ps-4 fw-medium">{{ $employee->name }}</td>
                                <td>{{ $employee->role->label ?? '-' }}</td>
                                <td>
                                    @if($attendanceRow && in_array($attendanceRow->status, ['present', 'late']))
                                        <span class="badge bg-success-subtle text-success">{{ ucfirst($attendanceRow->status) }}</span>
                                    @elseif($attendanceRow)
                                        <span class="badge bg-warning-subtle text-warning">{{ ucfirst($attendanceRow->status) }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">{{ __('Tidak Masuk') }}</span>
                                    @endif
                                </td>
                                <td>{{ $attendanceRow?->clock_in ? \Carbon\Carbon::parse($attendanceRow->clock_in)->format('H:i') : '-' }}</td>
                                <td>{{ $attendanceRow?->clock_out ? \Carbon\Carbon::parse($attendanceRow->clock_out)->format('H:i') : '-' }}</td>
                                @if($attendanceRole === 'technician')
                                    <td>
                                        <span class="badge {{ $taskSummary['total_active'] > 0 ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $taskSummary['label'] }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        @if(($taskSummary['active_ticket_id'] ?? 0) > 0)
                                            <a href="{{ route('tickets.show', $taskSummary['active_ticket_id']) }}" class="text-decoration-none fw-semibold">
                                                {{ __('Tiket') }}: {{ $taskSummary['ticket_active'] }}
                                            </a>
                                        @else
                                            {{ __('Tiket') }}: {{ $taskSummary['ticket_active'] }}
                                        @endif
                                        | {{ __('Instalasi') }}: {{ $taskSummary['installation_active'] }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $attendanceRole === 'technician' ? 7 : 5 }}" class="text-center py-4 text-muted">{{ __('Tidak ada data karyawan untuk filter ini.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Teknisi Online') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2"><i class="fa-solid fa-signal"></i></div>
                </div>
                <h3 class="fw-bold mb-1" id="onlineTechniciansCount">{{ $onlineTechnicians ?? 0 }}</h3>
                <div class="small text-body-secondary">{{ __('Realtime terdeteksi aktif') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Wash Online') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2"><i class="fa-solid fa-wifi"></i></div>
                </div>
                <h3 class="fw-bold mb-1" id="onlineWashCount">{{ $onlineWashEmployees ?? 0 }}</h3>
                <div class="small text-body-secondary">{{ __('Karyawan wash sedang aktif') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <!-- Customers -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Pelanggan') }}</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_customers'] }}</h3>
                <div class="small text-success">
                    <i class="fa-solid fa-arrow-trend-up me-1"></i>
                    <span>+{{ $stats['new_customers_this_month'] }} {{ __('bulan ini') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Tiket Aktif') }}</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['open_tickets'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ $stats['tickets_today'] }} {{ __('baru hari ini') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Installations -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Instalasi Tertunda') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['pending_installations'] }}</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Dijadwalkan minggu ini') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monitor Summary -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted small fw-bold mb-0">{{ __('Ringkasan Monitor') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-server"></i>
                    </div>
                </div>
                @if($monitorSummary)
                <h3 class="fw-bold mb-1">{{ number_format((int) ($monitorSummary['checked'] ?? 0)) }}</h3>
                <div class="small text-body-secondary mb-1">
                    <span>{{ __('Perangkat yang dicek') }}</span>
                </div>
                <div class="small text-body-secondary">
                    <span>{{ __('Gangguan') }}: {{ number_format((int) ($monitorSummary['down'] ?? 0)) }}</span>
                    <span class="mx-1">•</span>
                    <span>{{ __('Tiket') }}: {{ number_format((int) ($monitorSummary['tickets_created'] ?? 0)) }}</span>
                    <span class="mx-1">•</span>
                    <span>{{ __('Kesalahan') }}: {{ number_format((int) ($monitorSummary['errors'] ?? 0)) }}</span>
                </div>
                @if(!empty($monitorTrend))
                <div class="mt-2">
                    <div class="small text-muted mb-1">{{ __('Tren gangguan 7 hari') }}</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($monitorTrend as $trend)
                        <span class="badge {{ ($trend['down'] ?? 0) > 0 ? 'bg-warning text-dark' : 'bg-light text-muted' }}">
                            {{ $trend['label'] }}: {{ (int) ($trend['down'] ?? 0) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($monitorSummary['ran_at']))
                <div class="small text-muted mt-1">
                    <span>{{ __('Diperbarui') }} {{ \Carbon\Carbon::parse($monitorSummary['ran_at'])->diffForHumans() }}</span>
                </div>
                @endif
                @else
                <h3 class="fw-bold mb-1">0</h3>
                <div class="small text-body-secondary">
                    <span>{{ __('Belum ada data monitor') }}</span>
                </div>
                @if(!empty($monitorTrend))
                <div class="mt-2">
                    <div class="small text-muted mb-1">{{ __('Tren gangguan 7 hari') }}</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($monitorTrend as $trend)
                        <span class="badge {{ ($trend['down'] ?? 0) > 0 ? 'bg-warning text-dark' : 'bg-light text-muted' }}">
                            {{ $trend['label'] }}: {{ (int) ($trend['down'] ?? 0) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif
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
                        <small class="text-muted">{{ __('Barang') }}</small>
                    </div>
                    <div class="text-center w-50">
                        <h4 class="fw-bold text-success mb-0">{{ number_format($totalInventoryValue, 0, ',', '.') }}</h4>
                        <small class="text-muted">{{ __('Total Nilai') }}</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-uppercase small text-muted border-0">{{ __('Barang') }}</th>
                                <th class="text-end text-uppercase small text-muted border-0">{{ __('Stok') }}</th>
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
                    <a href="{{ route('inventory.index') }}" class="btn btn-link btn-sm text-decoration-none">{{ __('Lihat Semua Inventaris') }}</a>
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
                        {{ __('Tahun Ini') }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item active" href="#">{{ __('Tahun Ini') }}</a></li>
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

<!-- POS & Car Wash Summary -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Pendapatan ATK') }}</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">{{ __('Transaksi Hari Ini') }}</div>
                        <h4 class="fw-bold mb-0">{{ $stats['atk_today'] }}</h4>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">{{ __('Pendapatan Bulan Ini') }}</div>
                        <h4 class="fw-bold text-success mb-0">{{ number_format($stats['atk_month_revenue'], 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Pendapatan GT Wash') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="fa-solid fa-car"></i>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">{{ __('Transaksi Hari Ini') }}</div>
                        <h4 class="fw-bold mb-0">{{ $stats['wash_today'] }}</h4>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">{{ __('Pendapatan Bulan Ini') }}</div>
                        <h4 class="fw-bold text-success mb-0">{{ number_format($stats['wash_month_revenue'], 0, ',', '.') }}</h4>
                    </div>
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
                <h6 class="mb-0 fw-bold">{{ __('Rekap Tiket Bulanan') }} ({{ date('Y') }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 text-uppercase small text-muted border-0">{{ __('Bulan') }}</th>
                                <th class="text-center text-uppercase small text-muted border-0">{{ __('Total') }}</th>
                                <th class="text-center text-uppercase small text-muted border-0">{{ __('Selesai') }}</th>
                                <th class="text-center text-uppercase small text-muted border-0">{{ __('Buka/Tertunda') }}</th>
                                <th class="text-end pe-3 text-uppercase small text-muted border-0">{{ __('Tingkat Penyelesaian') }}</th>
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
                <h6 class="mb-0 fw-bold">{{ __('Tiket Dukungan Terbaru') }}</h6>
                <a href="{{ route('tickets.index') }}" class="btn btn-link btn-sm text-decoration-none">{{ __('Lihat Semua') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-uppercase small text-muted border-0">{{ __('ID Tiket') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Pelanggan') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Subject') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Status') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Tanggal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTickets as $ticket)
                        <tr>
                            <td class="ps-4"><span class="fw-medium">#{{ $ticket->ticket_number }}</span></td>
                            <td>{{ $ticket->customer?->name ?? __('Tanpa Pelanggan') }}</td>
                            <td>{{ Str::limit($ticket->subject, 30) }}</td>
                            <td>
                                @if($ticket->status === 'open')
                                    <span class="badge bg-danger-subtle text-danger">{{ __('Buka') }}</span>
                                @elseif($ticket->status === 'closed')
                                    <span class="badge bg-success-subtle text-success">{{ __('Tutup') }}</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">{{ ucfirst($ticket->status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $ticket->created_at->format('d M H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">{{ __('Belum ada tiket terbaru.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Info & Installations -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4" id="mixradius-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold">MixRADIUS</h6>
                    <div class="small text-muted">
                        <span>Konektivitas Layanan</span>
                        <span class="ms-2" id="mixradius-meta" style="display:none"></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if(!empty($mixRadiusOk))
                        <span id="mixradius-badge" class="badge bg-success">{{ __('Terhubung') }}</span>
                    @else
                        <span id="mixradius-badge" class="badge bg-danger">{{ __('Terputus') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <!-- Info Card -->
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">{{ __('Informasi Sistem') }}</h5>
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3 opacity-75"><i class="fa-regular fa-clock fa-2x"></i></div>
                    <div>
                        <div class="small opacity-75 text-uppercase">{{ __('Waktu Server') }}</div>
                        <div class="fw-bold">{{ now()->format('H:i:s') }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3 opacity-75"><i class="fa-regular fa-calendar fa-2x"></i></div>
                    <div>
                        <div class="small opacity-75 text-uppercase">{{ __('Tanggal') }}</div>
                        <div class="fw-bold">{{ now()->format('l, d F Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Installations -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold">{{ __('Instalasi Mendatang') }}</h6>
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
                        <span class="badge bg-info-subtle text-info">{{ $install->plan_date ? $install->plan_date->format('H:i') : __('Belum Dijadwalkan') }} WIB</span>
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-muted small">
                    {{ __('Tidak ada instalasi mendatang.') }}
                </div>
                @endforelse
            </div>
            <div class="card-footer bg-transparent border-0 text-center pb-3">
                @if (Route::has('installations.index'))
                    <a href="{{ route('installations.index') }}" class="text-decoration-none small fw-bold">{{ __('Lihat Kalender') }} <i class="fa-solid fa-arrow-right ms-1"></i></a>
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
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-uppercase small text-muted border-0">{{ __('Kode Aset') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Nama Barang') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Pemegang') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Peran') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Status') }}</th>
                            <th class="text-uppercase small text-muted border-0">{{ __('Kondisi') }}</th>
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
                                    <span class="badge bg-info-subtle text-info">{{ __('Teknisi') }}</span>
                                @elseif($asset->holder_type == 'App\Models\Coordinator')
                                    <span class="badge bg-warning-subtle text-warning">{{ __('Pengurus') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Tidak Diketahui') }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-success">{{ ucfirst($asset->status) }}</span></td>
                            <td>{{ ucfirst($asset->condition) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">{{ __('Belum ada alat yang dipinjamkan ke staf.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-refresh MixRADIUS status every 15s
        const badge = document.getElementById('mixradius-badge');
        const meta = document.getElementById('mixradius-meta');
        async function refreshMixRadius() {
            if (!badge) return;
            try {
                const res = await fetch("{{ route('health.mixradius') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
                if (!res.ok) throw new Error('network');
                const data = await res.json();
                const ok = !!data.ok;
                badge.textContent = ok ? 'Terhubung' : 'Terputus';
                badge.className = 'badge ' + (ok ? 'bg-success' : 'bg-danger');
                if (meta) {
                    const ts = data.checked_at ? new Date(data.checked_at) : null;
                    const timeStr = ts ? ts.toLocaleTimeString() : '';
                    const authStr = (data.ok_auth === true) ? 'auth: OK' : (data.ok_auth === false ? 'auth: FAIL' : 'auth: -');
                    if (typeof data.latency_ms === 'number') {
                        meta.style.display = '';
                        meta.textContent = `• ${data.latency_ms} ms • ${authStr} • ${timeStr}`;
                    } else {
                        meta.style.display = '';
                        meta.textContent = `• ${authStr} • ${timeStr}`;
                    }
                }
            } catch (e) {
                badge.textContent = 'Terputus';
                badge.className = 'badge bg-danger';
                if (meta) {
                    meta.style.display = '';
                    meta.textContent = '• auth: -';
                }
            }
        }
        setInterval(refreshMixRadius, 15000);
        // initial slight delay to avoid blocking page load
        setTimeout(refreshMixRadius, 2000);

    });
</script>
@endpush

@endsection
