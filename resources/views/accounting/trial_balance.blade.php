@extends('layouts.app')
@section('title', 'Neraca Saldo')
@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Neraca Saldo</h5>
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
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('accounting.trial_balance.pdf', request()->all()) }}">Export PDF</a>
                        <a class="btn btn-outline-success btn-sm" href="{{ route('accounting.trial_balance.excel', request()->all()) }}">Export Excel</a>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">Kode</th>
                            <th>Nama Akun</th>
                            <th style="width: 15%;" class="text-end">Debit</th>
                            <th style="width: 15%;" class="text-end">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                        <tr>
                            <td class="font-monospace">{{ $r->code }}</td>
                            <td>{{ $r->name }}</td>
                            <td class="text-end">{{ number_format($r->debit,0,',','.') }}</td>
                            <td class="text-end">{{ number_format($r->credit,0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3">Belum ada jurnal</td></tr>
                        @endforelse
                        <tr class="table-secondary fw-bold">
                            <td colspan="2" class="text-end">Total</td>
                            <td class="text-end">{{ number_format($totalDebit,0,',','.') }}</td>
                            <td class="text-end">{{ number_format($totalCredit,0,',','.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="alert alert-info mt-3">
        Total Debit harus sama dengan Total Kredit. Jika tidak seimbang, ada jurnal yang tidak balanced.
    </div>
    <div class="mt-2">
        <a href="{{ route('wash.reports.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Laporan Wash</a>
        <a href="{{ route('atk.reports.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Laporan ATK</a>
    </div>
    </div>
</div>
@endsection
