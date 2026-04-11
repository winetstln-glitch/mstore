@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    {{-- ============================================ 
         PAGE HEADER 
         ============================================ --}}
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col-12 col-xl-auto mb-3 mb-xl-0">
                <h1 class="h3 fw-bold text-gray-800 mb-1">{{ __('Jadwal Shift Karyawan') }}</h1>
                <p class="text-muted small mb-0">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    Semua karyawan tampil di scheduler (kecuali Owner/Coordinator/Admin). Shift dipisah: Teknisi dan Operator Wash.
                </p>
            </div>
            <div class="col-12 col-xl">
                @php
                    $currentMode    = $mode ?? 'weekly';
                    $currentGroup   = $selectedGroup ?? 'all';
                    $currentShift   = $selectedShift ?? 'all';
                    $canManage      = Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage');
                    $exportParams   = array_filter([
                        'month'      => $month,
                        'year'       => $year,
                        'mode'       => $currentMode,
                        'group'      => $selectedGroup ?? null,
                        'shift'      => $selectedShift ?? null,
                        'start_date' => ($currentMode === 'daily' && isset($dailyRangeStart)) ? $dailyRangeStart->format('Y-m-d') : null,
                        'end_date'   => ($currentMode === 'daily' && isset($dailyRangeEnd)) ? $dailyRangeEnd->format('Y-m-d') : null,
                    ]);
                @endphp

                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch align-items-lg-end justify-content-xl-end">
                    {{-- Filter Section --}}
                    <div class="filter-group filter-group-filters">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="filter-label">Filter</div>
                            <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none"
                                    data-bs-toggle="collapse" data-bs-target="#scheduleFilterCollapse"
                                    aria-expanded="false" aria-controls="scheduleFilterCollapse">
                                <i class="fa-solid fa-sliders me-1"></i>Atur
                            </button>
                        </div>

                        <div class="collapse d-lg-block mt-2" id="scheduleFilterCollapse">
                            <form action="{{ route('schedules.index') }}" method="GET" class="schedule-filter-form">
                                <input type="hidden" name="mode" value="{{ $currentMode }}">
                                @if($currentMode === 'daily' && isset($dailyRangeStart, $dailyRangeEnd))
                                    <input type="hidden" name="start_date" value="{{ $dailyRangeStart->format('Y-m-d') }}">
                                    <input type="hidden" name="end_date" value="{{ $dailyRangeEnd->format('Y-m-d') }}">
                                @endif

                                <div class="row g-2 align-items-end">
                                    <div class="col-6 col-md-3 col-xl-2">
                                        <label class="form-label small text-muted mb-1">Bulan</label>
                                        <select name="month" class="form-select form-select-sm shadow-sm">
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="col-6 col-md-3 col-xl-2">
                                        <label class="form-label small text-muted mb-1">Tahun</label>
                                        <select name="year" class="form-select form-select-sm shadow-sm">
                                            @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-3 col-xl-3">
                                        <label class="form-label small text-muted mb-1">Grup</label>
                                        <select name="group" class="form-select form-select-sm shadow-sm">
                                            <option value="all" {{ $currentGroup === 'all' ? 'selected' : '' }}>Semua Grup</option>
                                            <option value="teknisi" {{ $currentGroup === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                                            <option value="wash" {{ $currentGroup === 'wash' ? 'selected' : '' }}>Operator Wash</option>
                                            <option value="lainnya" {{ $currentGroup === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-3 col-xl-2">
                                        <label class="form-label small text-muted mb-1">Shift</label>
                                        <select name="shift" class="form-select form-select-sm shadow-sm">
                                            <option value="all" {{ $currentShift === 'all' ? 'selected' : '' }}>Semua Shift</option>
                                            <option value="piket" {{ $currentShift === 'piket' ? 'selected' : '' }}>S1</option>
                                            <option value="backup" {{ $currentShift === 'backup' ? 'selected' : '' }}>S2</option>
                                            <option value="off" {{ $currentShift === 'off' ? 'selected' : '' }}>OFF</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-xl-auto d-grid">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-filter me-1"></i>Terapkan
                                        </button>
                                    </div>

                                    <div class="col-12 col-xl-auto d-grid">
                                        <a href="{{ route('schedules.index', ['month' => $month, 'year' => $year, 'mode' => $currentMode]) }}"
                                           class="btn btn-light btn-sm">
                                            Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="right-toolbar d-flex gap-2 align-items-end justify-content-end flex-wrap flex-xl-nowrap ms-lg-auto">
                        {{-- Mode Toggle --}}
                        <div class="filter-group filter-group-min">
                            <div class="filter-label">Tampilan</div>
                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                <a href="{{ route('schedules.index', array_filter([
                                    'month' => $month, 'year' => $year, 'mode' => 'weekly',
                                    'group' => $selectedGroup ?? null, 'shift' => $selectedShift ?? null,
                                ])) }}" 
                                   class="btn btn-outline-primary {{ $currentMode === 'weekly' ? 'active' : '' }}">
                                    <i class="fa-solid fa-calendar-week me-1"></i>Mingguan
                                </a>
                                <a href="{{ route('schedules.index', array_filter([
                                    'month' => $month, 'year' => $year, 'mode' => 'daily',
                                    'group' => $selectedGroup ?? null, 'shift' => $selectedShift ?? null,
                                    'start_date' => isset($dailyRangeStart) ? $dailyRangeStart->format('Y-m-d') : null,
                                    'end_date' => isset($dailyRangeEnd) ? $dailyRangeEnd->format('Y-m-d') : null,
                                ])) }}" 
                                   class="btn btn-outline-primary {{ $currentMode === 'daily' ? 'active' : '' }}">
                                    <i class="fa-solid fa-calendar-day me-1"></i>Harian
                                </a>
                            </div>
                        </div>

                        {{-- Export Buttons --}}
                        <div class="filter-group filter-group-min">
                            <div class="filter-label">Export</div>
                            <div class="btn-group btn-group-sm shadow-sm" role="group">
                                <a href="{{ route('schedules.export.pdf', $exportParams) }}" 
                                   class="btn btn-outline-danger" title="Export PDF">
                                    <i class="fa-regular fa-file-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
                                </a>
                                <a href="{{ route('schedules.export.excel', $exportParams) }}" 
                                   class="btn btn-outline-success" title="Export Excel">
                                    <i class="fa-regular fa-file-excel me-1"></i><span class="d-none d-sm-inline">Excel</span>
                                </a>
                            </div>
                        </div>

                        {{-- Admin Actions --}}
                        @if($canManage)
                            <div class="filter-group filter-group-min">
                                <div class="filter-label">Aksi</div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary shadow-sm" 
                                            data-bs-toggle="modal" data-bs-target="#importScheduleModal" title="Import Excel">
                                        <i class="fa-solid fa-upload me-1"></i><span class="d-none d-md-inline">Import</span>
                                    </button>
                                    @if($currentMode === 'daily')
                                        <button type="button" class="btn btn-dark shadow-sm" 
                                                data-bs-toggle="modal" data-bs-target="#autoDailyScheduleModal" title="Auto Generate Harian">
                                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i><span class="d-none d-md-inline">Generate</span>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-dark shadow-sm" 
                                                data-bs-toggle="modal" data-bs-target="#autoScheduleModal" title="Auto Generate Mingguan">
                                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i><span class="d-none d-md-inline">Generate</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ 
         SHIFT LEGEND BAR 
         ============================================ --}}
    <div class="shift-legend-bar mb-4">
        <div class="row g-0 align-items-center">
            <div class="col-auto">
                <div class="legend-item">
                    <span class="legend-dot bg-success"></span>
                    <span class="legend-label">Shift 1</span>
                    <code class="legend-code">{{ $shift1Start }} - {{ $shift1End }}</code>
                </div>
            </div>
            <div class="col-auto px-4">
                <div class="legend-item">
                    <span class="legend-dot bg-warning"></span>
                    <span class="legend-label">Shift 2</span>
                    <code class="legend-code">{{ $shift2Start }} - {{ $shift2End }}</code>
                </div>
            </div>
            <div class="col-auto px-4">
                <div class="legend-item">
                    <span class="legend-dot bg-secondary"></span>
                    <span class="legend-label">Off</span>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <small class="text-muted">
                    <i class="fa-solid fa-gear me-1"></i>Ubah jam shift di <a href="#" class="text-decoration-underline">Pengaturan Attendance</a>
                </small>
            </div>
        </div>
    </div>

    {{-- ============================================ 
         MAIN SCHEDULE CARD 
         ============================================ --}}
    <div class="card shadow-sm border-0 schedule-card">
        <div class="card-body p-0">
            <div class="table-responsive schedule-table-responsive">
                @if($currentMode === 'daily')
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
                    @endphp

                    {{-- Date Range Filter Bar --}}
                    <div class="date-range-filter">
                        <form action="{{ route('schedules.index') }}" method="GET" 
                              class="d-flex align-items-center gap-3 flex-wrap">
                            <input type="hidden" name="mode" value="daily">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="group" value="{{ $currentGroup }}">
                            <input type="hidden" name="shift" value="{{ $currentShift }}">
                            
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
                    @foreach($groups as $group)
                        @if(($group['users'] ?? collect())->count() > 0)
                            <div class="schedule-group-section">
                                <div class="schedule-group-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="group-badge">{{ ($group['users'] ?? collect())->count() }}</span>
                                        <span class="group-label">{{ $group['label'] }}</span>
                                    </div>
                                </div>
                                
                                <table class="table table-hover align-middle schedule-month-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="schedule-name-col">
                                                <span class="small text-muted fw-semibold">Karyawan</span>
                                            </th>
                                            @foreach($days as $day)
                                                <th class="schedule-day-col text-center {{ $day->isSunday() ? 'sunday-col' : '' }}">
                                                    <div class="day-number">{{ $day->translatedFormat('d') }}</div>
                                                    <div class="day-name {{ $day->isSunday() ? 'text-danger' : '' }}">{{ $day->translatedFormat('D') }}</div>
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
                                                        $daySchedules = $dailySchedules->get($dayKey);
                                                        $row = $daySchedules ? $daySchedules->firstWhere('user_id', $tech->id) : null;
                                                        $status = $row?->status ?? 'off';
                                                        $cellClass = match($status) {
                                                            'piket'  => 'shift-cell piket',
                                                            'backup' => 'shift-cell backup',
                                                            default  => 'shift-cell off'
                                                        };
                                                    @endphp
                                                    <td class="text-center {{ $day->isSunday() ? 'sunday-col' : '' }}">
                                                        @if($canManage)
                                                            <form action="{{ route('schedules.daily.store') }}" method="POST" class="shift-form">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{ $tech->id }}">
                                                                <input type="hidden" name="date" value="{{ $dayKey }}">
                                                                <select name="status" class="form-select form-select-sm shift-select {{ $cellClass }}" 
                                                                        onchange="this.form.submit()">
                                                                    <option value="off" {{ $status === 'off' ? 'selected' : '' }}>OFF</option>
                                                                    <option value="piket" {{ $status === 'piket' ? 'selected' : '' }}>S1</option>
                                                                    <option value="backup" {{ $status === 'backup' ? 'selected' : '' }}>S2</option>
                                                                </select>
                                                            </form>
                                                        @else
                                                            <span class="shift-badge {{ $cellClass }}">
                                                                {{ $status === 'piket' ? 'S1' : ($status === 'backup' ? 'S2' : 'Off') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endforeach

                @else
                    {{-- ========== WEEKLY MODE ========== --}}
                    @php $colspan = 1 + count($weeksData); @endphp
                    
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
                                            <span class="group-badge me-2">{{ ($group['users'] ?? collect())->count() }}</span>
                                            <span class="group-label">{{ $group['label'] }}</span>
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
                                                    $weekSchedules = $schedules->get($week['week_number']);
                                                    $schedule = $weekSchedules 
                                                        ? $weekSchedules->where('user_id', $tech->id)->first() 
                                                        : null;
                                                    $status = $schedule ? $schedule->status : 'off';
                                                    $weekCellClass = match($status) {
                                                        'piket'  => 'shift-cell piket',
                                                        'backup' => 'shift-cell backup',
                                                        default  => 'shift-cell off'
                                                    };
                                                @endphp
                                                <td class="text-center">
                                                    @if($canManage)
                                                        <form action="{{ route('schedules.store') }}" method="POST" class="shift-form">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ $tech->id }}">
                                                            <input type="hidden" name="week_number" value="{{ $week['week_number'] }}">
                                                            <input type="hidden" name="year" value="{{ $year }}">
                                                            <select name="status" 
                                                                    class="form-select form-select-sm shift-select {{ $weekCellClass }}" 
                                                                    onchange="this.form.submit()">
                                                                <option value="off" {{ $status === 'off' ? 'selected' : '' }}>OFF</option>
                                                                <option value="piket" {{ $status === 'piket' ? 'selected' : '' }}>S1</option>
                                                                <option value="backup" {{ $status === 'backup' ? 'selected' : '' }}>S2</option>
                                                            </select>
                                                        </form>
                                                    @else
                                                        <span class="shift-badge {{ $weekCellClass }}">
                                                            {{ $status === 'piket' ? 'S1' : ($status === 'backup' ? 'S2' : 'Off') }}
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
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============================================ 
     MODALS 
     ============================================ --}}
