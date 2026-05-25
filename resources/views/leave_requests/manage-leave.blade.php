@extends('layouts.app')

@section('title', 'Kelola Pengajuan Cuti & Izin')

@section('content')
<div class="container-fluid py-4">
    <div class="leave-header-card p-3 p-md-4 rounded-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h2 mb-1 fw-bold">Kelola Pengajuan Cuti & Izin</h1>
                <p class="text-body-secondary mb-0">Review, approve, dan reject semua pengajuan cuti & izin teknisi.</p>
            </div>
        </div>
    </div>

    @php
        $summaryData = [
            'pending' => $requests->where('status', 'pending')->count(),
            'approved' => $requests->where('status', 'approved')->count(),
            'rejected' => $requests->where('status', 'rejected')->count(),
        ];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="leave-stat-card pending h-100 rounded-4 p-3">
                <div class="leave-stat-label">Menunggu Persetujuan</div>
                <div class="leave-stat-value">{{ $summaryData['pending'] }}</div>
                <div class="leave-stat-subtitle">Pengajuan yang butuh review</div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="leave-stat-card approved h-100 rounded-4 p-3">
                <div class="leave-stat-label">Sudah Disetujui</div>
                <div class="leave-stat-value">{{ $summaryData['approved'] }}</div>
                <div class="leave-stat-subtitle">Pengajuan yang berhasil</div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="leave-stat-card rejected h-100 rounded-4 p-3">
                <div class="leave-stat-label">Ditolak</div>
                <div class="leave-stat-value">{{ $summaryData['rejected'] }}</div>
                <div class="leave-stat-subtitle">Pengajuan yang ditolak</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.leave-requests') }}" class="row g-2 g-md-3 align-items-center">
                <div class="col-12 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="reason_keyword" value="{{ request('reason_keyword') }}" class="form-control border-0 bg-body-tertiary" placeholder="Cari alasan...">
                    </div>
                </div>
                <div class="col-12 col-lg-7 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                    <a href="{{ route('admin.leave-requests', ['reason_keyword' => 'mendadak']) }}" class="btn btn-outline-danger {{ request('reason_keyword')=='mendadak' ? 'active' : '' }}">Mendadak</a>
                    <a href="{{ route('admin.leave-requests', ['reason_keyword' => 'keluarga']) }}" class="btn btn-outline-secondary {{ request('reason_keyword')=='keluarga' ? 'active' : '' }}">Keluarga</a>
                    <a href="{{ route('admin.leave-requests', ['reason_keyword' => 'sakit']) }}" class="btn btn-outline-success {{ request('reason_keyword')=='sakit' ? 'active' : '' }}">Sakit</a>
                    @if(request()->has('reason_keyword') && request('reason_keyword')!=='')
                    <a href="{{ route('admin.leave-requests') }}" class="btn btn-link text-decoration-none">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 leave-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Teknisi</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Durasi</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-center pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $req->user->name }}</td>
                            <td>{{ $req->start_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $req->end_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $req->start_date->diffInDays($req->end_date) + 1 }} Hari</td>
                            <td>
                                @php
                                    $reasonRaw = $req->reason;
                                    $badge = null;
                                    if (str_starts_with($reasonRaw, '[')) {
                                        $pos = strpos($reasonRaw, ']');
                                        if ($pos !== false) {
                                            $badge = substr($reasonRaw, 1, $pos - 1);
                                            $reasonRaw = trim(substr($reasonRaw, $pos + 1));
                                        }
                                    }
                                @endphp
                                @if($badge)
                                    <span class="badge text-bg-secondary rounded-pill me-1">{{ $badge }}</span>
                                @endif
                                <span>{{ $reasonRaw }}</span>
                            </td>
                            <td>
                                @if($req->status == 'approved')
                                    <span class="badge text-bg-success rounded-pill">Approved</span>
                                @elseif($req->status == 'rejected')
                                    <span class="badge text-bg-danger rounded-pill">Rejected</span>
                                    @if($req->rejection_reason)
                                    <small class="d-block text-body-secondary mt-1">{{ $req->rejection_reason }}</small>
                                    @endif
                                @else
                                    <span class="badge text-bg-warning rounded-pill">Pending</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                @if($req->status == 'pending')
                                <div class="d-inline-flex gap-2">
                                    <button class="btn btn-success btn-sm px-3" onclick="approveLeave({{ $req->id }})" type="button">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm px-3" onclick="rejectLeave({{ $req->id }})" type="button">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-body-secondary">Belum ada pengajuan cuti/izin.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 p-md-4 border-top">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .leave-header-card {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
    }

    .leave-stat-card {
        border: 1px solid transparent;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .leave-stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        opacity: 0.85;
    }

    .leave-stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .leave-stat-subtitle {
        font-size: 0.8rem;
        opacity: 0.85;
    }

    .leave-stat-card.pending {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.25);
        color: #b45309;
    }

    .leave-stat-card.approved {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.25);
        color: #047857;
    }

    .leave-stat-card.rejected {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.25);
        color: #b91c1c;
    }

    .leave-table thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--bs-secondary-color);
    }

    .leave-modal-body-surface {
        background: var(--bs-tertiary-bg);
    }

    [data-bs-theme="dark"] .leave-header-card {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .leave-stat-card.pending {
        color: #fcd34d;
    }

    [data-bs-theme="dark"] .leave-stat-card.approved {
        color: #6ee7b7;
    }

    [data-bs-theme="dark"] .leave-stat-card.rejected {
        color: #fca5a5;
    }

    [data-bs-theme="dark"] .leave-table thead th {
        color: #cbd5e1;
    }

</style>
@endpush

<form id="rejectForm" method="POST" action="">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" value="rejected">
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Tolak Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body leave-modal-body-surface">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="rejection_reason" class="form-control" required></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Konfirmasi Tolak</button>
                </div>
            </div>
        </div>
    </div>
</form>

<form id="approveForm" method="POST" action="">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" value="approved">
</form>

<script>
function approveLeave(id) {
    if(confirm('Setujui pengajuan cuti ini?')) {
        let form = document.getElementById('approveForm');
        form.action = '{{ url('leave-requests') }}/' + id;
        form.submit();
    }
}

function rejectLeave(id) {
    let form = document.getElementById('rejectForm');
    form.action = '{{ url('leave-requests') }}/' + id;
    var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endsection
