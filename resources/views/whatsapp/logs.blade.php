@extends('layouts.app')

@section('title', 'WhatsApp Logs')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold">
                <i class="fa-brands fa-whatsapp me-2 text-success"></i>
                @if($selectedPhone)
                    Percakapan dengan <span class="text-primary">{{ $selectedPhone }}</span>
                @else
                    WhatsApp Activity Logs
                @endif
            </h5>
            <div class="d-flex flex-wrap gap-2">
                @if($selectedPhone)
                    <a href="{{ route('whatsapp.logs') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                @endif
                <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                    <i class="fa-solid fa-cog me-1"></i> Pengaturan
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filters & Sidebar -->
            @if(!$selectedPhone)
                <div class="bg-light border-bottom px-4 py-3">
                    <form method="GET" action="{{ route('whatsapp.logs') }}" class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text fw-medium bg-white border-end-0">
                                <i class="fa-solid fa-filter me-1 text-muted"></i>Tipe
                            </span>
                            <select class="form-select border-start-0" name="type" onchange="this.form.submit()">
                                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="incoming" {{ $type === 'incoming' ? 'selected' : '' }}>Pesan Masuk</option>
                                <option value="outgoing" {{ $type === 'outgoing' ? 'selected' : '' }}>Pesan Keluar</option>
                            </select>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text fw-medium bg-white border-end-0">
                                <i class="fa-solid fa-circle-check me-1 text-muted"></i>Status
                            </span>
                            <select class="form-select border-start-0" name="status" onchange="this.form.submit()">
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
                
                <div class="d-flex" style="min-height: 600px;">
                    <!-- Sidebar Phone List -->
                    <div class="border-end bg-white" style="width: 320px; flex-shrink: 0; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            @forelse($uniquePhones as $phone)
                                <a href="{{ route('whatsapp.logs', array_merge(request()->all(), ['phone' => $phone])) }}" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                                    <div class="d-flex flex-column">
                                        <div class="fw-semibold text-dark d-flex align-items-center">
                                            <i class="fa-solid fa-user-circle text-secondary me-2 fs-5"></i>
                                            {{ $phone }}
                                        </div>
                                        @if(isset($phoneLastLogs[$phone]))
                                            <small class="text-muted mt-1">
                                                {{ Str::limit($phoneLastLogs[$phone]->message, 35) }}
                                            </small>
                                        @endif
                                    </div>
                                    @if(isset($phoneLastLogs[$phone]))
                                        <small class="text-muted ms-2" style="white-space: nowrap;">
                                            {{ $phoneLastLogs[$phone]->created_at->diffForHumans() }}
                                        </small>
                                    @endif
                                </a>
                            @empty
                                <div class="list-group-item text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox d-block mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-0 fw-medium">Belum ada percakapan</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    
                    <!-- Table View -->
                    <div class="flex-grow-1 bg-white overflow-auto">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top shadow-sm">
                                    <tr>
                                        <th scope="col" class="px-4 py-3" style="width: 180px;">Waktu</th>
                                        <th scope="col" class="px-4 py-3" style="width: 140px;">Tipe</th>
                                        <th scope="col" class="px-4 py-3" style="width: 180px;">No. WhatsApp</th>
                                        <th scope="col" class="px-4 py-3">Pesan</th>
                                        <th scope="col" class="px-4 py-3" style="width: 120px;">Status</th>
                                        <th scope="col" class="px-4 py-3" style="width: 120px;">Response Time</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse($logs as $log)
                                    <tr class="border-bottom">
                                        <td class="px-4 py-3" style="white-space: nowrap;">
                                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($log->type === 'incoming')
                                                <span class="badge bg-info text-dark px-3 py-1">
                                                    <i class="fa-solid fa-arrow-down me-1"></i> Customer
                                                </span>
                                            @else
                                                <span class="badge {{ $log->sender_type === 'cs' ? 'bg-success' : 'bg-primary' }} px-3 py-1">
                                                    <i class="fa-solid fa-arrow-up me-1"></i> 
                                                    {{ $log->sender_type === 'cs' ? 'CS' : 'Bot' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('whatsapp.logs', array_merge(request()->all(), ['phone' => $log->phone_number])) }}" 
                                               class="font-monospace text-decoration-none fw-medium">
                                                {{ $log->phone_masked }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3" style="max-width: 450px;">
                                            <div class="text-truncate fw-medium" title="{{ e($log->message) }}">
                                                {{ e($log->message) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
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
                                            <span class="badge {{ $badge }} px-3 py-1">{{ $text }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($log->processing_time_ms)
                                                <small class="text-muted fw-medium">
                                                    <i class="fa-solid fa-clock me-1"></i>{{ $log->processing_time_ms }} ms
                                                </small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fa-solid fa-inbox text-muted mb-3 d-block" style="font-size: 3rem;"></i>
                                            <p class="mb-0 text-muted fw-medium">Tidak ada log aktivitas WhatsApp ditemukan.</p>
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
                    <div class="bg-white border-top px-4 py-3">
                        {{ $logs->links() }}
                    </div>
                @endif
                
            @else
                <!-- Conversation View -->
                <div class="d-flex flex-column" style="min-height: 600px;">
                    <div class="bg-white border-bottom shadow-sm px-4 py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex flex-column">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa-solid fa-user-circle text-secondary me-2 fs-4"></i>
                                {{ $selectedPhone }}
                            </h6>
                            <small class="text-muted">
                                {{ $conversation ? count($conversation) . ' pesan' : '' }}
                            </small>
                        </div>
                    </div>
                    
                    <div class="flex-grow-1 overflow-auto p-4" style="background: url('https://user-images.githubusercontent.com/15075759/28192123-0c855076-6853-11e7-82fb-3e2e5991f171.png'); background-size: contain;">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-lg-10 col-xl-8">
                                    @forelse($conversation as $log)
                                        <div class="d-flex {{ $log->type === 'incoming' ? '' : 'justify-content-end' }} mb-4">
                                            <div class="rounded-4 shadow-sm p-3 px-4" 
                                                 style="max-width: 80%; 
                                                        {{ $log->type === 'incoming' ? 'background: #ffffff;' : 'background: #d9fdd3;' }}
                                                        {{ $log->status === 'failed' ? 'border: 2px solid #dc3545;' : '' }}">
                                                
                                                <div class="d-flex justify-content-between align-items-center mb-2">
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
                                                    <small class="text-muted ms-3" style="white-space: nowrap;">
                                                        {{ $log->created_at->format('H:i') }}
                                                    </small>
                                                </div>
                                                
                                                <p class="mb-2 text-break" style="white-space: pre-wrap;">
                                                    {{ e($log->message) }}
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center gap-3">
                                                    <div>
                                                        @if($log->processing_time_ms)
                                                            <small class="text-muted fw-medium" title="Waktu Respon">
                                                                <i class="fa-solid fa-clock me-1"></i>{{ $log->processing_time_ms }} ms
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if($log->status === 'failed')
                                                            <span class="badge bg-danger text-white px-2 py-1" title="Error: {{ e($log->error_message) }}">
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
                                                
                                                @if($log->error_message)
                                                    <div class="mt-3 p-3 bg-danger bg-opacity-10 rounded-3 border border-danger border-opacity-25">
                                                        <small class="text-danger fw-semibold">
                                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                            {{ e($log->error_message) }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="fa-solid fa-comments text-muted mb-3" style="font-size: 4rem;"></i>
                                            <p class="text-muted mb-0 fw-medium fs-6">Belum ada percakapan dengan nomor ini.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
