@extends('layouts.app')

@section('title', 'WhatsApp Report')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">WhatsApp Report</h4>
            <div class="text-muted small">Reporting Center</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.whatsapp.pdf', ['from' => $from, 'to' => $to]) }}">PDF</a>
            <a class="btn btn-sm btn-outline-success" href="{{ route('reports.whatsapp.excel', ['from' => $from, 'to' => $to]) }}">Excel</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET" action="{{ route('reports.whatsapp') }}">
                <div class="col-auto">
                    <input type="datetime-local" name="from" value="{{ \Carbon\Carbon::parse($from)->format('Y-m-d\\TH:i') }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <input type="datetime-local" name="to" value="{{ \Carbon\Carbon::parse($to)->format('Y-m-d\\TH:i') }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary" type="submit">Tampilkan</button>
                </div>
            </form>

            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-4"><div class="p-2 border rounded">Incoming: <b>{{ $summary['incoming'] }}</b></div></div>
                <div class="col-12 col-lg-4"><div class="p-2 border rounded">Outgoing: <b>{{ $summary['outgoing'] }}</b></div></div>
                <div class="col-12 col-lg-4"><div class="p-2 border rounded">AI Usage: <b>{{ $summary['ai_usage'] }}</b></div></div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Intent</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($topIntents as $row)
                            <tr><td>{{ $row['intent'] }}</td><td class="text-end">{{ $row['total'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

