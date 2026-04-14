{{-- ========== WEEKLY MODE ========== --}}
@php 
    $colspan = 1 + count($weeksData); 
    $canManage = Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage');
@endphp

@if($canManage)
    <form action="{{ route('schedules.bulkStore') }}" method="POST" id="bulkWeeklyScheduleForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
@endif

<table class="table table-hover align-middle mb-0">
    <thead>
        <tr>
            <th class="schedule-name-col">
                <span class="small text-muted fw-semibold">Karyawan</span>
            </th>
            @foreach($weeksData as $week)
                <th class="schedule-week-col text-center">
                    <div class="week-header">
                        <span class="week-number">W{{ $week['week_number'] }}</span>
                        @if($canManage)
                            <button type="button" 
                                    class="btn btn-link btn-sm p-0 ms-1 week-edit-btn" 
                                    title="Ubah rentang tanggal minggu"
                                    onclick="editPeriod(
                                        '{{ $year }}', 
                                        '{{ $week['week_number'] }}', 
                                        '{{ $week['full_start_date'] }}', 
                                        '{{ $week['full_end_date'] }}'
                                    )">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        @endif
                    </div>
                    <div class="week-range">{{ $week['week_start_display'] }} — {{ $week['week_end_display'] }}</div>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($groups as $group)
            @if(($group['users'] ?? collect())->count() > 0)
                <tr class="group-row">
                    <td colspan="{{ $colspan }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="group-badge me-2">{{ ($group['users'] ?? collect())->count() }}</span>
                                <span class="group-label">{{ $group['label'] }}</span>
                            </div>
                            @if($canManage)
                                <button type="submit" class="btn btn-success btn-xs shadow-sm">
                                    <i class="fa-solid fa-save me-1"></i>Simpan Grup Ini
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                
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
                        @foreach($weeksData as $week)
                            @php
                                $schedule = $schedules->get($week['week_number'])?->get($tech->id);
                                $status = $schedule ? $schedule->status : \App\Models\TechnicianSchedule::STATUS_OFF;
                                $weekCellClass = match($status) {
                                    \App\Models\TechnicianSchedule::STATUS_PIKET  => 'shift-cell piket',
                                    \App\Models\TechnicianSchedule::STATUS_BACKUP => 'shift-cell backup',
                                    default  => 'shift-cell off'
                                };
                            @endphp
                            <td class="text-center">
                                @if($canManage)
                                    <select name="schedules[{{ $tech->id }}][{{ $week['week_number'] }}]" 
                                            class="form-select form-select-sm shift-select {{ $weekCellClass }}">
                                        <option value="{{ \App\Models\TechnicianSchedule::STATUS_OFF }}" {{ $status === \App\Models\TechnicianSchedule::STATUS_OFF ? 'selected' : '' }}>OFF</option>
                                        <option value="{{ \App\Models\TechnicianSchedule::STATUS_PIKET }}" {{ $status === \App\Models\TechnicianSchedule::STATUS_PIKET ? 'selected' : '' }}>S1</option>
                                        <option value="{{ \App\Models\TechnicianSchedule::STATUS_BACKUP }}" {{ $status === \App\Models\TechnicianSchedule::STATUS_BACKUP ? 'selected' : '' }}>S2</option>
                                    </select>
                                @else
                                    <span class="shift-badge {{ $weekCellClass }}">
                                        {{ $status === \App\Models\TechnicianSchedule::STATUS_PIKET ? 'S1' : ($status === \App\Models\TechnicianSchedule::STATUS_BACKUP ? 'S2' : 'Off') }}
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        @endforeach
    </tbody>
</table>

@if($canManage)
    </form>
@endif