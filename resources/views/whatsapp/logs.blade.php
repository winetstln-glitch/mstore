@extends('layouts.app')

@section('title', 'WhatsApp Logs')

@section('content')
<div class="card shadow-sm h-100" style="min-height: 80vh;">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">
            <i class="fa-brands fa-whatsapp me-2"></i>
            @if($selectedPhone)
                Percakapan dengan <code>{{ $selectedPhone }}</code>
            @else
                WhatsApp Activity Logs
            @endif
        </h5>
        <div class="d-flex gap-2">
            @if($selectedPhone)
                <a href="{{ route('whatsapp.logs') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            @endif
            <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Pengaturan
            </a>
        </div>
    </div>

    <div class="card-body p-0 d-flex flex-column flex-grow-1" style="max-height: 75vh;">
        <!-- Filters & Sidebar -->
        @if(!$selectedPhone)
            <div class="p-3 border-bottom">
                <form method="GET" action="{{ route('whatsapp.logs') }}" class="d-flex flex-wrap gap-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Tipe</span>
                        <select class="form-select" name="type" onchange="this.form.submit()">
                            <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="incoming" {{ $type === 'incoming' ? 'selected' : '' }}>Pesan Masuk</option>
                            <option value="outgoing" {{ $type === 'outgoing' ? 'selected' : '' }}>Pesan Keluar</option>
                        </select>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Status</span>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Terkirim</option>
                            <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Terima</option>
                            <option value="read" {{ $status === 'read' ? 'selected' : '' }}>Dibaca</option>
                            <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Gagal</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- Phone List Sidebar -->
            <div class="d-flex flex-grow-1">
                <div class="border-end" style="width: 300px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        @forelse($uniquePhones as $phone)
                            <a href="{{ route('whatsapp.logs', array_merge(request()->all(), ['phone' => $phone])) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">
                                        <i class="fa-solid fa-user-circle me-2"></i>
                                        {{ $phone }}
                                    </div>
                                    @php
                                        $lastLog = WhatsAppLog::where('phone_number', $phone)->latest()->first();
                                    @endphp
                                    @if($lastLog)
                                        <small class="text-muted">
                                            {{ Str::limit($lastLog->message, 30) }}
                                        </small>
                                    @endif
                                </div>
                                @if($lastLog)
                                    <small class="text-muted">
                                        {{ $lastLog->created_at->diffForHumans() }}
                                    </small>
                                @endif
                            </a>
                        @empty
                            <div class="list-group-item text-center py-4 text-muted">
                                <i class="fa-solid fa-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                Belum ada percakapan
                            </div>
                        @endforelse
                    </div>
                </div>
                
                <!-- Table View -->
                <div class="flex-grow-1 overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th scope="col">Waktu</th>
                                    <th scope="col">Tipe</th>
                                    <th scope="col">No. WhatsApp</th>
                                    <th scope="col">Pesan</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Response Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td style="white-space: nowrap;">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td>
                                        @if($log->type === 'incoming')
                                            <span class="badge bg-info text-dark">
                                                <i class="fa-solid fa-arrow-down me-1"></i> Customer
                                            </span>
                                        @else
                                            <span class="badge {{ $log->sender_type === 'cs' ? 'bg-success' : 'bg-primary' }}">
                                                <i class="fa-solid fa-arrow-up me-1"></i> 
                                                {{ $log->sender_type === 'cs' ? 'CS' : 'Bot' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('whatsapp.logs', array_merge(request()->all(), ['phone' => $log->phone_number])) }}" 
                                           class="font-monospace text-decoration-none">
                                            {{ $log->phone_masked }}
                                        </a>
                                    </td>
                                    <td style="max-width: 400px;">
                                        <div class="text-truncate" title="{{ e($log->message) }}">
                                            {{ e($log->message) }}
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
                                    <td>
                                        @if($log->processing_time_ms)
                                            <small class="text-muted">
                                                {{ $log->processing_time_ms }} ms
                                            </small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fa-solid fa-inbox text-muted mb-2 d-block" style="font-size: 2.5rem;"></i>
                                        <p class="mb-0 text-muted">Tidak ada log aktivitas WhatsApp ditemukan.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="p-3 border-top">
                    {{ $logs->links() }}
                </div>
            @endif
            
        @else
            <!-- Conversation View -->
            <div class="d-flex flex-column flex-grow-1" style="background: #e5ddd5;">
                <!-- Chat Header -->
                <div class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">
                            <i class="fa-solid fa-user-circle me-2"></i>
                            {{ $selectedPhone }}
                        </h6>
                        <small class="text-muted">
                            {{ $conversation ? count($conversation) . ' pesan' : '' }}
                        </small>
                    </div>
                </div>
                
                <!-- Chat Messages -->
                <div class="flex-grow-1 overflow-auto p-3" style="background: url('https://user-images.githubusercontent.com/15075759/28192123-0c855076-6853-11e7-82fb-3e2e5991f171.png'); background-size: contain;">
                    @forelse($conversation as $log)
                        <div class="d-flex {{ $log->type === 'incoming' ? '' : 'justify-content-end' }} mb-3">
                            <div class="rounded-4 shadow-sm p-3 max-w-75" 
                                 style="max-width: 75%; 
                                        {{ $log->type === 'incoming' ? 'background: #ffffff;' : 'background: #d9fdd3;' }}
                                        {{ $log->status === 'failed' ? 'border: 1px solid #dc3545;' : '' }}">
                                
                                <!-- Sender Info & Time -->
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold {{ $log->type === 'incoming' ? 'text-info' : ($log->sender_type === 'cs' ? 'text-success' : 'text-primary') }}">
                                        @if($log->type === 'incoming')
                                            <i class="fa-solid fa-user me-1"></i> Customer
                                        @else
                                            @if($log->sender_type === 'cs')
                                                <i class="fa-solid fa-headset me-1"></i> CS
                                            @else
                                                <i class="fa-solid fa-robot me-1"></i> Bot
                                            @endif
                                        @endif
                                    </small>
                                    <small class="text-muted ms-2">
                                        {{ $log->created_at->format('H:i') }}
                                    </small>
                                </div>
                                
                                <!-- Message -->
                                <p class="mb-1 text-break" style="white-space: pre-wrap;">
                                    {{ e($log->message) }}
                                </p>
                                
                                <!-- Status & Metadata -->
                                <div class="d-flex justify-content-between align-items-center mt-1 gap-2">
                                    <div>
                                        @if($log->processing_time_ms)
                                            <small class="text-muted" title="Waktu Respon">
                                                <i class="fa-solid fa-clock me-1"></i>{{ $log->processing_time_ms }} ms
                                            </small>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($log->status === 'failed')
                                            <span class="badge bg-danger text-white" title="Error: {{ e($log->error_message) }}">
                                                <i class="fa-solid fa-exclamation-triangle me-1"></i> Gagal
                                            </span>
                                        @elseif($log->status === 'read')
                                            <span class="text-primary">
                                                <i class="fa-solid fa-check-double"></i>
                                            </span>
                                        @elseif($log->status === 'delivered')
                                            <span class="text-muted">
                                                <i class="fa-solid fa-check-double"></i>
                                            </span>
                                        @elseif($log->status === 'sent')
                                            <span class="text-muted">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                        @else
                                            <span class="text-warning">
                                                <i class="fa-solid fa-clock"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Error Message -->
                                @if($log->error_message)
                                    <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25">
                                        <small class="text-danger">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                            {{ e($log->error_message) }}
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fa-solid fa-comments text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted mb-0">Belum ada percakapan dengan nomor ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
@endsection