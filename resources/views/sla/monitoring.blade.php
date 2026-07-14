@extends('layouts.app')

@section('title', 'SLA Monitoring')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">SLA Monitoring</h4>
            <div class="text-muted small">Tiket & Gangguan</div>
        </div>
        <a href="{{ route('sla.escalation-queue') }}" class="btn btn-sm btn-outline-primary">Escalation Queue</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">SLA Compliance</div><div class="h4 mb-0">{{ $summary['sla_compliance_percent'] ?? 0 }}%</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">SLA Breach</div><div class="h4 mb-0">{{ $summary['sla_breach_percent'] ?? 0 }}%</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Rata-rata Penyelesaian</div><div class="h4 mb-0">{{ (int) ($summary['avg_resolution_minutes'] ?? 0) }} m</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Tiket Kritis (Open)</div><div class="h4 mb-0">{{ (int) ($summary['critical_open_tickets'] ?? 0) }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header">Tiket Melewati SLA</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>SLA</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->ticket_number }}</td>
                                <td>{{ $ticket->customer?->name ?? '-' }}</td>
                                <td>{{ $ticket->status }}</td>
                                <td>{{ $ticket->sla_status ?? '-' }}</td>
                                <td>{{ $ticket->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Belum ada tiket melewati SLA.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

