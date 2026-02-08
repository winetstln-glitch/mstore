@extends('layouts.app')

@section('title', __('Laporan Keuangan Manajemen'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Laporan Laba Rugi & Kas') }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">{{ __('Finance') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Manager Report') }}</li>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('finance.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali') }}
            </a>
            <a href="{{ route('finance.manager_report.excel', ['month' => request('month'), 'coordinator_id' => request('coordinator_id')]) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> {{ __('Excel') }}
            </a>
            <a href="{{ route('finance.manager_report.pdf', ['month' => request('month'), 'coordinator_id' => request('coordinator_id')]) }}" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-1"></i> {{ __('PDF') }}
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow mb-4">
        <div class="card-body py-3">
            <form action="{{ route('finance.manager_report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">Pilih Koordinator</label>
                    <select name="coordinator_id" class="form-select form-select-sm">
                        <option value="">{{ __('Semua Pengurus (Global)') }}</option>
                        @foreach($coordinators as $coordinator)
                            <option value="{{ $coordinator->id }}" {{ request('coordinator_id') == $coordinator->id ? 'selected' : '' }}>
                                {{ $coordinator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Periode Laporan</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month') ?? date('Y-m') }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i> Tampilkan
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    @if(request('month'))
                    <span class="badge bg-secondary-subtle text-body border border-secondary">
                        <i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse(request('month').'-01')->translatedFormat('F Y') }}
                    </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Main Report Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 border-bottom">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-table-list me-2"></i>Rekonsiliasi Keuangan</h6>
        </div>
        <div class="card-body">
            
            <!-- TABEL 1: PENDAPATAN -->
            <h6 class="text-uppercase text-muted small fw-bold mb-2">A. Pendapatan (Revenue)</h6>
            <table class="table table-bordered table-sm mb-4">
                <tbody>
                    <tr>
                        <td style="width: 70%">{{ __('Pendapatan Member') }}</td>
                        <td class="text-end fw-bold">{{ number_format($memberIncome, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Pendapatan Voucher') }}</td>
                        <td class="text-end fw-bold">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-bold">TOTAL PENDAPATAN KOTOR</td>
                        <td class="text-end fw-bold fs-5">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- TABEL 2: POTONGAN & PENGELUARAN -->
            <h6 class="text-uppercase text-muted small fw-bold mb-2">B. Potongan & Beban Operasional</h6>
            <table class="table table-bordered table-sm mb-4">
                <tbody>
                    <!-- Komisi (Otomatis) -->
                    <tr>
                        <td style="width: 70%">
                            <span class="badge bg-danger">AUTO</span> {{ __('Komisi Pengurus') }}
                            <small class="text-muted">({{ $coordRate ?? 0 }}%)</small>
                        </td>
                        <td class="text-end text-danger">- {{ number_format($coordCommission, 0, ',', '.') }}</td>
                    </tr>
                    
                    <!-- Breakdown Pengeluaran (Manual) -->
                    <tr>
                        <td>{{ __('Transportasi') }}</td>
                        <td class="text-end text-danger">- {{ number_format($transportExpenses, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Konsumsi') }}</td>
                        <td class="text-end text-danger">- {{ number_format($consumptionExpenses, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Perbaikan & Maintenance') }}</td>
                        <td class="text-end text-danger">- {{ number_format($repairExpenses, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Biaya Operasional Lainnya') }}</td>
                        <td class="text-end text-danger">- {{ number_format($otherOperatingExpenses, 0, ',', '.') }}</td>
                    </tr>
                    
                    <!-- Total Expense -->
                    <tr class="table-danger">
                        <td class="fw-bold">TOTAL PENGELUARAN & POTONGAN</td>
                        <td class="text-end fw-bold text-dark">
                            - {{ number_format($coordCommission + $operatingExpenses, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- TABEL 3: POSISI SALDO (NET INCOME & SETORAN) -->
            <h6 class="text-uppercase text-muted small fw-bold mb-2">C. Posisi Saldo Akhir</h6>
            <table class="table table-bordered table-sm mb-0">
                <tbody>
                    <tr>
                        <td style="width: 70%" class="fw-bold bg-light">LABA BERSIH (NET INCOME)</td>
                        <td class="text-end fw-bold bg-light">
                            {{ number_format($totalRevenue - ($coordCommission + $operatingExpenses), 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-danger">
                            <i class="fa-solid fa-arrow-down me-1"></i> 
                            {{ __('Sudah Disetor ke Kas Pusat') }}
                        </td>
                        <td class="text-end text-danger">- {{ number_format($deposited, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="table-warning border border-warning border-2">
                        <td class="fw-bold fs-5">
                            <i class="fa-solid fa-wallet me-2"></i> SISA SALDO / WAJIB SETOR
                        </td>
                        <td class="text-end fw-bold fs-5">{{ number_format($netBalance, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

        </div>
        <div class="card-footer small text-muted text-center">
            * Laporan ini dihasilkan secara otomatis oleh sistem. Potongan Komisi dihitung berdasarkan persentase dari pendapatan kotor.
        </div>
    </div>

</div>
@endsection
