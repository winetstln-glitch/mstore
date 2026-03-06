@extends('layouts.app')
@section('title', 'Buku Besar')
@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Buku Besar</h5>
            </div>
            <form method="get" class="row g-3 align-items-end mb-4">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-muted">Akun</label>
                    <select name="account_id" class="form-select form-select-lg">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" {{ (request('account_id')==$a->id)?'selected':'' }}>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                        <a class="btn btn-outline-danger btn-lg w-100 w-md-auto" href="{{ route('accounting.ledger.pdf', request()->all()) }}">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </a>
                        <a class="btn btn-outline-success btn-lg w-100 w-md-auto" href="{{ route('accounting.ledger.excel', request()->all()) }}">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </a>
                    </div>
                </div>
            </form>
            @if($selected)
            <div class="mb-2">
                <span class="badge bg-secondary">{{ $selected->code }} - {{ $selected->name }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle table-responsive-mobile">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>No. Jurnal</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>
                        @php $balance = 0; @endphp
                        @forelse($entries as $e)
                        @php $balance += ($e->debit - $e->credit); @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($e->date)->format('Y-m-d') }}</td>
                            <td class="font-monospace">{{ $e->journal_no }}</td>
                            <td class="text-end">{{ number_format($e->debit,0,',','.') }}</td>
                            <td class="text-end">{{ number_format($e->credit,0,',','.') }}</td>
                            <td>{{ $e->memo }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada transaksi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
