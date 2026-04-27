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

                            <div class="row g-1 align-items-end">
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
                                        <option value="longshift" {{ $currentShift === 'longshift' ? 'selected' : '' }}>LS</option>
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
