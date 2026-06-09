@extends('layouts.app')

@section('title', 'Escalation Queue')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Escalation Queue</h4>
            <div class="text-muted small">Tiket & Gangguan</div>
        </div>
        <a href="{{ route('sla.monitoring') }}" class="btn btn-sm btn-outline-primary">SLA Monitoring</a>
    </div>

    <div class="card">
        <div class="card-header">Tiket Perlu Eskalasi</div>
        <div class="card-body">
            <div class="text-muted small mb-2">Kriteria: melewati SLA, belum ditugaskan, atau tidak ada update (>= 12 jam).</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>SLA</th>
                            <th>Teknisi</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->ticket_number }}</td>
                                <td>{{ $ticket->customer?->name ?? '-' }}</td>
                                <td>{{ $ticket->status }}</td>
                                <td>{{ $ticket->sla_status ?? '-' }}</td>
                                <td>{{ $ticket->technician_id ? ('#'.$ticket->technician_id) : '-' }}</td>
                                <td>{{ $ticket->updated_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Tidak ada tiket dalam escalation queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection

