@extends('layouts.app')

@section('title', __('Dasbor Admin & HRD'))

@section('content')

<div class="mb-4">
    <h4 class="fw-bold text-primary mb-1">{{ __('Dasbor Admin & HRD') }}</h4>
    <p class="text-muted small mb-0">{{ __('Selamat datang kembali di ringkasan dasbor admin dan HRD.') }}</p>
</div>

{{-- Widget Stats --}}
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Total Karyawan Aktif') }}</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_employees'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Hadir Hari Ini') }}</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['present'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Terlambat Hari Ini') }}</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['late'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Sakit/Izin Hari Ini') }}</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="fa-solid fa-file-medical"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['sick_permit'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Alpha (Mangkir)') }}</h6>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['alpha'] }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-secondary position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ __('Clock Out Hari Ini') }}</h6>
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded p-2">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['clocked_out'] }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Recent Activity Log --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold">{{ __('Log Aktivitas Terbaru') }}</h6>
                <a href="{{ route('admin.audit-trail') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-list me-1"></i>{{ __('Lihat Semua') }}
                </a>
            </div>
            <div class="card-body">
                @if($recentLogs->isEmpty())
                    <p class="text-center text-muted py-4">{{ __('Belum ada aktivitas terbaru.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-uppercase small text-muted border-0">{{ __('Waktu') }}</th>
                                    <th class="text-uppercase small text-muted border-0">{{ __('Pengguna') }}</th>
                                    <th class="text-uppercase small text-muted border-0">{{ __('Aksi') }}</th>
                                    <th class="text-uppercase small text-muted border-0">{{ __('Deskripsi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs as $log)
                                    <tr>
                                        <td class="small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $log->user?->name ?? __('Sistem') }}</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $log->action }}</span></td>
                                        <td class="small">{{ $log->description ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold">{{ __('Aksi Cepat') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('attendance.index') }}" class="btn btn-primary">
                        <i class="fa-solid fa-calendar-check me-2"></i>{{ __('Rekap Absensi') }}
                    </a>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-users me-2"></i>{{ __('Data Karyawan') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
