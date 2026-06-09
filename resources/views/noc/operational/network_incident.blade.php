@extends('layouts.app')

@section('title', 'Network Incident')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Network Incident</h4>
            <div class="text-muted small">Operasional NOC</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Incident</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Detected</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->type }}</td>
                                <td>{{ $item->severity }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->detected_at?->format('d M Y H:i') }}</td>
                                <td>{{ $item->resolved_at?->format('d M Y H:i') ?? '-' }}</td>
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

