@extends('layouts.app')
@section('title', 'Laporan ATK')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Header Judul & Export -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Laporan Keuangan ATK</h5>
                <div class="btn-group">
                    <a class="btn btn-sm btn-outline-secondary" id="btnExportPdfAtk">Export PDF</a>
                    <a class="btn btn-sm btn-outline-success" id="btnExportExcelAtk">Export Excel</a>
                </div>
            </div>

            <!-- Navigasi Tab -->
            <ul class="nav nav-tabs" id="atkReportTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-content" type="button">Laporan Harian</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly-content" type="button">Laporan Bulanan</button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Konten Laporan -->
    <div class="tab-content" id="atkReportTabContent">
        
        <!-- ======================= -->
        <!-- TAB HARIAN -->
        <!-- ======================= -->
        <div class="tab-pane fade show active" id="daily-content">
            <!-- Filter Tanggal -->
            <form method="get" class="row g-2 align-items-center mb-3 justify-content-end">
                <input type="hidden" name="view" value="daily">
                <div class="col-auto"><label class="form-label mb-0 fw-bold">Tanggal:</label></div>
                <div class="col-auto"><input type="date" name="date" value="{{ $date }}" class="form-control"></div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">Tampilkan</button></div>
            </form>

            <!-- Ringkasan Harian (Full Width) -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table mb-0 text-center">
                    <thead class="table">
                        <tr>
                            <th style="width: 33%;">Total Pemasukan</th>
                            <th style="width: 33%;">Total Pengeluaran</th>
                            <th style="width: 34%;">Laba / Rugi Kotor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="align-middle fw-bold" style="font-size: 1.1rem;">
                            <td class="text-success">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                            <td class="text-danger">Rp {{ number_format($dailyExpense,0,',','.') }}</td>
                            <td>Rp {{ number_format($dailyIncome - $dailyExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel Pemasukan (Full Width) -->
            <h6 class="fw-bold mt-3 text-decoration-underline">A. Rincian Pemasukan</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">Waktu</th>
                            <th style="width: 20%;">No. Transaksi</th>
                            <th style="width: 25%;">Metode Pembayaran</th>
                            <th class="text-end" style="width: 35%;">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyIncomeRows as $index => $r)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $r->created_at->format('H:i') }}</td>
                            <td class="font-monospace">{{ $r->transaction_number }}</td>
                            <td><span class="badge bg-secondary">{{ strtoupper($r->payment_method) }}</span></td>
                            <td class="text-end">{{ number_format($r->total_amount,0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3">Tidak ada data pemasukan</td></tr>
                        @endforelse
                        
                        <!-- Total Footer -->
                        <tr class="table fw-bold">
                            <td colspan="4" class="text-end">Total Pemasukan:</td>
                            <td class="text-end">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel Pengeluaran (Full Width) -->
            <h6 class="fw-bold mt-3 text-decoration-underline">B. Rincian Pengeluaran</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 20%;">Tanggal</th>
                            <th style="width: 40%;">Deskripsi</th>
                            <th class="text-end" style="width: 35%;">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyExpenseRows as $index => $r)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                            <td>{{ $r->description }}</td>
                            <td class="text-end text-danger">{{ number_format($r->amount,0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3">Tidak ada data pengeluaran</td></tr>
                        @endforelse
                        
                        <!-- Total Footer -->
                        <tr class="table fw-bold">
                            <td colspan="3" class="text-end">Total Pengeluaran:</td>
                            <td class="text-end">Rp {{ number_format($dailyExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Analisis Metode Pembayaran Harian -->
            <div class="row justify-content-end">
                <div class="col-md-6">
                     <h6 class="fw-bold text-decoration-underline">C. Analisis Per Metode Bayar (Harian)</h6>
                     <table class="table table-bordered table-sm">
                         <thead><tr><th>Metode</th><th class="text-end">Total</th></tr></thead>
                         <tbody>
                            @forelse($dailyByPayment as $r)
                            <tr><td>{{ strtoupper($r->payment_method) }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center">-</td></tr>
                            @endforelse
                         </tbody>
                     </table>
                </div>
            </div>

        </div> <!-- End Daily Tab -->

        <!-- ======================= -->
        <!-- TAB BULANAN -->
        <!-- ======================= -->
        <div class="tab-pane fade" id="monthly-content">
            <!-- Filter Bulan -->
            <form method="get" class="row g-2 align-items-center mb-3 justify-content-end">
                <input type="hidden" name="view" value="monthly">
                <div class="col-auto"><label class="form-label mb-0 fw-bold">Bulan:</label></div>
                <div class="col-auto"><input type="month" name="month" value="{{ $month }}" class="form-control"></div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">Tampilkan</button></div>
            </form>

            <!-- Ringkasan Bulanan -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table mb-0 text-center">
                    <thead class="table-secondary">
                        <tr>
                            <th style="width: 33%;">Pemasukan Bulanan</th>
                            <th style="width: 33%;">Pengeluaran Bulanan</th>
                            <th style="width: 34%;">Laba Bersih Bulanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="align-middle fw-bold">
                            <td class="text-success">Rp {{ number_format($monthlyIncome,0,',','.') }}</td>
                            <td class="text-danger">Rp {{ number_format($monthlyExpense,0,',','.') }}</td>
                            <td>Rp {{ number_format($monthlyIncome - $monthlyExpense,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel Analisis Bulanan -->
            <div class="row">
                <div class="col-12 mb-4">
                    <h6 class="fw-bold text-decoration-underline">Rekap Harian ({{ $month }})</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table">
                                <tr>
                                    <th style="width: 20%;">Tanggal</th>
                                    <th class="text-end" style="width: 26%;">Pemasukan</th>
                                    <th class="text-end" style="width: 26%;">Pengeluaran</th>
                                    <th class="text-end" style="width: 28%;">Laba / Rugi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $incomeMap = collect($monthlyDailyIncome)->keyBy('d');
                                    $expenseMap = collect($monthlyDailyExpense)->keyBy('d');
                                    $days = collect($incomeMap->keys())->merge($expenseMap->keys())->unique()->sort();
                                @endphp
                                @forelse($days as $d)
                                @php
                                    $inc = (float)($incomeMap[$d]->total ?? 0);
                                    $exp = (float)($expenseMap[$d]->total ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $d }}</td>
                                    <td class="text-end">Rp {{ number_format($inc,0,',','.') }}</td>
                                    <td class="text-end">Rp {{ number_format($exp,0,',','.') }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($inc - $exp,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="fw-bold text-decoration-underline">Analisis Per Metode Bayar (Bulanan)</h6>
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Metode</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse($monthlyByPayment as $r)
                            <tr><td>{{ strtoupper($r->payment_method) }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Jika ada data bulanan lain (misal per kategori ATK) bisa ditambahkan disini -->
            </div>

        </div> <!-- End Monthly Tab -->

    </div>
</div>

<!-- Script Export Logic (Preserved) -->
<script>
    (function() {
        function q(name) { return new URLSearchParams(window.location.search).get(name); }
        var pdf = document.getElementById('btnExportPdfAtk');
        var xls = document.getElementById('btnExportExcelAtk');
        if (pdf) {
            var params = new URLSearchParams();
            if (q('date')) params.set('date', q('date'));
            if (q('month')) params.set('month', q('month'));
            pdf.href = '{{ route('atk.reports.pdf') }}' + (params.toString() ? ('?' + params.toString()) : '');
        }
        if (xls) {
            var params2 = new URLSearchParams();
            if (q('date')) params2.set('date', q('date'));
            if (q('month')) params2.set('month', q('month'));
            xls.href = '{{ route('atk.reports.excel') }}' + (params2.toString() ? ('?' + params2.toString()) : '');
        }
        
        // Script Auto Switch Tab berdasarkan parameter URL
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('month') && !urlParams.has('date')) {
                new bootstrap.Tab(document.querySelector('#monthly-tab')).show();
            }
        });
    })();
</script>
@endsection
