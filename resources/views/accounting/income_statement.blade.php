@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Laporan Laba Rugi</h5>
            </div>
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
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('accounting.income_statement.pdf', request()->all()) }}">Export PDF</a>
                        <a class="btn btn-outline-success btn-sm" href="{{ route('accounting.income_statement.excel', request()->all()) }}">Export Excel</a>
                    </div>
                </div>
            </form>
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Pendapatan</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr><th>Kode</th><th>Nama Akun</th><th class="text-end">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                @forelse($revenues as $r)
                                <tr>
                                    <td class="font-monospace">{{ $r->code }}</td>
                                    <td>{{ $r->name }}</td>
                                    <td class="text-end">{{ number_format($r->amount,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada</td></tr>
                                @endforelse
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">Total Pendapatan</td>
                                    <td class="text-end">{{ number_format($totalRevenue,0,',','.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Beban</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr><th>Kode</th><th>Nama Akun</th><th class="text-end">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $e)
                                <tr>
                                    <td class="font-monospace">{{ $e->code }}</td>
                                    <td>{{ $e->name }}</td>
                                    <td class="text-end">{{ number_format($e->amount,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada</td></tr>
                                @endforelse
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">Total Beban</td>
                                    <td class="text-end">{{ number_format($totalExpense,0,',','.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <div class="alert alert-info d-flex justify-content-between">
                    <span>Laba Bersih</span>
                    <span class="fw-bold">{{ number_format($netIncome,0,',','.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
