@extends('layouts.app')

@section('title', __('Dasbor ATK'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-5">
        <div>
            <h1 class="h2 fw-bold mb-1">{{ __('Dasbor Toko ATK') }}</h1>
            <p class="text-muted mb-0">{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('atk.pos') }}" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-cash-register me-2"></i> {{ __('Buka POS') }}
            </a>
        </div>
    </div>

    @if(Auth::user()->hasPermission('attendance.view'))
    <div class="mb-5">
        <div class="card leave-header-card border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-2">{{ __('Absensi Saya Hari Ini') }}</h5>
                        <div class="d-flex align-items-center gap-3">
                            @if($todayAttendance)
                                <span class="badge bg-{{ $todayAttendance->status == 'present' ? 'success' : ($todayAttendance->status == 'late' ? 'warning' : 'secondary') }} fs-6">
                                    {{ __(ucfirst($todayAttendance->status)) }}
                                </span>
                                <div class="d-flex align-items-center gap-2 text-muted">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>{{ __('Masuk') }}: {{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }}</span>
                                    @if($todayAttendance->clock_out)
                                        <span class="mx-1">|</span>
                                        <span>{{ __('Pulang') }}: {{ \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="badge bg-secondary fs-6">{{ __('Belum Hadir') }}</span>
                                <span class="text-muted">{{ __('Anda belum melakukan absen masuk hari ini.') }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('attendance.create') }}" class="btn {{ !$todayAttendance ? 'btn-primary' : (!$todayAttendance->clock_out ? 'btn-danger' : 'btn-primary') }} btn-lg">
                            @if(!$todayAttendance)
                                <i class="fa-solid fa-fingerprint me-2"></i>{{ __('Absen Masuk') }}
                            @elseif(!$todayAttendance->clock_out)
                                <i class="fa-solid fa-fingerprint me-2"></i>{{ __('Absen Pulang') }}
                            @else
                                <i class="fa-solid fa-fingerprint me-2"></i>{{ __('Absensi') }}
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Key Performance Indicators --}}
    <div class="row g-4 mb-5">
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('atk.transactions.index') }}" class="text-decoration-none">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Penjualan Harian') }}</div>
                            <div class="stat-card-value">Rp {{ number_format($dailySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('atk.reports.index') }}" class="text-decoration-none">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Penjualan Bulanan') }}</div>
                            <div class="stat-card-value">Rp {{ number_format($monthlySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('atk.transactions.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Transaksi Hari Ini') }}</div>
                            <div class="stat-card-value">{{ $transactionCount }}</div>
                        </div>
                        <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Financial Health --}}
    <div class="row g-4 mb-5">
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('atk.cash-registers.index') }}" class="text-decoration-none">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Kas Utama') }}</div>
                            <div class="stat-card-value">Rp {{ number_format($cash->balance, 0, ',', '.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('atk.float-accounts.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Saldo Float') }}</div>
                            <div class="stat-card-value">Rp {{ number_format($floatAccounts->sum('current_balance'), 0, ',', '.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-12">
            <a href="{{ route('atk.owner-funds.index') }}" class="text-decoration-none">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Dana Talangan') }}</div>
                            <div class="stat-card-value">Rp {{ number_format($currentOwnerBalance, 0, ',', '.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Today's Activity Breakdown --}}
    <div class="row g-4 mb-5">
        <div class="col-xl-2 col-md-4">
            <a href="{{ route('atk.transactions.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Penjualan') }}</div>
                            <div class="stat-card-value fs-4">Rp {{ number_format($dailySales,0,',','.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-2 col-md-4">
            <a href="{{ route('atk.expenses.index') }}" class="text-decoration-none">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Pengeluaran') }}</div>
                            <div class="stat-card-value fs-4">Rp {{ number_format($todayExpenses,0,',','.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fa-solid fa-arrow-trend-down"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-2 col-md-4">
            <a href="{{ route('atk.transactions.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Top Up') }}</div>
                            <div class="stat-card-value fs-4">Rp {{ number_format($todayTopUp,0,',','.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                            <i class="fa-solid fa-arrow-up-right-dots"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-2 col-md-4">
            <a href="{{ route('atk.transactions.index') }}" class="text-decoration-none">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('PPOB') }}</div>
                            <div class="stat-card-value fs-4">Rp {{ number_format($todayPpob,0,',','.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-2 col-md-4">
            <a href="{{ route('atk.transactions.index') }}" class="text-decoration-none">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Transfer') }}</div>
                            <div class="stat-card-value fs-4">Rp {{ number_format($todayTransfer,0,',','.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-2 col-md-4">
            <a href="{{ route('atk.cash-movements.index') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Tarik Tunai') }}</div>
                            <div class="stat-card-value fs-4">Rp {{ number_format($todayWithdrawal,0,',','.') }}</div>
                        </div>
                        <div class="stat-card-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Attendance Overview --}}
    <div class="row g-4 mb-5">
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('attendance.daily') }}" class="text-decoration-none">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Kasir Hadir Hari Ini') }}</div>
                            <div class="stat-card-value">{{ $attendanceOverview['present'] ?? 0 }}</div>
                            <div class="stat-card-subtitle">{{ __('Total kasir aktif: :total', ['total' => $attendanceOverview['total'] ?? 0]) }}</div>
                        </div>
                        <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-6">
            <a href="{{ route('attendance.daily') }}" class="text-decoration-none">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-card-label">{{ __('Kasir Tidak Hadir') }}</div>
                            <div class="stat-card-value">{{ $attendanceOverview['not_present'] ?? 0 }}</div>
                            <div class="stat-card-subtitle">{{ __('Belum absen atau tidak masuk hari ini') }}</div>
                        </div>
                        <div class="stat-card-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fa-solid fa-user-xmark"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-card-label">{{ __('Jadwal Shift Hari Ini') }}</div>
                        <div class="stat-card-value">{{ $shiftSchedule?->status ? __(ucfirst($shiftSchedule->status)) : __('Belum Diatur') }}</div>
                        <div class="stat-card-subtitle">{{ $shiftSchedule?->notes ?: __('Belum ada catatan jadwal untuk hari ini.') }}</div>
                    </div>
                    <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Products & Quick Actions --}}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">{{ __('Produk Terlaris') }}</h6>
                    <i class="fa-solid fa-fire text-warning"></i>
                </div>
                <div class="card-body">
                    @if($topProducts->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($topProducts as $product)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="fw-semibold">{{ $product->product_name }}</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $product->total_qty }}x</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-box-open text-muted display-4 mb-3"></i>
                            <p class="text-muted mb-0">{{ __('Belum ada data penjualan.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
             <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">{{ __('Aksi Cepat') }}</h6>
                    <i class="fa-solid fa-bolt text-primary"></i>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('atk.products.create') }}" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-plus me-2"></i> {{ __('Tambah Produk Baru') }}
                        </a>
                        <a href="{{ route('atk.transactions.index') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fa-solid fa-list me-2"></i> {{ __('Lihat Riwayat Transaksi') }}
                        </a>
                        @if(Auth::user()->hasPermission('atk.manage'))
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('atk.float-accounts.index') }}" class="btn btn-outline-info w-100">
                                        <i class="fa-solid fa-credit-card me-1"></i> {{ __('Float') }}
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('atk.owner-funds.index') }}" class="btn btn-outline-warning w-100">
                                        <i class="fa-solid fa-hand-holding-dollar me-1"></i> {{ __('Talangan') }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
