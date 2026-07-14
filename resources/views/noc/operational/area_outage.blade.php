@extends('layouts.app')

@section('title', 'Area Outage')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Area Outage</h4>
            <div class="text-muted small">Operasional NOC</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Gangguan</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Mulai</th>
                            <th>Estimasi Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->type }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->started_at?->format('d M Y H:i') }}</td>
                                <td>{{ $item->estimated_finish_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Belum ada data.</td></tr>
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

