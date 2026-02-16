@extends('layouts.app')
@section('title', 'Laporan ATK')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Laporan Pemasukan & Pengeluaran - ATK</h5>
        <div class="btn-group">
            <a class="btn btn-sm btn-outline-secondary" id="btnExportPdfAtk">Export PDF</a>
            <a class="btn btn-sm btn-outline-success" id="btnExportExcelAtk">Export Excel</a>
        </div>
    </div>

    <div class="row g-3 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label">Tanggal</label>
            <form method="get">
                <div class="input-group">
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                    <button class="btn btn-outline-primary">Terapkan</button>
                </div>
            </form>
        </div>
        <div class="col-auto">
            <label class="form-label">Bulan</label>
            <form method="get">
                <div class="input-group">
                    <input type="month" name="month" value="{{ $month }}" class="form-control">
                    <button class="btn btn-outline-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Ringkasan Harian ({{ $date }})</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>Pemasukan</div><div>Rp {{ number_format($dailyIncome,0,',','.') }}</div>
                    </div>
                    <div class="d-flex justify-content-between">
                    <div>Pengeluaran</div><div>Rp {{ number_format($dailyExpense,0,',','.') }}</div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <div>Laba Kotor</div><div>Rp {{ number_format($dailyIncome - $dailyExpense,0,',','.') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Ringkasan Bulanan ({{ $month }})</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>Pemasukan</div><div>Rp {{ number_format($monthlyIncome,0,',','.') }}</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Pengeluaran</div><div>Rp {{ number_format($monthlyExpense,0,',','.') }}</div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <div>Laba Kotor</div><div>Rp {{ number_format($monthlyIncome - $monthlyExpense,0,',','.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Rincian Pemasukan Harian</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Waktu</th><th>No Trx</th><th>Metode</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach($dailyIncomeRows as $r)
                            <tr>
                                <td>{{ $r->created_at->format('H:i') }}</td>
                                <td>{{ $r->transaction_number }}</td>
                                <td>{{ strtoupper($r->payment_method) }}</td>
                                <td class="text-end">Rp {{ number_format($r->total_amount,0,',','.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Rincian Pengeluaran Harian</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Tanggal</th><th>Deskripsi</th><th class="text-end">Nominal</th></tr></thead>
                        <tbody>
                            @foreach($dailyExpenseRows as $r)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                                <td>{{ $r->description }}</td>
                                <td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Harian per Metode Bayar</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Metode</th><th class="text-end">Nominal</th></tr></thead>
                        <tbody>
                            @foreach($dailyByPayment as $r)
                            <tr><td>{{ strtoupper($r->payment_method) }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Bulanan per Metode Bayar</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Metode</th><th class="text-end">Nominal</th></tr></thead>
                        <tbody>
                            @foreach($monthlyByPayment as $r)
                            <tr><td>{{ strtoupper($r->payment_method) }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
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
    })();
</script>
@endsection
