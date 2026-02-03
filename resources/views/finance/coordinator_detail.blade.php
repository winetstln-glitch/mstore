@extends('layouts.app')

@section('title', __('Laporan Detail Koordinator'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Finance Report') }}: {{ $coordinator->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $coordinator->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('finance.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
            </a>
            <!-- PERBAIKAN: Pastikan route PDF ada dan mengirim parameter tanggal -->
            <a href="{{ route('finance.coordinator.pdf', ['coordinator' => $coordinator->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-danger" target="_blank">
                <i class="fa-solid fa-file-pdf me-1"></i> {{ __('Download PDF') }}
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow mb-4">
        <div class="card-body py-3">
            <form action="{{ route('finance.coordinator.detail', $coordinator->id) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label small text-muted">{{ __('Start Date') }}</label>
                    <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="{{ $startDate }}" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label small text-muted">{{ __('End Date') }}</label>
                    <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="{{ $endDate }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i> {{ __('Filter') }}
                    </button>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark border">
                        <i class="fa-solid fa-calendar-days me-1"></i>
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- REKONSILIASI KEUANGAN (Audit Summary) -->
    <div class="card shadow mb-4 border-0">
        <div class="card-header bg-primary py-2">
            <h6 class="m-0 font-weight-bold text-white"><i class="fa-solid fa-calculator me-2"></i>{{ __('Rekonsiliasi Dana') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 0.9rem;">
                    <tbody>
                        <tr>
                            <td width="50%">{{ __('1. Total Pendapatan Kotor') }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($grossRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="ps-4 text-danger">{{ __('- Potongan Komisi Pengurus') }}</td>
                            <td class="text-end text-danger">- {{ number_format($commission, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="ps-4 text-danger">{{ __('- Pengeluaran Operasional') }}</td>
                            <td class="text-end text-danger">- {{ number_format($expenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">{{ __('2. Saldo Bersih (Netto)') }}</td>
                            <td class="text-end fw-bold bg-light">{{ number_format($grossRevenue - $commission - $expenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="ps-4 text-primary">{{ __('- Sudah Disetor ke Pusat') }}</td>
                            <td class="text-end text-primary">- {{ number_format($deposited, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #e8f0fe;">
                            <td class="fw-bold text-primary">{{ __('3. Sisa Saldo / Wajib Setor') }}</td>
                            <td class="text-end fw-bold text-primary fs-5">{{ number_format($netBalance, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CARDS VISUAL (Alternatif View) -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">{{ __('Pendapatan Kotor') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($grossRevenue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">{{ __('Total Beban (Ops+Komisi)') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($commission + $expenses, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('Terkumpul dari Koordinator') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($deposited, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Sisa Tanggungan') }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($netBalance, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TRANSACTION HISTORY TABLE -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Riwayat Transaksi (Audit Trail)') }}</h6>
            <span class="badge bg-secondary">Manual + Auto Generated</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="120">{{ __('Tanggal') }}</th>
                            <th width="100">{{ __('Tipe') }}</th>
                            <th>{{ __('Keterangan') }}</th>
                            <th width="120" class="text-end">{{ __('Pemasukan (+)') }}</th>
                            <th width="120" class="text-end">{{ __('Pengeluaran (-)') }}</th>
                            <th width="100" class="text-center">{{ __('Bukti') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $transaction->type == 'income' ? 'success' : 'danger' }} border border-{{ $transaction->type == 'income' ? 'success' : 'danger' }}">
                                    {{ strtoupper($transaction->type) }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $transaction->category }}</div>
                                <div class="small text-muted">{{ $transaction->description }}</div>
                            </td>
                            <!-- Kolom Masuk -->
                            <td class="text-end text-success fw-bold">
                                {{ $transaction->type == 'income' ? number_format($transaction->amount, 0, ',', '.') : '' }}
                            </td>
                            <!-- Kolom Keluar -->
                            <td class="text-end text-danger fw-bold">
                                {{ $transaction->type != 'income' ? number_format($transaction->amount, 0, ',', '.') : '' }}
                            </td>
                            <td class="text-center small font-monospace">
                                {{ $transaction->reference_number ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                {{ __('Tidak ada transaksi manual dalam periode ini') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- INFO AUDITOR -->
            <div class="alert alert-warning mt-3 mb-0 small">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <strong>Catatan Audit:</strong> 
                Tabel di atas hanya menampilkan transaksi yang diinput manual. Angka <strong>Komisi Pengurus</strong> (sebesar {{ number_format($commission, 0, ',', '.') }}) dihitung otomatis oleh sistem berdasarkan persentase pendapatan kotor, dan tidak muncul sebagai baris transaksi individual di tabel ini.
            </div>
        </div>
    </div>

    <!-- Bagian Audit Tambahan: Rekonstruksi Deduksi (Optional tapi bagus untuk Audit) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-dark">{{ __('Rincian Potongan Otomatis') }}</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Keterangan Potongan</th>
                        <th class="text-end">Basis Perhitungan</th>
                        <th class="text-end">Nilai Potongan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Komisi Pengurus</td>
                        <td class="text-end text-muted">{{ number_format($grossRevenue, 0, ',', '.') }} x <span class="badge bg-secondary">Rate%</span></td>
                        <td class="text-end fw-bold text-danger">- {{ number_format($commission, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
