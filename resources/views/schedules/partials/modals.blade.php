{{-- ============================================ 
     MODALS 
     ============================================ --}}
@php $canManage = Auth::user()->hasRole('admin') || Auth::user()->hasPermission('schedule.manage'); @endphp

@if($canManage)
    {{-- Import Schedule Modal --}}
    <div class="modal fade" id="importScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="{{ route('schedules.import.excel') }}" method="POST" 
                  enctype="multipart/form-data" class="w-100">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="mode" value="{{ $mode ?? 'weekly' }}">
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
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
                        
                        <div class="row g-4 mb-4">
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

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Pilih Karyawan (Opsional)</label>
                            <p class="text-muted x-small mb-2">Kosongkan jika ingin generate untuk SEMUA karyawan di grup yang tampil.</p>
                            <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                @foreach($groups as $group)
                                    <div class="mb-2">
                                        <div class="fw-bold small text-primary mb-1 border-bottom">{{ $group['label'] }}</div>
                                        <div class="row g-2">
                                            @foreach($group['users'] as $u)
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="user_w_{{ $u->id }}">
                                                        <label class="form-check-label x-small" for="user_w_{{ $u->id }}">
                                                            {{ $u->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
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
                        
                        <div class="d-flex justify-content-center mb-4">
                            <div class="slot-config" style="width: 100%; max-width: 400px;">
                                <div class="slot-icon bg-secondary">
                                    <i class="fa-solid fa-bed"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold mb-1 small">Libur per Orang / Bulan</label>
                                    <input type="number" name="off_days" class="form-control form-control-lg text-center" 
                                           min="0" max="10" value="{{ $dailyOffDays ?? 2 }}" required>
                                    <div class="form-text text-center x-small">hari libur</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Pilih Karyawan (Opsional)</label>
                            <p class="text-muted x-small mb-2">Kosongkan jika ingin generate untuk SEMUA karyawan di grup yang tampil.</p>
                            <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                @foreach($groups as $group)
                                    <div class="mb-2">
                                        <div class="fw-bold small text-primary mb-1 border-bottom">{{ $group['label'] }}</div>
                                        <div class="row g-2">
                                            @foreach($group['users'] as $u)
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="user_d_{{ $u->id }}">
                                                        <label class="form-check-label x-small" for="user_d_{{ $u->id }}">
                                                            {{ $u->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
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