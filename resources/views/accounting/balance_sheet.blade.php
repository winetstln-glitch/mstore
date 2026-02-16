@extends('layouts.app')
@section('title', 'Neraca')
@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Neraca</h5>
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
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('accounting.balance_sheet.pdf', request()->all()) }}">Export PDF</a>
                        <a class="btn btn-outline-success btn-sm" href="{{ route('accounting.balance_sheet.excel', request()->all()) }}">Export Excel</a>
                    </div>
                </div>
            </form>
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Aset</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light"><tr><th>Kode</th><th>Nama Akun</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse($assets as $a)
                                <tr>
                                    <td class="font-monospace">{{ $a->code }}</td>
                                    <td>{{ $a->name }}</td>
                                    <td class="text-end">{{ number_format($a->amount,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada</td></tr>
                                @endforelse
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">Total Aset</td>
                                    <td class="text-end">{{ number_format($totalAssets,0,',','.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Kewajiban</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light"><tr><th>Kode</th><th>Nama Akun</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse($liabilities as $l)
                                <tr>
                                    <td class="font-monospace">{{ $l->code }}</td>
                                    <td>{{ $l->name }}</td>
                                    <td class="text-end">{{ number_format($l->amount,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada</td></tr>
                                @endforelse
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">Total Kewajiban</td>
                                    <td class="text-end">{{ number_format($totalLiabilities,0,',','.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <h6 class="fw-semibold mt-4">Ekuitas</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light"><tr><th>Kode</th><th>Nama Akun</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse($equity as $e)
                                <tr>
                                    <td class="font-monospace">{{ $e->code }}</td>
                                    <td>{{ $e->name }}</td>
                                    <td class="text-end">{{ number_format($e->amount,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada</td></tr>
                                @endforelse
                                <tr>
                                    <td class="font-monospace">3201</td>
                                    <td>Laba Berjalan</td>
                                    <td class="text-end">{{ number_format($netIncome,0,',','.') }}</td>
                                </tr>
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">Total Ekuitas</td>
                                    <td class="text-end">{{ number_format($totalEquity,0,',','.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3 alert alert-info d-flex justify-content-between">
                <span>Total Aset</span>
                <span class="fw-bold">{{ number_format($totalAssets,0,',','.') }}</span>
            </div>
            <div class="alert alert-info d-flex justify-content-between">
                <span>Total Kewajiban + Ekuitas</span>
                <span class="fw-bold">{{ number_format($rhs,0,',','.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
