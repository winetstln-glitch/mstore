@extends('layouts.app')

@section('title', 'Diagnostic Logs')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Diagnostic Logs</h4>
            <div class="text-muted small">Operasional NOC</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Log Diagnostic</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Created</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->created_at?->format('d M Y H:i') }}</td>
                                <td>#{{ $item->customer_id }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ \Illuminate\Support\Str::limit((string) $item->summary, 120) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Belum ada data.</td></tr>
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

