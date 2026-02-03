@extends('layouts.app')

@section('title', __('Laporan Operasional Pengurus'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Laporan Operasional & Setoran') }}</h1>
        <!-- Tombol Download PDF/Excel -->
        <div class="d-flex gap-2">
             <a href="{{ route('finance.index') }}" class="btn btn-secondary">Kembali</a>
             <button onclick="window.print()" class="btn btn-primary">Cetak</button>
        </div>
    </div>

    <!-- Kartu Summary Utama -->
    <div class="row mb-4">
        <!-- 1. Pendapatan Kotor -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pendapatan Kotor (Gross)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($grossRevenue, 0, ',', '.') }}</div>
                    <small class="text-muted">Total Uang Masuk Member</small>
                </div>
            </div>
        </div>

        <!-- 2. Total Beban Pengurus (Operasional + Komisi) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Beban Pengurus</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">-{{ number_format($totalExpenses + $commission, 0, ',', '.') }}</div>
                    <small class="text-muted">Opsional + Komisi 15%</small>
                </div>
            </div>
        </div>

        <!-- 3. Dana Wajib Setor (Net) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Sisa Wajib Setor (Net)</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($netBalance, 0, ',', '.') }}</div>
                    <small class="text-muted">Gross - Beban</small>
                </div>
            </div>
        </div>

        <!-- 4. Dana Pengambilan Barang (Stok) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pengambilan Barang</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">-{{ number_format($inventoryUsageValue, 0, ',', '.') }}</div>
                    <small class="text-muted">Nilai Barang Keluar</small>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL REKONSILIASI (VIEW PENGURUS) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white border-bottom">
            <h6 class="m-0 font-weight-bold text-primary">Rincian Alur Dana Pengurus</h6>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th style="width: 60%;">Uraian Transaksi</th>
                        <th class="text-end">Nominal (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- A. PENDAPATAN -->
                    <tr style="background-color: #d1e7dd;">
                        <td class="fw-bold">PENDAPATAN MEMBER</td>
                        <td class="text-end fw-bold">{{ number_format($grossRevenue, 0, ',', '.') }}</td>
                    </tr>

                    <!-- B. PENGELUARAN OPERASIONAL -->
                    <tr>
                        <td colspan="2" class="bg-light small fw-bold text-muted px-2">BIAYA OPERASIONAL (REALISASI)</td>
                    </tr>
                    @foreach($operationalExpenses as $expense)
                    <tr>
                        <td class="ps-4">
                            <i class="fa-solid fa-circle text-danger" style="font-size: 6px;"></i>
                            {{ $expense->category }} - {{ $expense->description }}
                        </td>
                        <td class="text-end text-danger">({{ number_format($expense->amount, 0, ',', '.') }})</td>
                    </tr>
                    @endforeach

                    <!-- C. POTONGAN KOMISI -->
                    <tr>
                        <td class="fw-bold text-primary">
                            <i class="fa-solid fa-scissors me-1"></i> POTONGAN KOMISI (15%)
                        </td>
                        <td class="text-end text-danger">({{ number_format($commission, 0, ',', '.') }})</td>
                    </tr>

                    <!-- D. SISA HASIL USAHA -->
                    <tr class="fw-bold bg-light">
                        <td>SISA HASIL USAHA (NET INCOME)</td>
                        <td class="text-end">{{ number_format($netIncome, 0, ',', '.') }}</td>
                    </tr>

                    <!-- E. PENGAMBILAN BARANG (DIPISAH SESUAI REQUEST) -->
                    <tr>
                        <td colspan="2" class="bg-warning bg-opacity-10 small fw-bold text-muted px-2">
                            <i class="fa-solid fa-box-open me-1"></i> PENGAMBILAN BARANG / INVENTARIS
                        </td>
                    </tr>
                    @foreach($inventoryItems as $item)
                    <tr>
                        <td class="ps-4">
                            <i class="fa-solid fa-box text-warning" style="font-size: 6px;"></i>
                            {{ $item->item_name }} ({{ $item->quantity }} unit)
                        </td>
                        <td class="text-end text-warning">({{ number_format($item->total_price, 0, ',', '.') }})</td>
                    </tr>
                    @endforeach

                    <!-- F. TOTAL WAJIB SETOR -->
                    <tr style="background-color: #cfe2ff; border-top: 2px solid #000;">
                        <td class="fw-bold fs-5">TOTAL WAJIB SETOR KE PUSAT</td>
                        <td class="text-end fw-bold fs-5">{{ number_format($netBalance, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
