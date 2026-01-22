@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <h5 class="mb-0 fw-bold">{{ __('Technician Attendance Recap') }}</h5>
                    
                    <form action="{{ route('attendance.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">{{ __('All Staff (Tech & Admin)') }}</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ request('user_id') == $tech->id ? 'selected' : '' }}>
                                {{ $tech->name }} ({{ $tech->role->name ?? 'User' }})
                            </option>
                        @endforeach
                    </select>
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control form-control-sm" placeholder="{{ __('Month') }}">
                        <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm" placeholder="{{ __('Date') }}">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
                        @if(Auth::user()->hasRole('admin'))
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal">
                            <i class="fa-solid fa-plus"></i> {{ __('Add') }}
                        </button>
                        @endif
                        <a href="{{ route('attendance.pdf', request()->all()) }}" class="btn btn-danger btn-sm" target="_blank">
                            <i class="fa-solid fa-file-pdf"></i> {{ __('PDF') }}
                        </a>
                        <a href="{{ route('attendance.excel', request()->all()) }}" class="btn btn-success btn-sm" target="_blank">
                            <i class="fa-solid fa-file-excel"></i> {{ __('Excel') }}
                        </a>
                        @if(Auth::user()->hasRole('admin'))
                        <button type="button" class="btn btn-warning btn-sm text-dark" onclick="confirmRecapFinance()">
                            <i class="fa-solid fa-money-bill-wave"></i> {{ __('Pay Salary') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="submitBulkDelete()">
                            <i class="fa-regular fa-trash-can"></i> {{ __('Delete Selected') }}
                        </button>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card-body">
                <!-- Stats Summary -->
                <div class="row g-2 mb-4">
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-success-subtle border border-success rounded text-center">
                            <div class="h4 mb-0 text-success fw-bold">{{ $stats['present'] }}</div>
                            <div class="small text-success-emphasis fw-bold">{{ __('Present') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-warning-subtle border border-warning rounded text-center">
                            <div class="h4 mb-0 text-warning fw-bold">{{ $stats['late'] }}</div>
                            <div class="small text-warning-emphasis fw-bold">{{ __('Late') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-info-subtle border border-info rounded text-center">
                            <div class="h4 mb-0 text-info fw-bold">{{ $stats['leave'] }}</div>
                            <div class="small text-info-emphasis fw-bold">{{ __('Leave') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-primary-subtle border border-primary rounded text-center">
                            <div class="h4 mb-0 text-primary fw-bold">{{ $stats['permit'] }}</div>
                            <div class="small text-primary-emphasis fw-bold">{{ __('Permit') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-secondary-subtle border border-secondary rounded text-center">
                            <div class="h4 mb-0 text-secondary fw-bold">{{ $stats['sick'] }}</div>
                            <div class="small text-secondary-emphasis fw-bold">{{ __('Sick') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-danger-subtle border border-danger rounded text-center">
                            <div class="h4 mb-0 text-danger fw-bold">{{ $stats['alpha'] }}</div>
                            <div class="small text-danger-emphasis fw-bold">{{ __('Alpha') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Alerts handled by SweetAlert in Layout --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                @if(Auth::user()->hasRole('admin'))
                                <th class="ps-3">
                                    <input type="checkbox" id="selectAllAttendance" onclick="toggleSelectAll()">
                                </th>
                                @endif
                                <th>{{ __('Technician') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Clock In') }}</th>
                                <th>{{ __('Clock Out') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="pe-3">{{ __('Photos') }}</th>
                                @if(Auth::user()->hasRole('admin'))
                                <th class="text-end pe-3">{{ __('Actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                <tr>
                                    @if(Auth::user()->hasRole('admin'))
                                    <td class="ps-3">
                                        <input type="checkbox" class="attendance-select" value="{{ $attendance->id }}">
                                    </td>
                                    @endif
                                    <td>
                                        <div class="fw-medium">{{ $attendance->user->name }}</div>
                                        <div class="small text-muted">{{ $attendance->user->email }}</div>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $attendance->clock_in->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="small">
                                        <div class="fw-bold">{{ $attendance->clock_in->format('H:i') }}</div>
                                        <a href="https://maps.google.com/?q={{ $attendance->lat_clock_in }},{{ $attendance->lng_clock_in }}" target="_blank" class="text-decoration-none small">{{ __('Loc') }}</a>
                                    </td>
                                    <td class="small">
                                        @if($attendance->clock_out)
                                            <div class="fw-bold">{{ $attendance->clock_out->format('H:i') }}</div>
                                            <a href="https://maps.google.com/?q={{ $attendance->lat_clock_out }},{{ $attendance->lng_clock_out }}" target="_blank" class="text-decoration-none small">{{ __('Loc') }}</a>
                                        @else
                                            <span class="text-warning italic">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            {{ __(ucfirst($attendance->status)) }}
                                        </span>
                                    </td>
                                    <td class="pe-3">
                                        <div class="d-flex gap-2">
                                            @if($attendance->photo_clock_in)
                                                <a href="{{ Storage::url($attendance->photo_clock_in) }}" target="_blank">
                                                    <img src="{{ Storage::url($attendance->photo_clock_in) }}" class="rounded object-fit-cover border" style="width: 32px; height: 32px;" alt="In">
                                                </a>
                                            @endif
                                            @if($attendance->photo_clock_out)
                                                <a href="{{ Storage::url($attendance->photo_clock_out) }}" target="_blank">
                                                    <img src="{{ Storage::url($attendance->photo_clock_out) }}" class="rounded object-fit-cover border" style="width: 32px; height: 32px;" alt="Out">
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    @if(Auth::user()->hasRole('admin'))
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex gap-1">
                                            <form method="POST" action="{{ route('attendance.notify', $attendance) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success text-white btn-sm" title="{{ __('Send WhatsApp Notification') }}" onclick="return confirm('{{ __('Send WhatsApp notification?') }}')">
                                                    <i class="fa-brands fa-whatsapp me-1"></i> {{ __('Notify') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('attendance.destroy', $attendance->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this record?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('Delete') }}">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->hasRole('admin') ? 8 : 6 }}" class="text-center py-4 text-muted">{{ __('No attendance records found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $attendances->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manual Attendance Modal -->
<div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">{{ __('Add Attendance / Leave') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('attendance.storeManual') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('User') }}</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">{{ __('Select User') }}</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Date') }}</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Status') }}</label>
                        <select name="status" class="form-select" required>
                            <option value="present">{{ __('Present (Hadir)') }}</option>
                            <option value="leave">{{ __('Leave (Cuti)') }}</option>
                            <option value="permit">{{ __('Permit (Izin)') }}</option>
                            <option value="sick">{{ __('Sick (Sakit)') }}</option>
                            <option value="late">{{ __('Late (Terlambat)') }}</option>
                            <option value="alpha">{{ __('Alpha (Tanpa Keterangan)') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Record') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(Auth::user()->hasRole('admin'))
<form id="form-recap-finance" action="{{ route('attendance.recap_finance') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="user_id" value="{{ request('user_id') }}">
    <input type="hidden" name="month" value="{{ request('month') }}">
    <input type="hidden" name="date" value="{{ request('date') }}">
</form>

<form id="bulkDeleteForm" action="{{ route('attendance.bulkDestroy') }}" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmRecapFinance() {
    Swal.fire({
        title: '{{ __('Record Salary Expense?') }}',
        text: '{{ __('This will create an expense transaction in Finance based on the current filter.') }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#d33',
        confirmButtonText: '{{ __('Yes, Record it!') }}',
        cancelButtonText: '{{ __('Cancel') }}'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-recap-finance').submit();
        }
    });
}

function toggleSelectAll() {
    const master = document.getElementById('selectAllAttendance');
    const items = document.querySelectorAll('.attendance-select');
    items.forEach(cb => cb.checked = master.checked);
}

function submitBulkDelete() {
    const selected = Array.from(document.querySelectorAll('.attendance-select:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: '{{ __('No attendance records found.') }}'
        });
        return;
    }
    Swal.fire({
        title: '{{ __('Are you sure you want to delete this record?') }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __('Yes, Record it!') }}',
        cancelButtonText: '{{ __('Cancel') }}'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('bulkDeleteForm');
            form.innerHTML = '@csrf<input type="hidden" name="_method" value="DELETE">';
            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            form.submit();
        }
    });
}
</script>
@endif

@endsection
