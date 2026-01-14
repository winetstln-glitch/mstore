@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Technician Schedule (Piket)') }}</h1>
        <div class="d-flex gap-2">
            <form action="{{ route('schedules.index') }}" method="GET" class="d-flex gap-2">
                <select name="month" class="form-select" onchange="this.form.submit()">
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Week') }}</th>
                            <th>{{ __('Date Range') }}</th>
                            @foreach($technicians as $tech)
                                <th class="text-center">{{ $tech->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Generate weeks for the selected month
                            $startDate = Carbon\Carbon::createFromDate($year, $month, 1)->startOfWeek();
                            $endDate = Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfWeek();
                        @endphp

                        @for($date = $startDate; $date->lte($endDate); $date->addWeek())
                            @php
                                $weekNum = $date->weekOfYear;
                                $period = $periods->get($weekNum);
                                $weekStart = $period ? $period->start_date->translatedFormat('d M') : $date->copy()->startOfWeek()->translatedFormat('d M');
                                $weekEnd = $period ? $period->end_date->translatedFormat('d M') : $date->copy()->endOfWeek()->translatedFormat('d M');
                                $fullStartDate = $period ? $period->start_date->format('Y-m-d') : $date->copy()->startOfWeek()->format('Y-m-d');
                                $fullEndDate = $period ? $period->end_date->format('Y-m-d') : $date->copy()->endOfWeek()->format('Y-m-d');
                            @endphp
                            <tr>
                                <td>{{ __('Week') }} {{ $weekNum }}</td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ $weekStart }} - {{ $weekEnd }}</span>
                                        @if(Auth::user()->hasRole('admin'))
                                        <button class="btn btn-sm btn-link p-0 ms-2" onclick="editPeriod({{ $year }}, {{ $weekNum }}, '{{ $fullStartDate }}', '{{ $fullEndDate }}')">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                                @foreach($technicians as $tech)
                                    @php
                                        $weekSchedules = $schedules->get($weekNum);
                                        $schedule = $weekSchedules ? $weekSchedules->where('user_id', $tech->id)->first() : null;
                                        $status = $schedule ? $schedule->status : 'off'; // Default off if not set
                                    @endphp
                                    <td class="text-center">
                                        @if(Auth::user()->hasRole('admin'))
                                        <form action="{{ route('schedules.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $tech->id }}">
                                            <input type="hidden" name="week_number" value="{{ $weekNum }}">
                                            <input type="hidden" name="year" value="{{ $year }}">
                                            
                                            <select name="status" class="form-select form-select-sm {{ $status == 'piket' ? 'bg-success text-white' : ($status == 'backup' ? 'bg-warning' : '') }}" onchange="this.form.submit()">
                                                <option value="off" {{ $status == 'off' ? 'selected' : '' }}>{{ __('Off') }}</option>
                                                <option value="piket" {{ $status == 'piket' ? 'selected' : '' }}>{{ __('Piket') }}</option>
                                                <option value="backup" {{ $status == 'backup' ? 'selected' : '' }}>{{ __('Backup') }}</option>
                                            </select>
                                        </form>
                                        @else
                                            @if($status == 'piket')
                                                <span class="badge bg-success">{{ __('Piket') }}</span>
                                            @elseif($status == 'backup')
                                                <span class="badge bg-warning text-dark">{{ __('Backup') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Off') }}</span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Period Modal -->
<div class="modal fade" id="editPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('schedules.updatePeriod') }}" method="POST">
            @csrf
            <input type="hidden" name="year" id="periodYear">
            <input type="hidden" name="week_number" id="periodWeek">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Week Period') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Start Date') }}</label>
                        <input type="date" name="start_date" id="periodStart" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('End Date') }}</label>
                        <input type="date" name="end_date" id="periodEnd" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function editPeriod(year, week, start, end) {
    document.getElementById('periodYear').value = year;
    document.getElementById('periodWeek').value = week;
    document.getElementById('periodStart').value = start;
    document.getElementById('periodEnd').value = end;
    
    var modal = new bootstrap.Modal(document.getElementById('editPeriodModal'));
    modal.show();
}
</script>
@endsection
