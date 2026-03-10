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
                        <option value="">-- Semua Akun --</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" {{ (request('account_id')==$a->id)?'selected':'' }}>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-bold small text-muted">Sumber</label>
                    <select name="source" class="form-select form-select-lg">
                        <option value="">-- Semua Sumber --</option>
                        @foreach($availableSources as $sourceKey => $sourceLabel)
                            <option value="{{ $sourceKey }}" {{ (string) request('source') === (string) $sourceKey ? 'selected' : '' }}>{{ $sourceLabel }}</option>
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
            <div class="mb-2">
                <span class="badge bg-secondary">{{ $selected ? $selected->code.' - '.$selected->name : 'Semua Akun' }}</span>
                <span class="badge bg-info text-dark">{{ $source !== '' ? ($availableSources[$source] ?? $source) : 'Semua Sumber' }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle table-responsive-mobile">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>No. Jurnal</th><th>Sumber</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>
                        @php $balance = 0; @endphp
                        @forelse($entries as $e)
                        @php $balance += ($e->debit - $e->credit); @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($e->date)->format('Y-m-d') }}</td>
                            <td class="font-monospace">{{ $e->journal_no }}</td>
                            <td>
                                @if($e->source_type === 'finance_transaction')
                                    <span class="badge bg-primary-subtle text-primary">Finance</span>
                                @elseif($e->source_type === 'atk_transaction')
                                    <span class="badge bg-warning-subtle text-warning">ATK</span>
                                @elseif($e->source_type === 'wash_transaction')
                                    <span class="badge bg-success-subtle text-success">Wash</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($e->source_type ?? '-') }}</span>
                                @endif
                            </td>
                            <td>{{ $e->account_code }} - {{ $e->account_name }}</td>
                            <td class="text-end">{{ number_format($e->debit,0,',','.') }}</td>
                            <td class="text-end">{{ number_format($e->credit,0,',','.') }}</td>
                            <td>{{ $e->memo }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">Tidak ada transaksi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
