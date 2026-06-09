@extends('layouts.app')

@section('title', 'NOC Report')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">NOC Report</h4>
            <div class="text-muted small">Reporting Center</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.noc.pdf', ['date' => $date]) }}">PDF</a>
            <a class="btn btn-sm btn-outline-success" href="{{ route('reports.noc.excel', ['date' => $date]) }}">Excel</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET" action="{{ route('reports.noc') }}">
                <div class="col-auto">
                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary" type="submit">Tampilkan</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Captured</th>
                            <th>Health</th>
                            <th>ONU Offline</th>
                            <th>LOS</th>
                            <th>PPPoE Active</th>
                            <th>Outage Active</th>
                            <th>Ticket Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $s)
                            <tr>
                                <td>{{ $s->captured_at?->format('H:i:s') }}</td>
                                <td>{{ $s->network_health_score }}</td>
                                <td>{{ $s->onu_offline }}</td>
                                <td>{{ $s->onu_los }}</td>
                                <td>{{ $s->pppoe_active_sessions }}</td>
                                <td>{{ $s->outage_active }}</td>
                                <td>{{ $s->ticket_open }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">Tidak ada snapshot pada tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

