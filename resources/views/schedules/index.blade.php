@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">{{ __('Jadwal Shift Karyawan (2 Shift)') }}</h1>
            <p class="text-muted small mb-0">Semua karyawan tampil di scheduler (kecuali Owner/Coordinator/Admin). Shift dipisah: Teknisi dan Operator Wash.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('schedules.index') }}" method="GET" class="d-flex gap-2">
                <input type="hidden" name="mode" value="{{ $mode ?? 'weekly' }}">
                <select name="month" class="form-select shadow-sm" onchange="this.form.submit()">
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="year" class="form-select shadow-sm" onchange="this.form.submit()">
                    @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <div class="btn-group shadow-sm">
                <a href="{{ route('schedules.index', ['month' => $month, 'year' => $year, 'mode' => 'weekly']) }}"
                   class="btn btn-outline-primary {{ ($mode ?? 'weekly') === 'weekly' ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-week me-1"></i>Mingguan
                </a>
                <a href="{{ route('schedules.index', ['month' => $month, 'year' => $year, 'mode' => 'daily']) }}"
                   class="btn btn-outline-primary {{ ($mode ?? 'weekly') === 'daily' ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-day me-1"></i>Harian
                </a>
            </div>
            <div class="btn-group shadow-sm">
                <a href="{{ route('schedules.export.pdf', ['month' => $month, 'year' => $year, 'mode' => ($mode ?? 'weekly')]) }}" class="btn btn-outline-danger">
                    <i class="fa-regular fa-file-pdf me-1"></i> PDF
                </a>
                @if(($mode ?? 'weekly') === 'weekly')
                    <a href="{{ route('schedules.export.excel', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-success">
                        <i class="fa-regular fa-file-excel me-1"></i> Excel
                    </a>
                @endif
            </div>
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'))
                @if(($mode ?? 'weekly') === 'daily')
                    <button type="button" class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#autoDailyScheduleModal">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto Generate (Harian)
                    </button>
                @else
                    <button type="button" class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#autoScheduleModal">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto Generate
                    </button>
                @endif
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="alert alert-light border border-info mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex gap-4">
                    <div>
                        <span class="badge bg-success me-1">&nbsp;</span>
                        <strong>Shift 1:</strong> <code class="text-dark">{{ $shift1Start }} - {{ $shift1End }}</code>
                    </div>
                    <div>
                        <span class="badge bg-warning me-1">&nbsp;</span>
                        <strong>Shift 2:</strong> <code class="text-dark">{{ $shift2Start }} - {{ $shift2End }}</code>
                    </div>
                </div>
                <small class="text-muted italic"><i class="fa-solid fa-circle-info me-1"></i>Ubah jam shift di Pengaturan Attendance.</small>
            </div>

            <div class="table-responsive">
                @if(($mode ?? 'weekly') === 'daily')
                    @php
                        $selectedMonth = (int) $month;
                        $selectedYear = (int) $year;
                    @endphp

                    @foreach($groups as $group)
                        @if(($group['users'] ?? collect())->count() > 0)
                            <div class="mb-3 fw-bold text-primary">
                                {{ $group['label'] }} ({{ ($group['users'] ?? collect())->count() }})
                            </div>

                            @foreach($calendarWeeks as $week)
                                @php
                                    $weekStart = $week['days'][0];
                                    $weekEnd = $week['days'][6];
                                @endphp

                                <div class="small text-muted mb-2">
                                    {{ $weekStart->translatedFormat('d M Y') }} - {{ $weekEnd->translatedFormat('d M Y') }}
                                </div>

                                <table class="table table-hover align-middle border mb-4">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-2" style="width: 220px;">Karyawan</th>
                                            @foreach($week['days'] as $day)
                                                @php $inMonth = ((int) $day->month) === $selectedMonth; @endphp
                                                <th class="py-2 text-center {{ $inMonth ? '' : 'text-muted' }}" style="min-width: 110px;">
                                                    <div class="fw-bold">{{ $day->translatedFormat('D') }}</div>
                                                    <small>{{ $day->translatedFormat('d M') }}</small>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group['users'] as $tech)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $tech->schedule_name ?? $tech->name }}</div>
                                                    <small class="text-muted">
                                                        {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                                        @if(!empty($tech->schedule_department))
                                                            • {{ $tech->schedule_department }}
                                                        @endif
                                                    </small>
                                                </td>
                                                @foreach($week['days'] as $day)
                                                    @php
                                                        $inMonth = ((int) $day->month) === $selectedMonth;
                                                        $dayKey = $day->format('Y-m-d');
                                                        $daySchedules = $dailySchedules->get($dayKey);
                                                        $row = $daySchedules ? $daySchedules->firstWhere('user_id', $tech->id) : null;
                                                        $status = $row?->status ?? 'off';
                                                        $cellClass = $status === 'piket' ? 'bg-success text-white' : ($status === 'backup' ? 'bg-warning text-dark' : 'bg-light text-muted');
                                                    @endphp
                                                    <td class="text-center {{ $inMonth ? '' : 'bg-light text-muted' }}">
                                                        @if(! $inMonth)
                                                            <span class="text-muted">-</span>
                                                        @else
                                                            @if(Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'))
                                                                <form action="{{ route('schedules.daily.store') }}" method="POST">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{ $tech->id }}">
                                                                    <input type="hidden" name="date" value="{{ $dayKey }}">
                                                                    <select name="status"
                                                                            class="form-select form-select-sm fw-bold {{ $cellClass }}"
                                                                            onchange="this.form.submit()">
                                                                        <option value="off" {{ $status == 'off' ? 'selected' : '' }}>OFF</option>
                                                                        <option value="piket" {{ $status == 'piket' ? 'selected' : '' }}>S1</option>
                                                                        <option value="backup" {{ $status == 'backup' ? 'selected' : '' }}>S2</option>
                                                                    </select>
                                                                </form>
                                                            @else
                                                                @if($status == 'piket')
                                                                    <span class="badge bg-success px-3 py-2">Shift 1</span>
                                                                @elseif($status == 'backup')
                                                                    <span class="badge bg-warning text-dark px-3 py-2">Shift 2</span>
                                                                @else
                                                                    <span class="badge bg-light text-muted border px-3 py-2 text-uppercase">Off</span>
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endforeach
                        @endif
                    @endforeach
                @else
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 text-center" style="width: 220px;">Karyawan</th>
                            @foreach($weeksData as $week)
                                <th class="py-3 text-center" style="min-width: 130px;">
                                    <div class="fw-bold text-primary d-flex justify-content-center align-items-center gap-1">
                                        <span>W{{ $week['week_number'] }}</span>
                                        @if(Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'))
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 text-primary"
                                                    title="Ubah rentang tanggal minggu"
                                                    onclick="editPeriod('{{ $year }}', '{{ $week['week_number'] }}', '{{ $week['full_start_date'] }}', '{{ $week['full_end_date'] }}')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $week['week_start_display'] }} - {{ $week['week_end_display'] }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $colspan = 1 + count($weeksData); @endphp
                        @foreach($groups as $group)
                            @if(($group['users'] ?? collect())->count() > 0)
                                <tr class="table-secondary">
                                    <td colspan="{{ $colspan }}" class="fw-bold">
                                        {{ $group['label'] }} ({{ ($group['users'] ?? collect())->count() }})
                                    </td>
                                </tr>
                                @foreach($group['users'] as $tech)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $tech->schedule_name ?? $tech->name }}</div>
                                            <small class="text-muted">
                                                {{ $tech->schedule_position ?? ($tech->role?->label ?? 'Karyawan') }}
                                                @if(!empty($tech->schedule_department))
                                                    • {{ $tech->schedule_department }}
                                                @endif
                                            </small>
                                        </td>
                                        @foreach($weeksData as $week)
                                            @php
                                                $weekSchedules = $schedules->get($week['week_number']);
                                                $schedule = $weekSchedules ? $weekSchedules->where('user_id', $tech->id)->first() : null;
                                                $status = $schedule ? $schedule->status : 'off';
                                            @endphp
                                            <td class="text-center">
                                                @if(Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'))
                                                    <form action="{{ route('schedules.store') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $tech->id }}">
                                                        <input type="hidden" name="week_number" value="{{ $week['week_number'] }}">
                                                        <input type="hidden" name="year" value="{{ $year }}">
                                                        <select name="status"
                                                                class="form-select form-select-sm fw-bold {{ $status == 'piket' ? 'bg-success text-white border-success' : ($status == 'backup' ? 'bg-warning text-dark border-warning' : 'bg-light text-muted') }}"
                                                                onchange="this.form.submit()">
                                                            <option value="off" {{ $status == 'off' ? 'selected' : '' }}>OFF</option>
                                                            <option value="piket" {{ $status == 'piket' ? 'selected' : '' }}>S1</option>
                                                            <option value="backup" {{ $status == 'backup' ? 'selected' : '' }}>S2</option>
                                                        </select>
                                                    </form>
                                                @else
                                                    @if($status == 'piket')
                                                        <span class="badge bg-success px-3 py-2">Shift 1</span>
                                                    @elseif($status == 'backup')
                                                        <span class="badge bg-warning text-dark px-3 py-2">Shift 2</span>
                                                    @else
                                                        <span class="badge bg-light text-muted border px-3 py-2 text-uppercase">Off</span>
                                                    @endif
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

@if(Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'))
    <div class="modal fade" id="autoScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('schedules.autoGenerate') }}" method="POST" class="w-100" onsubmit="return confirm('Generate jadwal otomatis untuk bulan ini? Jadwal minggu pada bulan ini akan ditimpa.')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="modal-content shadow">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Auto Generate Jadwal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-3 small">
                            Pengaturan ini menghitung slot per minggu: berapa orang masuk Shift 1 dan Shift 2. Sisanya otomatis Off (untuk minggu tersebut).
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Slot Shift 1 / Minggu</label>
                                <input type="number" name="shift1_slots" class="form-control" min="1" max="50" value="{{ $autoShift1Slots ?? 1 }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Slot Shift 2 / Minggu</label>
                                <input type="number" name="shift2_slots" class="form-control" min="1" max="50" value="{{ $autoShift2Slots ?? 1 }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark px-4">Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

@if(Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'))
    <div class="modal fade" id="autoDailyScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('schedules.daily.autoGenerate') }}" method="POST" class="w-100" onsubmit="return confirm('Generate jadwal harian otomatis untuk bulan ini? Jadwal harian pada bulan ini akan ditimpa.')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="modal-content shadow">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Auto Generate Jadwal Harian</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-3 small">
                            Sistem akan membagi jadwal harian S1/S2/OFF secara merata dan memastikan tiap orang mendapat jumlah libur yang sama dalam bulan ini.
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Libur per Orang / Bulan</label>
                                <input type="number" name="off_days" class="form-control" min="0" max="10" value="{{ $dailyOffDays ?? 2 }}" required>
                                <div class="form-text">Contoh: 2 berarti tiap orang libur 2 hari dalam bulan tersebut.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark px-4">Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- MODAL TETAP SAMA NAMUN DENGAN STYLE BOOTSTRAP 5 YANG LEBIH BAIK --}}
<div class="modal fade" id="editPeriodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('schedules.updatePeriod') }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="year" id="periodYear">
            <input type="hidden" name="week_number" id="periodWeek">
            <div class="modal-content shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-calendar-pen me-2"></i>{{ __('Ubah Rentang Minggu') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('Tanggal Mulai') }}</label>
                            <input type="date" name="start_date" id="periodStart" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('Tanggal Selesai') }}</label>
                            <input type="date" name="end_date" id="periodEnd" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
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

<style>
    .table th { vertical-align: middle; }
    .form-select-sm { font-size: 0.75rem; padding-top: 0.25rem; padding-bottom: 0.25rem; }
    .badge { letter-spacing: 0.5px; }
    .table-hover tbody tr:hover { background-color: rgba(0,123,255,0.02); }
</style>
@endsection