@if($canManage)
    {{-- Import Schedule Modal --}}
    <div class="modal fade" id="importScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="{{ route('schedules.import.excel') }}" method="POST" 
                  enctype="multipart/form-data" class="w-100">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="mode" value="{{ $currentMode }}">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-upload me-2 text-primary"></i>Import Jadwal
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="alert alert-info small mb-3">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Gunakan file hasil export Excel, lalu edit nilai menjadi: <strong>S1</strong>, <strong>S2</strong>, atau <strong>OFF</strong>.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">File Excel (.xlsx)</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-upload me-1"></i>Upload
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Auto Generate Weekly Modal --}}
    <div class="modal fade" id="autoScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('schedules.autoGenerate') }}" method="POST" class="w-100" 
                  onsubmit="return confirm('Generate jadwal otomatis untuk bulan ini? Jadwal minggu pada bulan ini akan ditimpa.')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Auto Generate Jadwal
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-4">
                            Tentukan jumlah slot per minggu untuk masing-masing shift. Sisa karyawan akan otomatis mendapat status Off.
                        </p>
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <div class="slot-config">
                                    <div class="slot-icon bg-success">
                                        <span>S1</span>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold mb-1 small">Slot Shift 1 / Minggu</label>
                                        <input type="number" name="shift1_slots" class="form-control form-control-lg text-center" 
                                               min="1" max="50" value="{{ $autoShift1Slots ?? 1 }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="slot-config">
                                    <div class="slot-icon bg-warning">
                                        <span>S2</span>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold mb-1 small">Slot Shift 2 / Minggu</label>
                                        <input type="number" name="shift2_slots" class="form-control form-control-lg text-center" 
                                               min="1" max="50" value="{{ $autoShift2Slots ?? 1 }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark px-4">
                            <i class="fa-solid fa-bolt me-1"></i>Generate
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Auto Generate Daily Modal --}}
    <div class="modal fade" id="autoDailyScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('schedules.daily.autoGenerate') }}" method="POST" class="w-100" 
                  onsubmit="return confirm('Generate jadwal harian otomatis untuk bulan ini? Jadwal harian pada bulan ini akan ditimpa.')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Auto Generate Jadwal Harian
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-4">
                            Sistem akan membagi jadwal S1/S2/OFF secara merata dan memastikan tiap orang mendapat jumlah libur yang sama.
                        </p>
                        <div class="slot-config justify-content-center">
                            <div class="slot-icon bg-secondary">
                                <i class="fa-solid fa-bed"></i>
                            </div>
                            <div style="max-width: 200px;">
                                <label class="form-label fw-semibold mb-1 small">Libur per Orang / Bulan</label>
                                <input type="number" name="off_days" class="form-control form-control-lg text-center" 
                                       min="0" max="10" value="{{ $dailyOffDays ?? 2 }}" required>
                                <div class="form-text text-center">hari libur</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark px-4">
                            <i class="fa-solid fa-bolt me-1"></i>Generate
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- Edit Period Modal --}}
<div class="modal fade" id="editPeriodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('schedules.updatePeriod') }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="year" id="periodYear">
            <input type="hidden" name="week_number" id="periodWeek">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-calendar-pen me-2"></i>Ubah Rentang Minggu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">{{ __('Tanggal Mulai') }}</label>
                            <input type="date" name="start_date" id="periodStart" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">{{ __('Tanggal Selesai') }}</label>
                            <input type="date" name="end_date" id="periodEnd" class="form-control form-control-lg" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-check me-1"></i>Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function editPeriod(year, week, start, end) {
    document.getElementById('periodYear').value = year;
    document.getElementById('periodWeek').value = week;
    document.getElementById('periodStart').value = start;
    document.getElementById('periodEnd').value = end;
    new bootstrap.Modal(document.getElementById('editPeriodModal')).show();
}
</script>
@endpush

