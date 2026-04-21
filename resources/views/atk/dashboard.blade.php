@extends('layouts.app')

@section('title', __('Dasbor ATK'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Dasbor Toko ATK') }}</h1>
        <a href="{{ route('atk.pos') }}" class="btn btn-primary">
            <i class="fa-solid fa-cash-register me-2"></i> {{ __('Buka POS') }}
        </a>
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
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Penjualan Harian') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($dailySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Penjualan Bulanan') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($monthlySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                {{ __('Transaksi Hari Ini') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $transactionCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Kasir Hadir Hari Ini') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceOverview['present'] ?? 0 }}</div>
                    <div class="small text-muted mt-2">{{ __('Total kasir aktif: :total', ['total' => $attendanceOverview['total'] ?? 0]) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">{{ __('Kasir Tidak Hadir') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $attendanceOverview['not_present'] ?? 0 }}</div>
                    <div class="small text-muted mt-2">{{ __('Belum absen atau tidak masuk hari ini') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
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
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Produk Terlaris') }}</h6>
                </div>
                <div class="card-body">
                    @if($topProducts->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($topProducts as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $product->product_name }}
                                    <span class="badge bg-primary rounded-pill">{{ $product->total_qty }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-center text-muted my-3">{{ __('Belum ada data penjualan.') }}</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
             <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Aksi Cepat') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('atk.products.create') }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-plus me-2"></i> {{ __('Tambah Produk Baru') }}
                        </a>
                        <a href="{{ route('atk.transactions.index') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-list me-2"></i> {{ __('Lihat Riwayat Transaksi') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
