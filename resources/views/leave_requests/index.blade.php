@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="leave-header-card p-3 p-md-4 rounded-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1 fw-bold">{{ __('Leave Requests') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Kelola pengajuan cuti dan izin teknisi dalam satu halaman.') }}</p>
            </div>
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#createLeaveModal">
                <i class="fa-solid fa-plus me-1"></i>{{ __('Request Leave') }}
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
        @if(!Auth::user()->hasPermission('leave.manage'))
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card quota h-100 rounded-4 p-3">
                <div class="leave-stat-label">{{ __('Kuota Bulan Ini') }}</div>
                <div class="leave-stat-value">{{ $usedDays }} / {{ $quota }}</div>
                <div class="leave-stat-subtitle">{{ __('Hari terpakai') }}</div>
            </div>
        </div>
        @endif
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card pending h-100 rounded-4 p-3">
                <div class="leave-stat-label">{{ __('Pending') }}</div>
                <div class="leave-stat-value">{{ $summaryData['pending'] }}</div>
                <div class="leave-stat-subtitle">{{ __('Menunggu persetujuan') }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card approved h-100 rounded-4 p-3">
                <div class="leave-stat-label">{{ __('Approved') }}</div>
                <div class="leave-stat-value">{{ $summaryData['approved'] }}</div>
                <div class="leave-stat-subtitle">{{ __('Sudah disetujui') }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="leave-stat-card rejected h-100 rounded-4 p-3">
                <div class="leave-stat-label">{{ __('Rejected') }}</div>
                <div class="leave-stat-value">{{ $summaryData['rejected'] }}</div>
                <div class="leave-stat-subtitle">{{ __('Ditolak') }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('leave-requests.index') }}" class="row g-2 g-md-3 align-items-center">
                <div class="col-12 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="reason_keyword" value="{{ request('reason_keyword') }}" class="form-control border-0 bg-body-tertiary" placeholder="{{ __('Search reason...') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-7 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('leave-requests.index', ['reason_keyword' => 'mendadak']) }}" class="btn btn-outline-danger {{ request('reason_keyword')=='mendadak' ? 'active' : '' }}">Mendadak</a>
                    <a href="{{ route('leave-requests.index', ['reason_keyword' => 'keluarga']) }}" class="btn btn-outline-secondary {{ request('reason_keyword')=='keluarga' ? 'active' : '' }}">Keluarga</a>
                    <a href="{{ route('leave-requests.index', ['reason_keyword' => 'sakit']) }}" class="btn btn-outline-success {{ request('reason_keyword')=='sakit' ? 'active' : '' }}">Sakit</a>
                    @if(request()->has('reason_keyword') && request('reason_keyword')!=='')
                    <a href="{{ route('leave-requests.index') }}" class="btn btn-link text-decoration-none">{{ __('Clear') }}</a>
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
                            <th class="ps-3">{{ __('Employee') }}</th>
                            <th>{{ __('Start Date') }}</th>
                            <th>{{ __('End Date') }}</th>
                            <th>{{ __('Duration') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Status') }}</th>
                            @if(Auth::user()->hasPermission('leave.manage') || Auth::user()->id === $req->user_id)
                            <th class="text-center pe-3">{{ __('Action') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $req->user->name }}</td>
                            <td>{{ $req->start_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $req->end_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $req->start_date->diffInDays($req->end_date) + 1 }} {{ __('Days') }}</td>
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
                                    <span class="badge text-bg-success rounded-pill">{{ __('Approved') }}</span>
                                @elseif($req->status == 'rejected')
                                    <span class="badge text-bg-danger rounded-pill">{{ __('Rejected') }}</span>
                                    @if($req->rejection_reason)
                                    <small class="d-block text-body-secondary mt-1">{{ $req->rejection_reason }}</small>
                                    @endif
                                @else
                                    <span class="badge text-bg-warning rounded-pill">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-2">
                                    @if(Auth::user()->hasPermission('leave.manage') || ($req->status == 'pending' && Auth::id() === $req->user_id))
                                    <a href="{{ route('leave-requests.edit', $req->id) }}" class="btn btn-outline-primary btn-sm px-3">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @endif
                                    
                                    @if(Auth::user()->hasPermission('leave.manage') && $req->status == 'pending')
                                    <button class="btn btn-success btn-sm px-3" onclick="approveLeave({{ $req->id }})" type="button">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm px-3" onclick="rejectLeave({{ $req->id }})" type="button">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->hasPermission('leave.manage') ? 7 : 6 }}" class="text-center py-5 text-body-secondary">{{ __('No leave requests found.') }}</td>
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

<!-- Create Leave Modal -->
<div class="modal fade" id="createLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leave-requests.store') }}" method="POST">
            @csrf
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">{{ __('Request Leave') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body leave-modal-body-surface">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="category" class="form-select" required>
                            <option value="cuti">Cuti</option>
                            <option value="sakit">Izin Sakit</option>
                            <option value="keluarga">Izin Keperluan Keluarga</option>
                            <option value="mendadak">Izin Keperluan Mendadak</option>
                            <option value="lainnya">Izin Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Start Date') }}</label>
                        <input type="date" name="start_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('End Date') }}</label>
                        <input type="date" name="end_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-info rounded-4 border-0 mb-0">
                        {{ __('Maximum :count days allowed per month.', ['count' => $quota]) }}
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit Request') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form id="rejectForm" method="POST" action="">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" value="rejected">
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">{{ __('Reject Request') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body leave-modal-body-surface">
                    <label class="form-label">{{ __('Reason for Rejection') }}</label>
                    <textarea name="rejection_reason" class="form-control" required></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Confirm Reject') }}</button>
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
    if(confirm('{{ __('Approve this leave request?') }}')) {
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