@push('styles')
<style>
/* ========================================
   PAGE HEADER & FILTER BAR
   ======================================== */
.filter-group {
    min-width: 0;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.6rem;
    padding: 0.75rem 1rem;
}

.filter-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 0.35rem;
}

.filter-group .form-select {
    min-width: 110px;
}

[data-bs-theme="dark"] .filter-label {
    color: #8a939c;
}

/* ========================================
   SHIFT LEGEND BAR
   ======================================== */
.shift-legend-bar {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.75rem 1.25rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

.legend-label {
    font-weight: 600;
    font-size: 0.85rem;
}

.legend-code {
    font-size: 0.8rem;
    background: var(--bs-tertiary-bg);
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
}

/* ========================================
   SCHEDULE CARD
   ======================================== */
.schedule-card {
    border-radius: 0.75rem;
    overflow: hidden;
}

.date-range-filter {
    background: var(--bs-tertiary-bg);
    border-bottom: 1px solid var(--bs-border-color);
    padding: 0.75rem 1.25rem;
}

/* ========================================
   GROUP SECTIONS (DAILY MODE)
   ======================================== */
.schedule-group-section {
    border-bottom: 2px solid var(--bs-border-color);
}

.schedule-group-section:last-child {
    border-bottom: none;
}

.schedule-group-header {
    background: var(--bs-tertiary-bg);
    padding: 0.6rem 1.25rem;
    border-bottom: 1px solid var(--bs-border-color);
}

.group-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: var(--bs-primary);
    color: #fff;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
}

