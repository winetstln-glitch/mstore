@extends('layouts.app')
@section('title', 'Neraca Saldo')
@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Neraca Saldo</h5>
            </div>
            <form method="get" class="row g-3 align-items-end mb-4">
                <div class="col-12 col-md-auto">
                    <label class="form-label fw-bold small text-muted">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $start }}" class="form-control form-control-lg">
                </div>
                <div class="col-12 col-md-auto">
                    <label class="form-label fw-bold small text-muted">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $end }}" class="form-control form-control-lg">
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-primary btn-lg w-100 w-md-auto">
                        <i class="fas fa-filter me-1"></i> Terapkan
                    </button>
                </div>
                <div class="col-12 col-md-auto ms-md-auto">
                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a class="btn btn-outline-danger btn-lg w-100 w-md-auto" href="{{ route('accounting.trial_balance.pdf', request()->all()) }}">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </a>
                        <a class="btn btn-outline-success btn-lg w-100 w-md-auto" href="{{ route('accounting.trial_balance.excel', request()->all()) }}">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </a>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle table-responsive-mobile">
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
        <a href="{{ route('wash.reports.index') }}" class="btn btn-outline-secondary">Kembali ke Laporan Wash</a>
        <a href="{{ route('atk.reports.index') }}" class="btn btn-outline-secondary">Kembali ke Laporan ATK</a>
    </div>
    </div>
</div>
@endsection
