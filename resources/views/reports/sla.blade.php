@extends('layouts.app')

@section('title', 'SLA Report')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">SLA Report</h4>
            <div class="text-muted small">Reporting Center</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.sla.pdf', ['from' => $from, 'to' => $to]) }}">PDF</a>
            <a class="btn btn-sm btn-outline-success" href="{{ route('reports.sla.excel', ['from' => $from, 'to' => $to]) }}">Excel</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET" action="{{ route('reports.sla') }}">
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
                <div class="col-12 col-lg-4"><div class="p-2 border rounded">Compliance: <b>{{ $summary['compliance_percent'] }}%</b></div></div>
                <div class="col-12 col-lg-4"><div class="p-2 border rounded">Breach: <b>{{ $summary['breach_percent'] }}%</b></div></div>
                <div class="col-12 col-lg-4"><div class="p-2 border rounded">Breaches: <b>{{ $summary['breaches'] }}</b></div></div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>No Tiket</th><th>Status</th><th>SLA</th><th>Dibuat</th></tr></thead>
                    <tbody>
                        @forelse($criticalTickets as $t)
                            <tr>
                                <td>{{ $t->ticket_number }}</td>
                                <td>{{ $t->status }}</td>
                                <td>{{ $t->sla_status }}</td>
                                <td>{{ $t->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