.group-label {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--bs-body-color);
}

.group-row td {
    background: var(--bs-tertiary-bg);
    padding: 0.6rem 1.25rem;
    border-bottom: 2px solid var(--bs-border-color);
}

/* ========================================
   TABLE STRUCTURE
   ======================================== */
.schedule-name-col {
    min-width: 200px;
    max-width: 220px;
    width: 220px;
    padding: 0.6rem 1rem !important;
    border-right: 2px solid var(--bs-border-color) !important;
    position: sticky;
    left: 0;
    z-index: 2;
    background: var(--bs-body-bg);
}

thead .schedule-name-col {
    z-index: 3;
    background: var(--bs-tertiary-bg);
}

.schedule-day-col {
    min-width: 72px;
    padding: 0.4rem 0.25rem !important;
}

.schedule-week-col {
    min-width: 120px;
    padding: 0.6rem 0.5rem !important;
}

.day-number {
    font-weight: 700;
    font-size: 0.85rem;
    line-height: 1.2;
}

.day-name {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: var(--bs-secondary-color);
}

.sunday-col {
    background: rgba(220, 53, 69, 0.03);
}

[data-bs-theme="dark"] .sunday-col {
    background: rgba(220, 53, 69, 0.08);
}

.week-header {
    display: flex;
    align-items: center;
    justify-content: center;
}

