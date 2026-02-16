@extends('layouts.app')
@section('title', 'Laporan Arus Kas')
@section('content')
<div class="container-fluid py-3">
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Laporan Arus Kas (Metode Langsung)</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end mb-3">
                <div class="col-auto">
                    <label class="form-label">Dari</label>
                    <input type="date" name="start_date" value="{{ $start }}" class="form-control">
                </div>
                <div class="col-auto">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="end_date" value="{{ $end }}" class="form-control">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Terapkan</button>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-group">
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('accounting.cash_flow.pdf', request()->all()) }}">Export PDF</a>
                        <a class="btn btn-outline-success btn-sm" href="{{ route('accounting.cash_flow.excel', request()->all()) }}">Export Excel</a>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tbody>
                        <tr class="table-light"><th colspan="2">Arus Kas dari Aktivitas Operasi</th></tr>
                        <tr><td>Penerimaan dari pelanggan</td><td class="text-end">{{ number_format($operatingIn,0,',','.') }}</td></tr>
                        <tr><td>Pembayaran beban</td><td class="text-end">({{ number_format($operatingOut,0,',','.') }})</td></tr>
                        <tr class="fw-bold"><td>Netto Operasi</td><td class="text-end">{{ number_format($netOperating,0,',','.') }}</td></tr>
                        <tr class="table-light"><th colspan="2">Arus Kas dari Aktivitas Investasi</th></tr>
                        <tr><td>Penerimaan penjualan aset</td><td class="text-end">{{ number_format($investingIn,0,',','.') }}</td></tr>
                        <tr><td>Pembelian aset</td><td class="text-end">({{ number_format($investingOut,0,',','.') }})</td></tr>
                        <tr class="fw-bold"><td>Netto Investasi</td><td class="text-end">{{ number_format($netInvesting,0,',','.') }}</td></tr>
                        <tr class="table-light"><th colspan="2">Arus Kas dari Aktivitas Pendanaan</th></tr>
                        <tr><td>Penerimaan pendanaan</td><td class="text-end">{{ number_format($financingIn,0,',','.') }}</td></tr>
                        <tr><td>Pengembalian/Distribusi</td><td class="text-end">({{ number_format($financingOut,0,',','.') }})</td></tr>
                        <tr class="fw-bold"><td>Netto Pendanaan</td><td class="text-end">{{ number_format($netFinancing,0,',','.') }}</td></tr>
                        <tr class="table-secondary fw-bold"><td>Kenaikan (Penurunan) Kas</td><td class="text-end">{{ number_format($netChange,0,',','.') }}</td></tr>
                        @if(!is_null($openingCash))
                        <tr><td>Saldo Awal Kas</td><td class="text-end">{{ number_format($openingCash,0,',','.') }}</td></tr>
                        @endif
                        <tr><td>Saldo Akhir Kas</td><td class="text-end">{{ number_format($closingCash,0,',','.') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
