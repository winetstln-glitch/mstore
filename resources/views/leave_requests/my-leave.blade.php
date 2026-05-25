@extends('layouts.app')

@section('title', 'Cuti & Izin Saya')

@section('content')
<div class="container-fluid py-4">
    <div class="leave-header-card p-3 p-md-4 rounded-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h2 mb-1 fw-bold">Cuti & Izin Saya</h1>
                <p class="text-body-secondary mb-0">Kelola pengajuan cuti dan izin Anda secara mandiri.</p>
            </div>
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#createLeaveModal">
                <i class="fa-solid fa-plus me-1"></i>Ajukan Cuti/Izin
            </button>
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
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card quota h-100 rounded-4 p-3">
                <div class="leave-stat-label">Kuota Bulan Ini</div>
                <div class="leave-stat-value">{{ $usedDays }} / {{ $quota }}</div>
                <div class="leave-stat-subtitle">Hari terpakai</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card pending h-100 rounded-4 p-3">
                <div class="leave-stat-label">Pending</div>
                <div class="leave-stat-value">{{ $summaryData['pending'] }}</div>
                <div class="leave-stat-subtitle">Menunggu persetujuan</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card approved h-100 rounded-4 p-3">
                <div class="leave-stat-label">Approved</div>
                <div class="leave-stat-value">{{ $summaryData['approved'] }}</div>
                <div class="leave-stat-subtitle">Sudah disetujui</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card rejected h-100 rounded-4 p-3">
                <div class="leave-stat-label">Rejected</div>
                <div class="leave-stat-value">{{ $summaryData['rejected'] }}</div>
                <div class="leave-stat-subtitle">Ditolak</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 leave-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Durasi</th>
                            <th>Alasan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr>
                            <td class="ps-3">{{ $req->start_date->translatedFormat('d M Y') }}</td>
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
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-body-secondary">Belum ada pengajuan cuti/izin.</td>
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

    .leave-stat-card.quota {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.25);
        color: #1d4ed8;
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

    [data-bs-theme="dark"] .leave-stat-card.quota {
        color: #93c5fd;
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

<div class="modal fade" id="createLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leave-requests.store') }}" method="POST">
            @csrf
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Ajukan Cuti/Izin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body leave-modal-body-surface">
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="category" class="form-select" required>
                            <option value="cuti">Cuti</option>
                            <option value="sakit">Izin Sakit</option>
                            <option value="keluarga">Izin Keperluan Keluarga</option>
                            <option value="mendadak">Izin Keperluan Mendadak</option>
                            <option value="lainnya">Izin Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-info rounded-4 border-0 mb-0">
                        Maximum {{ $quota }} hari diperbolehkan per bulan.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
