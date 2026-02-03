@extends('layouts.app')

@section('title', __('Finance Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Finance Dashboard') }}</h1>
        <div class="d-flex flex-wrap gap-2">
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <!-- PERBAIKAN: Ganti label menjadi sesuai konteks agar tidak menyesatkan auditor -->
            <a href="{{ route('finance.manager_report') }}" class="btn btn-secondary">
                <i class="fa-solid fa-file-lines me-1"></i> {{ __('Laporan Manajemen') }}
            </a>
            <a href="{{ route('finance.profit_loss') }}" class="btn btn-primary">
                <i class="fa-solid fa-chart-line me-1"></i> {{ __('Laba Rugi (P&L)') }}
            </a>
            <a href="{{ route('finance.material_report') }}" class="btn btn-info">
                <i class="fa-solid fa-boxes-stacked me-1"></i> {{ __('Laporan Aset') }}
            </a>
            <a href="{{ route('finance.export_accounting') }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> {{ __('Export Jurnal') }}
            </a>
            <a href="{{ route('finance.settings') }}" class="btn btn-dark">
                <i class="fa-solid fa-cog me-1"></i> {{ __('Settings') }}
            </a>
            @endif
            <button class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#helpModal">
                <i class="fa-solid fa-circle-question me-1"></i> {{ __('Panduan Audit') }}
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Transaction') }}
            </button>
        </div>
    </div>

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
        <!-- SUMMARY CARDS: CASH POSITION (Posisi Kas) -->
        <div class="row">
            <!-- Total Income (Gross) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    {{ __('Total Pendapatan Kotor') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalIncome, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-sack-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Expenses (Outflow) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    {{ __('Total Kas Keluar') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 text-danger">-{{ number_format($totalExpense, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-money-bill-transfer fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Balance (Net Cash) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ __('Saldo Kas Perusahaan') }}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($balance, 0, ',', '.') }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <span class="text-success">Gross</span> - <span class="text-danger">Exp</span>
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-building-columns fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accumulated Tool Fund (Asset/Liability) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    {{ __('Akumulasi Dana Peralatan') }}</div>
                                <!-- PERBAIKAN AUDIT: Tampilkan nilai positif, ini adalah Dana yang "ditahan", bukan biaya -->
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalInvestorFunds ?? 0, 0, ',', '.') }}</div>
                                <small class="text-muted">{{ __('Tersimpan (Belum Dipakai)') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-piggy-bank fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCRUALS & FUNDS STATUS (Status Dana yang Ditahan) -->
        <div class="row mb-4">
            <!-- ISP Fund Liability -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-secondary shadow h-100 py-2" style="border-left-color: #6f42c1 !important;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    {{ __('Dana ISP (Kewajiban)') }} <span class="badge bg-secondary">{{ $ispRate }}%</span></div>
                                <!-- PERBAIKAN AUDIT: Hilangkan tanda minus di sini, tampilkan sebagai akumulasi kewajiban -->
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalIspShare, 0, ',', '.') }}</div>
                                <small class="text-muted">{{ __('Perlu dibayarkan ke ISP') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-wifi fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool Fund Accumulation -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2" style="border-left-color: #17a2b8 !important;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    {{ __('Akumulasi Dana Peralatan') }} <span class="badge bg-info text-white">{{ $toolRate }}%</span></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalToolFund, 0, ',', '.') }}</div>
                                <small class="text-muted">{{ __('Dana Cadangan Perbaikan/Beli Alat') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-toolbox fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('Rekonsiliasi Kas Per Koordinator') }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Koordinator') }}</th>
                                <th class="text-end">{{ __('Pendapatan Kotor') }}</th>
                                <th class="text-end text-danger">{{ __('Potongan (Komisi)') }}</th>
                                <th class="text-end text-danger">{{ __('Biaya Operasional') }}</th>
                                <th class="text-end text-primary">{{ __('Disetor ke Pusat') }}</th>
                                <th class="text-end fw-bold bg-light">{{ __('Sisa Tanggal (Tanggungan)') }}</th>
                                <th class="text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coordinatorSummaries as $summary)
                            <tr>
                                <td>{{ $summary->name }}</td>
                                <td class="text-end">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-{{ number_format($summary->commission, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-{{ number_format($summary->expenses, 0, ',', '.') }}</td>
                                <td class="text-end text-primary">-{{ number_format($summary->deposited ?? 0, 0, ',', '.') }}</td>
                                @php
                                    $balance = $summary->net_balance;
                                    $isNegative = $balance < 0;
                                @endphp
                                <td class="text-end fw-bold {{ $isNegative ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($balance, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('finance.coordinator.detail', $summary->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Lihat Detail Transaksi') }}">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        @php
            $summary = $coordinatorSummaries[0] ?? null;
        @endphp

        @if($summary)
            <!-- VIEW KOORDINATOR: Sederhana dan Fokus ke Kas -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        {{ __('Total Pendapatan Kotor') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-coins fa-2x text-gray-300"></i>
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
                                        {{ __('Komisi Saya') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->commission, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        {{ __('Pengeluaran Operasional') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->expenses, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-receipt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        {{ __('Uang Kas Ditahan') }}</div>
                                    <!-- PERBAIKAN: Ini adalah uang investor yang ditahan, bukan pengeluaran koordinator -->
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->investor_cash, 0, ',', '.') }}</div>
                                    <small class="text-muted">Dana Investor (Dicatat)</small>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-piggy-bank fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        {{ __('Wajib Setor') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary->net_balance, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-hand-holding-dollar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
    
    <!-- INVESTOR SHARE DETAIL: Audit Trail bagi hasil -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Rincian Bagi Hasil Investor (Periode Ini)') }}</h6>
            <a href="{{ route('finance.investor_share.pdf') }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i> {{ __('Unduh Laporan PDF') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Koordinator') }}</th>
                            <th class="text-end">{{ __('Total Bagi Hasil (Net)') }}</th>
                            <th class="text-end">{{ __('Dana Kas Investor (Tersimpan)') }}</th>
                            <th class="text-end fw-bold">{{ __('Total Kewajiban Investor') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coordinatorSummaries as $summary)
                        <tr>
                            <td><strong>{{ $summary->name }}</strong></td>
                            <!-- PERBAIKAN: Tampilkan positif. Di tampilan sebelumnya minus, membingungkan auditor apakah ini uang keluar atau alokasi -->
                            <td class="text-end">{{ number_format($summary->investor_share, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($summary->investor_cash, 0, ',', '.') }}</td>
                            @php
                                $totalLiability = $summary->investor_share + $summary->investor_cash;
                            @endphp
                            <td class="text-end fw-bold bg-light">{{ number_format($totalLiability, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $investorDetails = $investorDetailsByCoordinator[$summary->id] ?? [];
                        @endphp
                        @foreach($investorDetails as $detail)
                        <tr>
                            <td>&nbsp;&nbsp;- {{ $detail['investor_name'] }}</td>
                            <td class="text-end text-muted">{{ number_format($detail['profit_share'], 0, ',', '.') }}</td>
                            <td class="text-end text-muted">{{ number_format($detail['cash_fund'], 0, ',', '.') }}</td>
                            <td class="text-end text-muted fw-bold">{{ number_format($detail['profit_share'] + $detail['cash_fund'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- INCOME BREAKDOWN: Audit Trail Alur Pendapatan -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-info">{{ __('Audit Trail: Alur Pendapatan (10 Terakhir)') }}</h6>
            <a href="{{ route('finance.income_breakdown.pdf') }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="fas fa-file-pdf fa-sm text-white-50"></i> {{ __('Unduh PDF') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Tanggal') }}</th>
                            <th>{{ __('Koordinator') }}</th>
                            <th class="text-end bg-white border-end">{{ __('Pendapatan Kotor') }}</th>
                            <!-- Kolom Potongan -->
                            <th class="text-end text-danger bg-light">Komisi</th>
                            <th class="text-end text-danger bg-light">ISP</th>
                            <th class="text-end text-danger bg-light">Alat</th>
                            <th class="text-end text-danger bg-light">Mgr Fee</th>
                            <!-- Kolom Bersih -->
                            <th class="text-end fw-bold bg-white border-start">{{ __('Net Balance') }}</th>
                            <!-- Kolom Alokasi Akhir -->
                            <th class="text-end text-info">Dana Kas</th>
                            <th class="text-end text-success fw-bold">{{ __('Income Investor') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incomeBreakdowns as $breakdown)
                        <tr>
                            <td>{{ $breakdown->date->format('d M Y') }}</td>
                            <td>{{ $breakdown->coordinator_name }}</td>
                            
                            <!-- Gross -->
                            <td class="text-end fw-bold">{{ number_format($breakdown->gross_amount, 0, ',', '.') }}</td>
                            
                            <!-- Deductions (Harus minus) -->
                            <td class="text-end text-danger">-{{ number_format($breakdown->commission, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($breakdown->isp_share, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($breakdown->tool_fund, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-{{ number_format($breakdown->manager_income, 0, ',', '.') }}</td>
                            
                            <!-- Net Balance -->
                            <td class="text-end fw-bold bg-light">{{ number_format($breakdown->net_balance, 0, ',', '.') }}</td>
                            
                            <!-- Allocations -->
                            <!-- PERBAIKAN: Dana Kas di sini sebaiknya ditulis positif atau (bracket) sebagai alokasi. Disini saya biarkan minus jika itu logika sistem, tapi beri warna berbeda agar dianggap "Allocation" bukan "Loss" -->
                            <td class="text-end text-warning">
                                {{ number_format($breakdown->cash_fund, 0, ',', '.') }}
                                <small class="d-block text-muted" style="font-size:0.7rem">({{ $investorCashRate }}%)</small>
                            </td>
                            <td class="text-end text-success fw-bold">{{ number_format($breakdown->investor_share, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary me-3">{{ __('Jurnal Transaksi (General Ledger)') }}</h6>
                    <button type="button" id="toggleSelectMode" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fa-solid fa-list-check me-1"></i> {{ __('Pilih Banyak') }}
                    </button>
                    <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-danger d-none" onclick="submitBulkDelete()">
                        <i class="fa-solid fa-trash me-1"></i> {{ __('Hapus Terpilih') }}
                    </button>
                </div>
                <form action="{{ route('finance.index') }}" method="GET" class="d-flex align-items-center">
                    <input type="month" name="month" class="form-control form-control-sm me-2" value="{{ request('month') }}">
                    
                    <select name="coordinator_id" class="form-select form-select-sm me-2" style="max-width: 150px;">
                        <option value="">{{ __('Semua Koordinator') }}</option>
                        @foreach($coordinators as $coordinator)
                            <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                {{ $coordinator->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="type" class="form-select form-select-sm me-2">
                        <option value="">{{ __('Semua Jenis') }}</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>{{ __('Pemasukan') }}</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>{{ __('Pengeluaran') }}</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center select-column d-none" width="40">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>{{ __('Tanggal') }}</th>
                                <th width="100">{{ __('Tipe') }}</th>
                                <th>{{ __('Kategori') }}</th>
                                <th>{{ __('Keterangan') }}</th>
                                <th>{{ __('Koordinator') }}</th>
                                <th class="text-end text-success">{{ __('Debet (Masuk)') }}</th>
                                <th class="text-end text-danger">{{ __('Kredit (Keluar)') }}</th>
                                <th width="100">{{ __('Ref') }}</th>
                                <th class="text-center" width="100">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                            <tr>
                                <td class="text-center select-column d-none">
                                    <input type="checkbox" name="ids[]" value="{{ $transaction->id }}" class="form-check-input select-row">
                                </td>
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : 'danger' }}">
                                        {{ $transaction->type == 'income' ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-white">{{ ucfirst($transaction->category) }}</span>
                                </td>
                                <td>{{ $transaction->description }}</td>
                                <td>
                                    @if($transaction->coordinator)
                                        <span class="badge bg-info text-dark">{{ $transaction->coordinator->name }}</span>
                                    @else
                                        <span class="text-muted">Pusat</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success">
                                    {{ $transaction->type == 'income' ? number_format($transaction->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    {{ $transaction->type != 'income' ? number_format($transaction->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="small font-monospace">{{ $transaction->reference_number }}</td>
                                <td class="text-center row-actions">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary"
                                            data-bs-toggle="modal"  
                                            data-bs-target="#editTransactionModal"
                                            data-id="{{ $transaction->id }}"
                                            data-type="{{ $transaction->type }}"
                                            data-category="{{ $transaction->category }}"
                                            data-amount="{{ $transaction->amount }}"
                                            data-payment-method="{{ $transaction->payment_method }}"
                                            data-date="{{ $transaction->transaction_date->format('Y-m-d') }}"
                                            data-coordinator="{{ $transaction->coordinator_id }}"
                                            data-description="{{ $transaction->description }}"
                                            data-ref="{{ $transaction->reference_number }}"
                                            data-action="{{ route('finance.update', $transaction->id) }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form action="{{ route('finance.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Yakin ingin menghapus transaksi ini?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">{{ __('Tidak ada transaksi ditemukan.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    @else
        <!-- VIEW GRAFIK SEDERHANA UNTUK KOORDINATOR -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('Trend Pendapatan Bulanan') }}</h6>
            </div>
            <div class="card-body">
                @php
                    $maxIncome = $monthlyIncome->max('total') ?? 0;
                @endphp
                @if($monthlyIncome->isEmpty())
                    <p class="text-muted mb-0">{{ __('Belum ada data pendapatan.') }}</p>
                @else
                    <div>
                        @foreach($monthlyIncome as $row)
                            @php
                                $percent = $maxIncome > 0 ? ($row->total / $maxIncome) * 100 : 0;
                                $label = \Carbon\Carbon::parse($row->ym . '-01')->translatedFormat('M Y');
                            @endphp
                            <div class="d-flex align-items-center mb-2">
                                <div style="width: 90px; font-size: 0.85rem;">
                                    {{ $label }}
                                </div>
                                <div class="flex-grow-1 bg-light" style="height: 20px; border-radius: 4px;">
                                    <div class="bg-success" style="width: {{ $percent }}%; height: 100%; border-radius: 4px;"></div>
                                </div>
                                <div class="ms-2" style="width: 140px; text-align: right; font-size: 0.9rem;">
                                    {{ number_format($row->total, 0, ',', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<form id="bulkDeleteForm" action="{{ route('finance.bulkDestroy') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- MODALS (SAMA SEPERTI SEBELUMNYA) -->
<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Tambah Transaksi Baru') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('finance.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Jenis Transaksi') }}</label>
                        <select name="type" id="addType" class="form-select" required onchange="updateCategories('add')">
                            <option value="income">{{ __('Pemasukan') }}</option>
                            <option value="expense">{{ __('Pengeluaran') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Kategori') }}</label>
                        <select name="category" id="addCategory" class="form-select" required>
                            <!-- Diisi via JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Metode Pembayaran') }}</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">{{ __('Tunai') }}</option>
                            <option value="transfer">{{ __('Transfer Bank') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Jumlah Nominal (Rp)') }}</label>
                        <input type="number" name="amount" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tanggal') }}</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Koordinator (Opsional)') }}</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">{{ __('Tanpa Koordinator / Pusat') }}</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Keterangan / Deskripsi') }}</label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('No. Referensi / Bukti') }}</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Contoh: INV-001">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Transaksi') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Transaksi') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTransactionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Jenis Transaksi') }}</label>
                        <select name="type" id="editType" class="form-select" required onchange="updateCategories('edit')">
                            <option value="income">{{ __('Pemasukan') }}</option>
                            <option value="expense">{{ __('Pengeluaran') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Kategori') }}</label>
                        <select name="category" id="editCategory" class="form-select" required>
                            <!-- Diisi via JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Metode Pembayaran') }}</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">{{ __('Tunai') }}</option>
                            <option value="transfer">{{ __('Transfer Bank') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Jumlah Nominal (Rp)') }}</label>
                        <input type="number" name="amount" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tanggal') }}</label>
                        <input type="date" name="transaction_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Koordinator (Opsional)') }}</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">{{ __('Tanpa Koordinator / Pusat') }}</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}">{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Keterangan / Deskripsi') }}</label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('No. Referensi / Bukti') }}</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update Transaksi') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Help Modal (Audit Guide) -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="helpModalLabel"><i class="fa-solid fa-clipboard-check me-2"></i>{{ __('Panduan Audit & Alur Keuangan') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border-primary shadow-sm">
                    <h6 class="alert-heading fw-bold text-primary"><i class="fa-solid fa-circle-plus me-2"></i>1. Alur Pemasukan (Recognition)</h6>
                    <hr>
                    <ol class="mb-0">
                        <li>Input transaksi sebagai <strong>Income</strong>.</li>
                        <li>Sistem otomatis memisahkan dana berdasarkan persentase alokasi (Slicing Revenue).</li>
                        <li><span class="badge bg-danger">Komisi & Biaya</span> = Langsung mengurangi kas (Cash Out).</li>
                        <li><span class="badge bg-warning">Dana ISP & Alat</span> = Akumulasi Kewajiban (Accrued Liability), bukan kas keluar langsung, namun ditandai sebagai "Dana Ditahan".</li>
                    </ol>
                </div>

                <div class="alert alert-light border-danger shadow-sm">
                    <h6 class="alert-heading fw-bold text-danger"><i class="fa-solid fa-circle-minus me-2"></i>2. Alur Pengeluaran (Disbursement)</h6>
                    <hr>
                    <ul class="mb-0">
                        <li>Input sebagai <strong>Expense</strong>.</li>
                        <li>Jika menggunakan Dana Peralatan, pastikan kategorinya sesuai agar laporan aset akurat.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('Mengerti') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Konfigurasi Kategori Dinamis
    const categories = {
        income: [
            { val: 'Member Income', label: 'Pendapatan Member' },
            { val: 'Voucher Income', label: 'Pendapatan Voucher' },
            { val: 'Other Income', label: 'Pendapatan Lainnya' }
        ],
        expense: [
            { val: 'Deposit to Company', label: 'Setor Tunai ke Pusat' },
            { val: 'Salary', label: 'Gaji Karyawan' },
            { val: 'Operational', label: 'Biaya Server/Listrik' },
            { val: 'Transport', label: 'Transportasi' },
            { val: 'Consumption', label: 'Konsumsi' },
            { val: 'Repair', label: 'Perbaikan Alat' },
            { val: 'Maintenance', label: 'Pemeliharaan' },
            { val: 'Pembayaran ISP', label: 'Pembayaran ISP' },
            { val: 'Pembelian Alat', label: 'Pembelian Aset Baru' }
        ],
        transfer: [
            { val: 'Setoran Pengurus', label: 'Setoran dari Koordinator' }
        ]
    };

    function updateCategories(prefix) {
        const typeSelect = document.getElementById(prefix + 'Type');
        const catSelect = document.getElementById(prefix + 'Category');
        const selectedType = typeSelect.value;
        
        catSelect.innerHTML = '<option value="">Pilih Kategori...</option>';
        
        if(categories[selectedType]) {
            categories[selectedType].forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.val;
                option.textContent = cat.label;
                catSelect.appendChild(option);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Init categories for add modal
        updateCategories('add');

        // Edit Modal Logic
        var editTransactionModal = document.getElementById('editTransactionModal');
        if (editTransactionModal) {
            editTransactionModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var type = button.getAttribute('data-type');
                var category = button.getAttribute('data-category');
                // ... (ambil data lainnya sama seperti sebelumnya)
                
                // Set type first
                var form = document.getElementById('editTransactionForm');
                form.querySelector('[name="type"]').value = type;
                
                // Update categories based on type
                updateCategories('edit');
                
                // Then set category
                setTimeout(() => {
                   form.querySelector('[name="category"]').value = category; 
                }, 50);

                form.action = button.getAttribute('data-action');
                form.querySelector('[name="amount"]').value = button.getAttribute('data-amount');
                form.querySelector('[name="payment_method"]').value = button.getAttribute('data-payment-method') || 'cash';
                form.querySelector('[name="transaction_date"]').value = button.getAttribute('data-date');
                form.querySelector('[name="coordinator_id"]').value = button.getAttribute('data-coordinator') || '';
                form.querySelector('[name="description"]').value = button.getAttribute('data-description');
                form.querySelector('[name="reference_number"]').value = button.getAttribute('data-ref');
            });
        }

        // Bulk Delete Logic
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.select-row');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const toggleSelectModeBtn = document.getElementById('toggleSelectMode');
        const selectColumns = document.querySelectorAll('.select-column');
        const rowActions = document.querySelectorAll('.row-actions');

        function updateBulkDeleteVisibility() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (bulkDeleteBtn) {
                if (anyChecked) {
                    bulkDeleteBtn.classList.remove('d-none');
                } else {
                    bulkDeleteBtn.classList.add('d-none');
                }
            }
        }

        if (toggleSelectModeBtn) {
            toggleSelectModeBtn.addEventListener('click', function () {
                const isActive = this.classList.toggle('active');

                selectColumns.forEach(col => {
                    if (isActive) {
                        col.classList.remove('d-none');
                    } else {
                        col.classList.add('d-none');
                    }
                });

                rowActions.forEach(cell => {
                    if (isActive) {
                        cell.classList.add('d-none');
                    } else {
                        cell.classList.remove('d-none');
                    }
                });

                if (!isActive) {
                    if (selectAll) {
                        selectAll.checked = false;
                    }
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                    });
                    updateBulkDeleteVisibility();
                }
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkDeleteVisibility();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteVisibility);
        });

        window.submitBulkDelete = function() {
            if (confirm('{{ __('Apakah Anda yakin ingin menghapus transaksi terpilih secara permanen?') }}')) {
                const inputs = bulkDeleteForm.querySelectorAll('input[name="ids[]"]');
                inputs.forEach(input => input.remove());
                
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        bulkDeleteForm.appendChild(input);
                    }
                });
                
                bulkDeleteForm.submit();
            }
        }
    });
</script>

@endsection