.week-number {
    font-weight: 800;
    font-size: 1rem;
    color: var(--bs-primary);
}

.week-range {
    font-size: 0.65rem;
    color: var(--bs-secondary-color);
    margin-top: 0.15rem;
}

.week-edit-btn {
    font-size: 0.7rem;
    opacity: 0.5;
    transition: opacity 0.2s;
}

.week-edit-btn:hover {
    opacity: 1;
}

/* ========================================
   EMPLOYEE INFO
   ======================================== */
.employee-name {
    font-weight: 600;
    font-size: 0.85rem;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.employee-info {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.employee-divider {
    margin: 0 0.25rem;
    opacity: 0.4;
}

/* ========================================
   SHIFT CELLS & BADGES
   ======================================== */
.shift-form {
    margin: -0.25rem;
}

.shift-select {
    font-size: 0.7rem !important;
    padding: 0.2rem 0.25rem !important;
    border: 2px solid transparent !important;
    border-radius: 0.375rem !important;
    font-weight: 700 !important;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.shift-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.shift-badge {
    display: inline-block;
    min-width: 38px;
    padding: 0.3rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.shift-cell.piket {
    background-color: #198754;
    color: #fff;
}

.shift-cell.backup {
    background-color: #ffc107;
    color: #212529;
}

.shift-cell.off {
    background-color: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
}

.shift-select.shift-cell.off {
    border-color: var(--bs-border-color) !important;
}

.schedule-row:hover .schedule-name-col {
    background: var(--bs-tertiary-bg);
}

/* ========================================
   SLOT CONFIG (MODALS)
   ======================================== */
.slot-config {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.slot-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    color: #fff;
    flex-shrink: 0;
}

/* ========================================
   RESPONSIVE
   ======================================== */
.schedule-table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.schedule-month-table {
    min-width: max-content;
}

@media (max-width: 575.98px) {
    .shift-legend-bar .row {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .shift-legend-bar .col-auto {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    .shift-legend-bar .ms-auto {
        margin-left: 0 !important;
    }
    
    .schedule-name-col {
        min-width: 150px;
        max-width: 150px;
        width: 150px;
    }
    
    .schedule-day-col {
        min-width: 58px;
    }
}

@media (min-width: 1400px) {
    .schedule-name-col {
        min-width: 240px;
        max-width: 240px;
        width: 240px;
    }
}

/* ========================================
   SCROLLBAR STYLING
   ======================================== */
.schedule-table-responsive::-webkit-scrollbar {
    height: 8px;
}

.schedule-table-responsive::-webkit-scrollbar-track {
    background: var(--bs-tertiary-bg);
    border-radius: 4px;
}

.schedule-table-responsive::-webkit-scrollbar-thumb {
    background: var(--bs-secondary-color);
    border-radius: 4px;
}

.schedule-table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--bs-primary);
}

/* ========================================
   DARK MODE OVERRIDES
   ======================================== */
[data-bs-theme="dark"] .text-gray-800 {
    color: var(--bs-body-color) !important;
}

[data-bs-theme="dark"] .shift-cell.off {
    background-color: #2b3035;
    color: #6c757d;
}

[data-bs-theme="dark"] .shift-select.shift-cell.off {
    border-color: #3d4449 !important;
}

.filter-group .btn-group { width: auto; }

.filter-group-filters { min-width: min(100%, 720px); }
.schedule-filter-form .form-select { min-width: 0; }
.schedule-filter-form .btn { white-space: nowrap; }

/* Right toolbar alignment on desktop */
.right-toolbar { border-left: none; padding-left: 0; }
@media (min-width: 1200px) {
    .right-toolbar { border-left: 1px solid var(--bs-border-color); padding-left: 12px; }
}

/* Minimal style for non-filter boxes on desktop */
@media (min-width: 992px) {
    .filter-group-min {
        background: transparent;
        border: 0;
        padding: 0;
    }
}

@media (max-width: 575.98px) {
    .filter-group { padding: 0.7rem 0.8rem; }
    .filter-group .btn-group { width: 100%; }
    .filter-group .btn-group .btn { flex: 1 1 auto; }
}

@media (min-width: 992px) {
    #scheduleFilterCollapse { margin-top: 0 !important; }
}
</style>
@endpush
@endsection
