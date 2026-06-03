@extends('layouts.app')

@section('title', 'WhatsApp Logs')

@section('content')
<div class="card shadow-sm">

    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">
            <i class="fa-brands fa-whatsapp me-2"></i>
            WhatsApp Activity Logs
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Pengaturan
            </a>
            <a href="{{ route('whatsapp.builder.index') }}" class="btn btn-success btn-sm">
                <i class="fa-solid fa-robot me-1"></i> Bot Builder
            </a>
        </div>
    </div>

    <div class="card-body">

        {{-- Filters --}}
        <div class="d-flex flex-wrap gap-2 mb-4">
            <form method="GET" action="{{ route('whatsapp.logs') }}" class="d-flex flex-wrap gap-2 flex-grow-1">
                <div class="input-group">
                    <span class="input-group-text">Tipe</span>
                    <select class="form-select" name="type" onchange="this.form.submit()">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="incoming" {{ $type === 'incoming' ? 'selected' : '' }}>Pesan Masuk</option>
                        <option value="outgoing" {{ $type === 'outgoing' ? 'selected' : '' }}>Pesan Keluar</option>
                    </select>
                </div>
                <div class="input-group">
                    <span class="input-group-text">Status</span>
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Terkirim</option>
                        <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Terkirim</option>
                        <option value="read" {{ $status === 'read' ? 'selected' : '' }}>Dibaca</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Gagal</option>
                    </select>
                </div>
            </form>
        </div>

        {{-- Logs Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Waktu</th>
                        <th scope="col">Tipe</th>
                        <th scope="col">No. WhatsApp</th>
                        <th scope="col">Pesan</th>
                        <th scope="col">Status</th>
                        <th scope="col">Provider ID</th>
                        <th scope="col">Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>
                            @if($log->type === 'incoming')
                                <span class="badge bg-info text-dark">
                                    <i class="fa-solid fa-arrow-down me-1"></i> Masuk
                                </span>
                            @else
                                <span class="badge bg-primary">
                                    <i class="fa-solid fa-arrow-up me-1"></i> Keluar
                                </span>
                            @endif
                        </td>
                        <td>
                            <code class="font-monospace">{{ $log->phone_number }}</code>
                        </td>
                        <td style="max-width: 350px;">
                            <div class="text-truncate" title="{{ e($log->message) }}">
                                {{ e(Str::limit($log->message, 80)) }}
                            </div>
                        </td>
                        <td>
                            @php
                                $statusBadges = [
                                    'pending' => ['bg-warning', 'Pending'],
                                    'sent' => ['bg-success', 'Terkirim'],
                                    'delivered' => ['bg-info', 'Terima'],
                                    'read' => ['bg-dark', 'Dibaca'],
                                    'failed' => ['bg-danger', 'Gagal'],
                                ];
                                [$badge, $text] = $statusBadges[$log->status] ?? ['bg-secondary', $log->status];
                            @endphp
                            <span class="badge {{ $badge }}">{{ $text }}</span>
                        </td>
                        <td>{{ $log->provider_message_id ?? '-' }}</td>
                        <td style="max-width: 200px;">
                            @if($log->error_message)
                                <span class="text-danger" title="{{ e($log->error_message) }}">
                                    {{ e(Str::limit($log->error_message, 40)) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fa-solid fa-inbox text-muted mb-2 d-block" style="font-size: 2.5rem;"></i>
                            <p class="mb-0 text-muted">Tidak ada log aktivitas WhatsApp ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
