@extends('layouts.app')

@section('title', 'Dasbor Admin')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
        <h1 class="h2">Dasbor Admin & HRD</h1>
    </div>

    {{-- Widget Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $stats['total_employees'] }}</h3>
                            <p class="card-text mt-1">Total Karyawan Aktif</p>
                        </div>
                        <i class="fa-solid fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $stats['present'] }}</h3>
                            <p class="card-text mt-1">Hadir Hari Ini</p>
                        </div>
                        <i class="fa-solid fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $stats['late'] }}</h3>
                            <p class="card-text mt-1">Terlambat Hari Ini</p>
                        </div>
                        <i class="fa-solid fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $stats['sick_permit'] }}</h3>
                            <p class="card-text mt-1">Sakit/Izin Hari Ini</p>
                        </div>
                        <i class="fa-solid fa-file-medical fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $stats['alpha'] }}</h3>
                            <p class="card-text mt-1">Alpha (Mangkir) Hari Ini</p>
                        </div>
                        <i class="fa-solid fa-user-slash fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-white bg-secondary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $stats['clocked_out'] }}</h3>
                            <p class="card-text mt-1">Clock Out Hari Ini</p>
                        </div>
                        <i class="fa-solid fa-right-from-bracket fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Recent Activity Log --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Log Aktivitas Terbaru</h5>
                    <a href="{{ route('admin.audit-trail') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="card-body">
                    @if($recentLogs->isEmpty())
                        <p class="text-center text-muted py-4">Belum ada aktivitas terbaru.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Pengguna</th>
                                        <th>Aksi</th>
                                        <th>Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                                            <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                            <td>{{ $log->description ?? '-' }}</td>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('attendance.index') }}" class="btn btn-primary">
                            <i class="fa-solid fa-calendar-check me-2"></i>Rekap Absensi
                        </a>
                        <a href="{{ route('employees.index') }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-users me-2"></i>Data Karyawan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
