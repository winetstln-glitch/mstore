@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">{{ __('Technician Schedule (2 Shift)') }}</h1>
            <p class="text-muted small mb-0">Manajemen jadwal mingguan teknisi dan shift kerja.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('schedules.index') }}" method="GET" class="d-flex gap-2">
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
                <a href="{{ route('schedules.export.pdf', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-danger">
                    <i class="fa-regular fa-file-pdf me-1"></i> PDF
                </a>
                <a href="{{ route('schedules.export.excel', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-success">
                    <i class="fa-regular fa-file-excel me-1"></i> Excel
                </a>
            </div>
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
                @php
                    $startDate = Carbon\Carbon::createFromDate($year, $month, 1)->startOfWeek();
                    $endDate = Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfWeek();
                    $weeksData = [];
                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addWeek()) {
                        $weekNum = $date->weekOfYear;
                        $period = $periods->get($weekNum);
                        $currentWeekStart = $period ? $period->start_date : $date->copy()->startOfWeek();
                        $currentWeekEnd = $period ? $period->end_date : $date->copy()->endOfWeek();
                        $weeksData[] = [
                            'week_number' => $weekNum,
                            'week_start_display' => $currentWeekStart->translatedFormat('d M'),
                            'week_end_display' => $currentWeekEnd->translatedFormat('d M'),
                            'full_start_date' => $currentWeekStart->format('Y-m-d'),
                            'full_end_date' => $currentWeekEnd->format('Y-m-d'),
                        ];
                    }
                @endphp
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 text-center" style="width: 220px;">Karyawan</th>
                            @foreach($weeksData as $week)
                                <th class="py-3 text-center" style="min-width: 130px;">
                                    <div class="fw-bold text-primary d-flex justify-content-center align-items-center gap-1">
                                        <span>W{{ $week['week_number'] }}</span>
                                        @if(Auth::user()->hasPermission('schedule.manage') || Auth::user()->hasRole('admin'))
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
                        @foreach($technicians as $tech)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $tech->schedule_name ?? $tech->name }}</div>
                                    <small class="text-muted">{{ $tech->position ?? 'Karyawan' }}</small>
                                </td>
                                @foreach($weeksData as $week)
                                    @php
                                        $weekSchedules = $schedules->get($week['week_number']);
                                        $schedule = $weekSchedules ? $weekSchedules->where('user_id', $tech->id)->first() : null;
                                        $status = $schedule ? $schedule->status : 'off';
                                    @endphp
                                    <td class="text-center">
                                        @if(Auth::user()->hasRole('admin'))
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
