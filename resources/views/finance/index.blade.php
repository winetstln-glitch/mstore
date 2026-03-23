@extends('layouts.app')

@section('title', __('Finance Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h1 class="h3 mb-0 text-body">{{ __('Finance Dashboard') }}</h1>
        <div class="row g-2 row-cols-2 row-cols-md-auto justify-content-md-end w-100 w-md-auto">
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <!-- PERBAIKAN: Ganti label menjadi sesuai konteks agar tidak menyesatkan auditor -->
            <div class="col">
                <a href="{{ route('finance.manager_report') }}" class="btn btn-secondary w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" title="{{ __('Laporan Manajemen') }}">
                    <i class="fa-solid fa-file-lines me-1"></i> <span>{{ __('Manajemen') }}</span>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('finance.investor_report') }}" class="btn btn-warning text-dark w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" title="{{ __('Laporan Investor') }}">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i> <span>{{ __('Investor') }}</span>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('finance.profit_loss') }}" class="btn btn-primary w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" title="{{ __('Laba Rugi (P&L)') }}">
                    <i class="fa-solid fa-chart-line me-1"></i> <span>{{ __('P&L') }}</span>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('finance.material_report') }}" class="btn btn-info w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" title="{{ __('Laporan Aset') }}">
                    <i class="fa-solid fa-boxes-stacked me-1"></i> <span>{{ __('Aset') }}</span>
                </a>
            </div>
            
            <!-- Integration: Accounting Reports Dropdown -->
            <div class="col">
                <div class="dropdown w-100 h-100">
                    <button class="btn btn-orange border dropdown-toggle w-100 h-100 d-flex align-items-center justify-content-center" type="button" id="accountingDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Laporan Akuntansi') }}">
                        <i class="fa-solid fa-book-open me-1"></i> <span>{{ __('Akuntansi') }}</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="accountingDropdown">
                        <li><a class="dropdown-item" href="{{ route('accounting.balance_sheet') }}"><i class="fa-solid fa-scale-balanced me-2"></i>{{ __('Neraca') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('accounting.cash_flow') }}"><i class="fa-solid fa-money-bill-wave me-2"></i>{{ __('Arus Kas') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('accounting.income_statement') }}"><i class="fa-solid fa-file-invoice-dollar me-2"></i>{{ __('Laba Rugi') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('accounting.ledger') }}"><i class="fa-solid fa-book me-2"></i>{{ __('Buku Besar') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('accounting.trial_balance') }}"><i class="fa-solid fa-table-list me-2"></i>{{ __('Neraca Saldo') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="col">
                <a href="{{ route('finance.export_accounting') }}" class="btn btn-success w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" title="{{ __('Export Jurnal') }}">
                    <i class="fa-solid fa-file-excel me-1"></i> <span>{{ __('Jurnal') }}</span>
                </a>
            </div>
            @endif
            <div class="col">
                <button class="btn btn-light border w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#helpModal" title="{{ __('Panduan Audit') }}">
                    <i class="fa-solid fa-circle-question me-1"></i> <span>{{ __('Panduan') }}</span>
                </button>
            </div>
            <div class="col">
                <button class="btn btn-primary w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addTransactionModal" title="{{ __('Add Transaction') }}">
                    <i class="fa-solid fa-plus me-1"></i> <span>{{ __('Transaksi') }}</span>
                </button>
            </div>
        </div>
    </div>

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
        <!-- SUMMARY CARDS: CASH POSITION (Posisi Kas) -->
        <div class="row">
            <!-- Total Income (Gross) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    {{ __('Total Pendapatan Kotor') }}</div>
                                <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($totalIncome, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-sack-dollar fa-2x text-body-tertiary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Expenses (Outflow) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    {{ __('Total Kas Keluar') }}</div>
                                <div class="h5 mb-0 font-weight-bold finance-kpi-value text-danger">-{{ number_format($totalExpense, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-money-bill-transfer fa-2x text-body-tertiary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Balance (Net Cash) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ __('Saldo Kas Perusahaan') }}</div>
                                <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($cashBalance ?? 0, 0, ',', '.') }}</div>
                                <small class="text-body-secondary" style="font-size: 0.75rem;">
                                    <span class="text-success">Income</span> - <span class="text-danger">Expense</span> - <span class="text-warning">Transfer</span>
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-building-columns fa-2x text-body-tertiary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer to Company (Non Expense) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-4 border-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    {{ __('Setoran ke Kantor (Transfer)') }}</div>
                                <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($totalTransfer ?? 0, 0, ',', '.') }}</div>
                                <small class="text-body-secondary">{{ __('Dicatat transfer, bukan biaya operasional') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-building-circle-arrow-right fa-2x text-body-tertiary"></i>
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
                <div class="card border-start border-4 border-secondary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    {{ __('Dana ISP (Kewajiban)') }} <span class="badge bg-secondary">{{ $ispRate ?? 0 }}%</span></div>
                                <!-- PERBAIKAN AUDIT: Hilangkan tanda minus di sini, tampilkan sebagai akumulasi kewajiban -->
                                <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($totalIspShare ?? 0, 0, ',', '.') }}</div>
                                <small class="text-body-secondary">{{ __('Perlu dibayarkan ke ISP') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-wifi fa-2x text-body-tertiary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool Fund Accumulation -->
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-start border-4 border-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    {{ __('Akumulasi Dana Peralatan') }} <span class="badge bg-info text-white">{{ $toolRate ?? 0 }}%</span></div>
                                <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($totalToolFund ?? 0, 0, ',', '.') }}</div>
                                <small class="text-body-secondary">{{ __('Dana Cadangan Perbaikan/Beli Alat') }}</small>
                            </div>
                            <div class="col-auto">
                                <i class="fa-solid fa-toolbox fa-2x text-body-tertiary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================ MODIFIKASI UTAMA ============================ -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('Rekonsiliasi Kas Pengurus (Cash Only)') }}</h6>
                <span class="badge bg-warning text-dark">Exclude Ambil Barang</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 px-3 small mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Laporan ini hanya menghitung <strong>Arus Kas Tunai</strong>. Biaya "Ambil Barang" tidak memotong target setoran.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-responsive-mobile" width="100%" cellspacing="0">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th rowspan="2" class="text-end">{{ __('Koordinator') }}</th>
                                <th rowspan="2" class="text-end">{{ __('Pendapatan') }}</th>
                                <th rowspan="2" class="text-end text-danger">{{ __('Komisi') }}</th>
                                <th rowspan="2" class="text-end text-danger">
                                    {{ __('Pengeluaran Tunai') }}
                                    <br><small class="font-weight-normal text-body-secondary">(Ops & Beli Luar)</small>
                                </th>
                                <th colspan="2" class="text-center fw-bold">{{ __('Setoran') }}</th>
                                <th rowspan="2" class="text-end fw-bold">{{ __('Sisa Setor') }}</th>
                                <th rowspan="2" class="text-center">{{ __('Aksi') }}</th>
                            </tr>
                            <tr>
                                <th class="text-end fw-bold text-primary">{{ __('Wajib Setor') }}</th>
                                <th class="text-end fw-bold">{{ __('Sudah Setor') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coordinatorSummaries as $summary)
                            @php
                                // Pastikan variabel 'cash_expenses' ada di Controller Anda
                                // Jika belum, ganti dengan $summary->expenses (namun logikanya akan salah)
                                $cashExp = $summary->cash_expenses ?? 0; 
                                $deposited = $summary->deposited ?? 0;
                                
                                // Rumus Target: Pendapatan - Komisi - Pengeluaran Tunai
                                $mustDeposit = $summary->gross_revenue - $summary->commission - $cashExp;
                                
                                // Rumus Sisa: Wajib Setor - Sudah Disetor
                                $remainingCash = $mustDeposit - $deposited;
                            @endphp
                            <tr>
                                <td class="align-middle">
                                    <strong>{{ $summary->name }}</strong>
                                </td>
                                <td class="text-end align-middle">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</td>
                                <td class="text-end align-middle text-danger">-{{ number_format($summary->commission, 0, ',', '.') }}</td>
                                
                                <!-- Kolom Pengeluaran Tunai (Exclude Barang) -->
                                <td class="text-end align-middle text-danger">
                                    -{{ number_format($cashExp, 0, ',', '.') }}
                                </td>
                                
                                <!-- Kolom Target (Wajib Setor) -->
                                <td class="text-end align-middle fw-bold">
                                    {{ number_format($mustDeposit, 0, ',', '.') }}
                                </td>
                                
                                <!-- Kolom Sudah Setor -->
                                <td class="text-end align-middle text-primary">
                                    {{ number_format($deposited, 0, ',', '.') }}
                                </td>
                                
                                <!-- Kolom Sisa -->
                                <td class="text-end align-middle fw-bold {{ $remainingCash < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($remainingCash, 0, ',', '.') }}
                                </td>
                                
                                <td class="text-center align-middle">
                                    <a href="{{ route('finance.coordinator.detail', $summary->id) }}" class="btn btn-outline-primary" title="{{ __('Lihat Detail Transaksi') }}">
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
        <!-- ======================================================================== -->

    @else
        @php
            $summary = $coordinatorSummaries[0] ?? null;
        @endphp

        @if($summary)
            <!-- VIEW KOORDINATOR: Sederhana dan Fokus ke Kas -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-start border-4 border-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        {{ __('Total Pendapatan Kotor') }}</div>
                                    <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($summary->gross_revenue, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-coins fa-2x text-body-tertiary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-start border-4 border-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        {{ __('Komisi Saya') }}</div>
                                    <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($summary->commission, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-start border-4 border-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        {{ __('Pengeluaran Tunai') }}</div>
                                    <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($summary->cash_expenses ?? 0, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-receipt fa-2x text-body-tertiary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-start border-4 border-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        {{ __('Uang Kas Ditahan') }}</div>
                                    <!-- PERBAIKAN: Ini adalah uang investor yang ditahan, bukan pengeluaran koordinator -->
                                    <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($summary->investor_cash ?? 0, 0, ',', '.') }}</div>
                                    <small class="text-body-secondary">Dana Investor (Dicatat)</small>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-piggy-bank fa-2x text-body-tertiary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-start border-4 border-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        {{ __('Wajib Setor') }}</div>
                                    <div class="h5 mb-0 font-weight-bold finance-kpi-value text-body">{{ number_format($summary->net_balance ?? 0, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-hand-holding-dollar fa-2x text-body-tertiary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex flex-column flex-xl-row align-items-start align-items-xl-center justify-content-between gap-3">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 w-100 w-xl-auto">
                        <h6 class="m-0 font-weight-bold text-primary">{{ __('Jurnal Transaksi (General Ledger)') }}</h6>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
                            <button type="button" id="toggleSelectMode" class="btn btn-outline-secondary btn-sm w-100 w-md-auto">
                                <i class="fa-solid fa-list-check me-1"></i> {{ __('Pilih Banyak') }}
                            </button>
                            <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm d-none w-100 w-md-auto" onclick="submitBulkDelete()">
                                <i class="fa-solid fa-trash me-1"></i> {{ __('Hapus Terpilih') }}
                            </button>
                        </div>
                    </div>
                    <form action="{{ route('finance.index') }}" method="GET" class="w-100 w-xl-auto">
                        <div class="row g-2 align-items-stretch align-items-xl-center row-cols-1 row-cols-sm-2 row-cols-lg-auto">
                            <div class="col">
                                <input type="month" name="month" class="form-control form-control-sm w-100" value="{{ request('month') }}">
                            </div>
                            <div class="col">
                                <select name="coordinator_id" class="form-select form-select-sm w-100">
                                    <option value="">Semua Koordinator</option>
                                    @foreach($coordinators as $c)
                                        <option value="{{ $c->id }}" @selected((string) request('coordinator_id') === (string) $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <select name="type" class="form-select form-select-sm w-100">
                                    <option value="">Semua Jenis</option>
                                    <option value="income" @selected(request('type') === 'income')>Pemasukan</option>
                                    <option value="expense" @selected(request('type') === 'expense')>Pengeluaran</option>
                                    <option value="transfer" @selected(request('type') === 'transfer')>Transfer</option>
                                </select>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-responsive-mobile" width="100%" cellspacing="0">
                        <thead class="bg-body-tertiary">
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
                            @forelse($transactions ?? [] as $transaction)
                            <tr>
                                <td class="text-center select-column d-none">
                                    <input type="checkbox" name="ids[]" value="{{ $transaction->id }}" class="form-check-input select-row">
                                </td>
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td>
                                    @if($transaction->type === 'income')
                                        <span class="badge bg-success">Masuk</span>
                                    @elseif($transaction->type === 'transfer')
                                        <span class="badge bg-warning text-dark">Transfer</span>
                                    @else
                                        <span class="badge bg-danger">Keluar</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucfirst($transaction->category) }}</span>
                                </td>
                                <td>{{ $transaction->description }}</td>
                                <td>
                                    @if($transaction->coordinator)
                                        <span class="badge bg-info text-dark">{{ $transaction->coordinator->name }}</span>
                                    @else
                                        <span class="text-body-secondary">Pusat</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success">
                                    {{ $transaction->type === 'income' ? number_format($transaction->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    {{ in_array($transaction->type, ['expense', 'transfer'], true) ? number_format($transaction->amount, 0, ',', '.') : '-' }}
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
                    {{ $transactions->links() ?? '' }}
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
                                <div class="flex-grow-1 bg-secondary" style="height: 20px; border-radius: 4px; --bs-bg-opacity: .2;">
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
                <h5 class="modal-title">{{ __('Add Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('finance.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="income">{{ __('Pemasukan') }}</option>
                            <option value="expense">{{ __('Pengeluaran') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="Member Income">{{ __('Iuran Bulanan (Member Income)') }}</option>
                            <option value="Voucher Income">{{ __('Voucher (Voucher Income)') }}</option>
                            <option value="Installation Fee">{{ __('Biaya Pasang') }}</option>
                            <option value="Biaya Operasional">{{ __('Biaya Operasional (Bensin/Makan)') }}</option>
                            <option value="Pembelian Alat">{{ __('Beli Alat (diluar)') }}</option>
                            <option value="Ambil Barang">{{ __('Ambil Barang (Stok)') }}</option>
                            <option value="Gaji">{{ __('Gaji') }}</option>
                            <option value="Deposit to Company">{{ __('Setor ke Kantor (Deposit)') }}</option>
                            <option value="Lainnya">{{ __('Lainnya') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator') }} (Optional)</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">-- None --</option>
                            @foreach($coordinators ?? [] as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
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
                <h5 class="modal-title">{{ __('Edit Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTransactionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="edit_type" class="form-select" required>
                            <option value="income">{{ __('Pemasukan') }}</option>
                            <option value="expense">{{ __('Pengeluaran') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category" id="edit_category" class="form-select" required>
                            <option value="Member Income">{{ __('Iuran Bulanan (Member Income)') }}</option>
                            <option value="Voucher Income">{{ __('Voucher (Voucher Income)') }}</option>
                            <option value="Installation Fee">{{ __('Biaya Pasang') }}</option>
                            <option value="Biaya Operasional">{{ __('Biaya Operasional (Bensin/Makan)') }}</option>
                            <option value="Pembelian Alat">{{ __('Beli Alat (Stok)') }}</option>
                            <option value="Gaji">{{ __('Gaji') }}</option>
                            <option value="Deposit to Company">{{ __('Setor ke Kantor (Deposit)') }}</option>
                            <option value="Ambil Barang">{{ __('Ambil Barang (Stok)') }}</option>
                            <option value="Lainnya">{{ __('Lainnya') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coordinator') }} (Optional)</label>
                        <select name="coordinator_id" id="edit_coordinator_id" class="form-select">
                            <option value="">-- None --</option>
                            @foreach($coordinators ?? [] as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                         <label class="form-label">{{ __('Ref') }}</label>
                         <input type="text" name="reference_number" id="edit_reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Panduan Audit Keuangan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Saldo Kas Perusahaan</h6>
                <p class="small text-muted">Adalah uang tunai riil yang seharusnya ada di tangan bendahara/admin saat ini. Dihitung dari Total Masuk - Total Keluar.</p>
                
                <h6>2. Akumulasi Dana Peralatan</h6>
                <p class="small text-muted">Bukan biaya hilang, tapi dana yang disisihkan (virtual) dari pendapatan untuk beli alat di masa depan. Uangnya masih ada di Saldo Kas Perusahaan sampai dibelanjakan.</p>
                
                <h6>3. Wajib Setor Koordinator</h6>
                <p class="small text-muted">Uang yang harus disetor koordinator ke admin. Rumus: (Pendapatan Kotor - Komisi) - Biaya Operasional.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleSelectMode');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const selectCols = document.querySelectorAll('.select-column');
        const checkboxes = document.querySelectorAll('.select-row');
        const selectAll = document.getElementById('selectAll');
        
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                selectCols.forEach(el => el.classList.toggle('d-none'));
                if (bulkDeleteBtn) {
                    bulkDeleteBtn.classList.toggle('d-none');
                }
            });
        }
        
        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }
        
        const editModal = document.getElementById('editTransactionModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) {
                    return;
                }
                const action = button.getAttribute('data-action');
                const type = button.getAttribute('data-type');
                const category = button.getAttribute('data-category');
                const amount = button.getAttribute('data-amount');
                const date = button.getAttribute('data-date');
                const coordinator = button.getAttribute('data-coordinator');
                const description = button.getAttribute('data-description');
                const ref = button.getAttribute('data-ref');

                const form = document.getElementById('editTransactionForm');
                form.action = action;

                document.getElementById('edit_type').value = type;
                document.getElementById('edit_category').value = category;
                document.getElementById('edit_amount').value = amount;
                document.getElementById('edit_transaction_date').value = date;
                document.getElementById('edit_coordinator_id').value = coordinator || '';
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_reference_number').value = ref;
            });
        }
    });

    function submitBulkDelete() {
        if(!confirm('{{ __("Yakin ingin menghapus transaksi terpilih?") }}')) return;
        
        const ids = [];
        document.querySelectorAll('.select-row:checked').forEach(cb => ids.push(cb.value));
        
        if(ids.length === 0) {
            alert('{{ __("Pilih transaksi dulu!") }}');
            return;
        }

        const form = document.getElementById('bulkDeleteForm');
        if (!form) {
            return;
        }
        // Clear old inputs
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        
        // Add new inputs
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        form.submit();
    }
</script>
@endpush
@endsection
