{{-- ========== DAILY MODE ========== --}}
@php
    $rangeStart = isset($dailyRangeStart) 
        ? $dailyRangeStart->copy() 
        : \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
    $rangeEnd = isset($dailyRangeEnd) 
        ? $dailyRangeEnd->copy() 
        : \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->endOfMonth()->startOfDay();
    
    $days = [];
    for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
        $days[] = $d->copy();
    }
    $canManage = Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage');
@endphp

{{-- Date Range Filter Bar --}}
<div class="date-range-filter">
    <form action="{{ route('schedules.index') }}" method="GET" 
          class="d-flex align-items-center gap-3 flex-wrap">
        <input type="hidden" name="mode" value="daily">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="group" value="{{ $selectedGroup }}">
        <input type="hidden" name="shift" value="{{ $selectedShift }}">
        
        <div class="d-flex align-items-center gap-2">
            <i class="fa-regular fa-calendar text-muted"></i>
            <span class="text-muted small fw-semibold">Periode:</span>
        </div>
        
        <div class="input-group input-group-sm" style="max-width: 180px;">
            <input type="date" name="start_date" class="form-control" 
                   value="{{ $rangeStart->format('Y-m-d') }}">
        </div>
        
        <span class="text-muted">—</span>
        
        <div class="input-group input-group-sm" style="max-width: 180px;">
            <input type="date" name="end_date" class="form-control" 
                   value="{{ $rangeEnd->format('Y-m-d') }}">
        </div>
        
        <button class="btn btn-primary btn-sm" type="submit">
            <i class="fa-solid fa-check me-1"></i>Terapkan
        </button>
        
        <div class="ms-auto text-muted small d-none d-md-block">
            <i class="fa-solid fa-arrows-left-right me-1"></i>Scroll horizontal untuk melihat tanggal lainnya
        </div>
    </form>
</div>

{{-- Daily Schedule Tables by Group --}}
@if($canManage)
    <form action="{{ route('schedules.daily.bulkStore') }}" method="POST" id="bulkDailyScheduleForm">
        @csrf
@endif

@foreach($groups as $group)
    @if(($group['users'] ?? collect())->count() > 0)
        <div class="schedule-group-section mb-4">
            <div class="schedule-group-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="group-badge">{{ ($group['users'] ?? collect())->count() }}</span>
                    <span class="group-label">{{ $group['label'] }}</span>
                </div>
                @if($canManage)
                    <button type="submit" class="btn btn-success btn-xs shadow-sm">
                        <i class="fa-solid fa-save me-1"></i>Simpan Grup Ini
                    </button>
                @endif
            </div>
            
            <div class="table-responsive schedule-daily-scroll">
                <table class="table table-hover align-middle schedule-month-table schedule-daily-table mb-0">
                    <thead>
                        <tr>
                            <th class="schedule-name-col">
                                <span class="small text-muted fw-semibold">Karyawan</span>
                            </th>
                            @foreach($days as $day)
                                @php $isToday = $day->isToday(); @endphp
                                <th class="schedule-day-col text-center {{ $day->isSunday() ? 'sunday-col' : '' }} {{ $isToday ? 'today-col' : '' }}">
                                    <div class="day-number">{{ $day->translatedFormat('d') }}</div>
                                    <div class="day-name {{ $day->isSunday() ? 'text-danger' : '' }}">{{ $day->translatedFormat('D') }}</div>
                                    @if($isToday) <div class="today-badge">TODAY</div> @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group['users'] as $tech)
                            <tr class="schedule-row">
                                <td class="schedule-name-col">
                                    <div class="employee-name">{{ $tech->schedule_name ?? $tech->name }}</div>
                                    <div class="employee-info">
                                        {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                        @if(!empty($tech->schedule_department))
                                            <span class="employee-divider">•</span>{{ $tech->schedule_department }}
                                        @endif
                                    </div>
                                </td>
                                @foreach($days as $day)
                                    @php
                                        $dayKey = $day->format('Y-m-d');
                                        $row = $dailySchedules->get($dayKey)?->get($tech->id);
                                        $status = $row?->status ?? \App\Models\TechnicianDailySchedule::STATUS_OFF;
                                        $cellClass = match($status) {
                                            \App\Models\TechnicianDailySchedule::STATUS_PIKET  => 'shift-cell piket',
                                            \App\Models\TechnicianDailySchedule::STATUS_BACKUP => 'shift-cell backup',
                                            default  => 'shift-cell off'
                                        };
                                    @endphp
                                    <td class="text-center {{ $day->isSunday() ? 'sunday-col' : '' }} {{ $day->isToday() ? 'today-col' : '' }}">
                                        @if($canManage)
                                            <select name="schedules[{{ $tech->id }}][{{ $dayKey }}]" class="form-select form-select-sm shift-select {{ $cellClass }}">
                                                <option value="{{ \App\Models\TechnicianDailySchedule::STATUS_OFF }}" {{ $status === \App\Models\TechnicianDailySchedule::STATUS_OFF ? 'selected' : '' }}>OFF</option>
                                                <option value="{{ \App\Models\TechnicianDailySchedule::STATUS_PIKET }}" {{ $status === \App\Models\TechnicianDailySchedule::STATUS_PIKET ? 'selected' : '' }}>S1</option>
                                                <option value="{{ \App\Models\TechnicianDailySchedule::STATUS_BACKUP }}" {{ $status === \App\Models\TechnicianDailySchedule::STATUS_BACKUP ? 'selected' : '' }}>S2</option>
                                            </select>
                                        @else
                                            <span class="shift-badge {{ $cellClass }}">
                                                {{ $status === \App\Models\TechnicianDailySchedule::STATUS_PIKET ? 'S1' : ($status === \App\Models\TechnicianDailySchedule::STATUS_BACKUP ? 'S2' : 'Off') }}
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endforeach

@if($canManage)
    </form>
@endif
