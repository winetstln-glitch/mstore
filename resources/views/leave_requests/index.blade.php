@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Leave Requests') }}</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeaveModal">
            <i class="fa-solid fa-plus"></i> {{ __('Request Leave') }}
        </button>
    </div>

    @if(!Auth::user()->hasPermission('leave.manage'))
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Monthly Quota Usage') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $usedDays }} / {{ $quota }} {{ __('Days') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('leave-requests.index') }}" class="d-flex align-items-center gap-2 mb-3">
                <input type="text" name="reason_keyword" value="{{ request('reason_keyword') }}" class="form-control w-auto" placeholder="{{ __('Search reason...') }}">
                <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('Filter') }}</button>
                <a href="{{ route('leave-requests.index', ['reason_keyword' => 'mendadak']) }}" class="btn btn-outline-danger btn-sm {{ request('reason_keyword')=='mendadak' ? 'active' : '' }}">Mendadak</a>
                <a href="{{ route('leave-requests.index', ['reason_keyword' => 'keluarga']) }}" class="btn btn-outline-secondary btn-sm {{ request('reason_keyword')=='keluarga' ? 'active' : '' }}">Keluarga</a>
                <a href="{{ route('leave-requests.index', ['reason_keyword' => 'sakit']) }}" class="btn btn-outline-success btn-sm {{ request('reason_keyword')=='sakit' ? 'active' : '' }}">Sakit</a>
                @if(request()->has('reason_keyword') && request('reason_keyword')!=='')
                <a href="{{ route('leave-requests.index') }}" class="btn btn-link btn-sm text-decoration-none">{{ __('Clear') }}</a>
                @endif
            </form>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Start Date') }}</th>
                            <th>{{ __('End Date') }}</th>
                            <th>{{ __('Duration') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Status') }}</th>
                            @if(Auth::user()->hasPermission('leave.manage'))
                            <th>{{ __('Action') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->user->name }}</td>
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
                                    <span class="badge bg-secondary me-1">{{ $badge }}</span>
                                @endif
                                <span>{{ $reasonRaw }}</span>
                            </td>
                            <td>
                                @if($req->status == 'approved')
                                    <span class="badge bg-success">{{ __('Approved') }}</span>
                                @elseif($req->status == 'rejected')
                                    <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                    @if($req->rejection_reason)
                                    <small class="d-block text-muted">{{ $req->rejection_reason }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            @if(Auth::user()->hasPermission('leave.manage'))
                            <td>
                                @if($req->status == 'pending')
                                <button class="btn btn-success btn-sm" onclick="approveLeave({{ $req->id }})">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="rejectLeave({{ $req->id }})">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->hasPermission('leave.manage') ? 7 : 6 }}" class="text-center">{{ __('No leave requests found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>
    </div>
</div>

<!-- Create Leave Modal -->
<div class="modal fade" id="createLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leave-requests.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Request Leave') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                    <div class="alert alert-info">
                        {{ __('Maximum :count days allowed per month.', ['count' => $quota]) }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit Request') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal (Dynamic) -->
<form id="rejectForm" method="POST" action="">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" value="rejected">
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Reject Request') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">{{ __('Reason for Rejection') }}</label>
                    <textarea name="rejection_reason" class="form-control" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Confirm Reject') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Approve Form -->
<form id="approveForm" method="POST" action="">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" value="approved">
</form>

<script>
function approveLeave(id) {
    if(confirm('{{ __('Approve this leave request?') }}')) {
        let form = document.getElementById('approveForm');
        form.action = '/leave-requests/' + id;
        form.submit();
    }
}

function rejectLeave(id) {
    let form = document.getElementById('rejectForm');
    form.action = '/leave-requests/' + id;
    var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endsection
