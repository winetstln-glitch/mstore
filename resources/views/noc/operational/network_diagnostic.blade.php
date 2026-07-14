@extends('layouts.app')

@section('title', 'Network Diagnostic')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Network Diagnostic</h4>
            <div class="text-muted small">Operasional NOC</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Diagnostic</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Diagnosis</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Ticket</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>#{{ $item->customer_id }}</td>
                                <td>{{ $item->diagnosis_key }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->priority }}</td>
                                <td>{{ $item->ticket_id ? $item->ticket_id : '-' }}</td>
                                <td>{{ $item->completed_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection

