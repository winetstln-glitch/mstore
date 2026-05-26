@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fa-solid fa-clipboard-user me-2 text-primary"></i>{{ __('Rekap Absensi Teknisi') }}
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('attendance.daily') }}" class="btn btn-info text-white btn-sm">
                                <i class="fa-solid fa-calendar-day me-1"></i>{{ __('Absensi Harian') }}
                            </a>
                            <a href="{{ route('attendance.kiosk') }}" class="btn btn-dark btn-sm">
                                <i class="fa-solid fa-barcode me-1"></i>Kiosk Barcode
                            </a>
                        </div>
                    </div>
                    
                    <form action="{{ route('attendance.index') }}" method="GET" class="w-100 border-top pt-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Filter Pengguna') }}</label>
                                <select name="user_id" class="form-select form-select-sm js-search-select">
                                    <option value="">{{ __('Semua Staf') }}</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ request('user_id') == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }} ({{ $tech->role->name ?? __('User') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Status') }}</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">{{ __('Semua') }}</option>
                                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>{{ __('Hadir') }}</option>
                                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>{{ __('Terlambat') }}</option>
                                    <option value="leave" {{ request('status') === 'leave' ? 'selected' : '' }}>{{ __('Cuti') }}</option>
                                    <option value="permit" {{ request('status') === 'permit' ? 'selected' : '' }}>{{ __('Izin') }}</option>
                                    <option value="sick" {{ request('status') === 'sick' ? 'selected' : '' }}>{{ __('Sakit') }}</option>
                                    <option value="alpha" {{ request('status') === 'alpha' ? 'selected' : '' }}>{{ __('Alpha') }}</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Bulan') }}</label>
                                <input type="month" name="month" value="{{ request('month') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">{{ __('Tanggal') }}</label>
                                <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fa-solid fa-filter me-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                            @php
                                $user = Auth::user();
                                $isAdmin = $user->hasAnyRole(['admin', 'manager hrd']);
                            @endphp
                            @if($isAdmin)
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal">
                                    <i class="fa-solid fa-plus me-1"></i>{{ __('Tambah Manual') }}
                                </button>
                                <button type="button" class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#salaryAdjustmentModal">
                                    <i class="fa-solid fa-money-bill-transfer me-1"></i>{{ __('Bonus/Kasbon') }}
                                </button>
                            @endif
                            <div class="vr mx-1 d-none d-md-block"></div>
                            <a href="{{ route('attendance.payslip', request()->all()) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fa-solid fa-receipt me-1"></i>{{ __('Slip Gaji') }}
                            </a>
                            <a href="{{ route('attendance.excel', array_merge(request()->all(), ['download' => 'details'])) }}" class="btn btn-outline-success btn-sm" target="_blank">
                                <i class="fa-solid fa-file-excel me-1"></i>{{ __('Download Rincian') }}
                            </a>
                            @if($isAdmin)
                                <div class="ms-auto d-flex gap-2">
                                    <button type="button" class="btn btn-warning text-dark btn-sm fw-bold" onclick="confirmRecapFinance()">
                                        <i class="fa-solid fa-money-bill-wave me-1"></i>{{ __('Catat Gaji') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="submitBulkDelete()">
                                        <i class="fa-regular fa-trash-can me-1"></i>{{ __('Hapus Terpilih') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <!-- Panduan -->
                <div class="alert alert-info py-2 mb-4">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Panduan:</strong> Halaman ini menampilkan <strong>riwayat absensi yang sudah ada</strong>. Untuk melihat <strong>SEMUA karyawan (termasuk yang belum absen)</strong>, klik tombol <strong>"Absensi Harian"</strong> di atas!
                </div>

                <!-- Ringkasan Statistik -->
                <div class="row g-2 mb-4">
                    @foreach(['present' => ['bg' => 'success', 'text' => __('Hadir')], 
                             'late'    => ['bg' => 'warning', 'text' => __('Terlambat')], 
                             'leave'   => ['bg' => 'info', 'text' => __('Cuti')], 
                             'permit'  => ['bg' => 'primary', 'text' => __('Izin')], 
                             'sick'    => ['bg' => 'secondary', 'text' => __('Sakit')], 
                             'alpha'   => ['bg' => 'danger', 'text' => __('Alpha')]] as $key => $theme)
                        <div class="col-4 col-md-2">
                            <div class="p-2 bg-{{ $theme['bg'] }}-subtle border border-{{ $theme['bg'] }} rounded text-center">
                                <div class="h4 mb-0 text-{{ $theme['bg'] }} fw-bold">{{ $stats[$key] ?? 0 }}</div>
                                <div class="small text-{{ $theme['bg'] }}-emphasis fw-bold">{{ $theme['text'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-responsive-mobile">
                        <thead class="table-light">
                            <tr>
                                @if(Auth::user()->hasRole('admin'))
                                    <th class="ps-3" style="width: 40px;">
                                        <input type="checkbox" id="selectAllAttendance" onclick="toggleSelectAll()">
                                    </th>
                                @endif
                                <th>{{ __('Teknisi') }}</th>
                                <th>{{ __('Tanggal') }}</th>
                                <th>{{ __('Jam Masuk') }}</th>
                                <th>{{ __('Jam Pulang') }}</th>
                                <th>{{ __('Lokasi') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th style="width: 90px;">{{ __('Foto') }}</th>
                                @if(Auth::user()->hasRole('admin'))
                                    <th class="text-end pe-3" style="width: 150px;">{{ __('Aksi') }}</th>
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
                                        <div class="mt-1">
                                            @php $shift = $attendance->shift_info; @endphp
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle x-small py-1" title="{{ __('Jadwal Shift') }}">
                                                <i class="fa-solid fa-clock me-1"></i>{{ $shift['start'] }} - {{ $shift['end'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="small text-muted">
                                        {{ ($attendance->work_date ?? $attendance->clock_in)?->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle py-1" style="font-size: 0.85rem; width: fit-content;">
                                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>{{ $attendance->clock_in?->format('H:i') ?? '--:--' }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        @if($attendance->clock_out)
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle py-1" style="font-size: 0.85rem; width: fit-content;">
                                                <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>{{ $attendance->clock_out->format('H:i') }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-3">--:--</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <div class="vstack gap-1">
                                            @if($attendance->lat_clock_in && $attendance->lng_clock_in)
                                                <a href="https://maps.google.com/?q={{ $attendance->lat_clock_in }},{{ $attendance->lng_clock_in }}" target="_blank" class="btn btn-outline-primary btn-xs py-0 px-2" style="font-size: 0.7rem;" title="{{ __('Lokasi Masuk') }}">
                                                    <i class="fa-solid fa-location-dot me-1"></i>{{ __('Masuk') }}
                                                </a>
                                            @endif
                                            @if($attendance->clock_out && $attendance->lat_clock_out && $attendance->lng_clock_out)
                                                <a href="https://maps.google.com/?q={{ $attendance->lat_clock_out }},{{ $attendance->lng_clock_out }}" target="_blank" class="btn btn-outline-info btn-xs py-0 px-2" style="font-size: 0.7rem;" title="{{ __('Lokasi Pulang') }}">
                                                    <i class="fa-solid fa-location-dot me-1"></i>{{ __('Pulang') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($attendance->status) {
                                                'present' => 'bg-success-subtle text-success border border-success-subtle',
                                                'late' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                                'leave', 'permit', 'sick' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                                'alpha' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                                default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ __(ucfirst($attendance->status)) }}
                                        </span>
                                    </td>
                                    <td class="pe-3">
                                        <div class="d-flex gap-1">
                                            @if($attendance->photo_clock_in)
                                                <a href="{{ Storage::url($attendance->photo_clock_in) }}" target="_blank">
                                                    <img src="{{ Storage::url($attendance->photo_clock_in) }}" class="rounded object-fit-cover border shadow-xs" style="width: 32px; height: 32px;" alt="In" loading="lazy">
                                                </a>
                                            @endif
                                            @if($attendance->photo_clock_out)
                                                <a href="{{ Storage::url($attendance->photo_clock_out) }}" target="_blank">
                                                    <img src="{{ Storage::url($attendance->photo_clock_out) }}" class="rounded object-fit-cover border shadow-xs" style="width: 32px; height: 32px;" alt="Out" loading="lazy">
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    @if(Auth::user()->hasRole('admin'))
                                        <td class="text-end pe-3">
                                            <div class="d-inline-flex gap-1">
                                                {{-- Notifikasi WhatsApp --}}
                                                <form method="POST" action="{{ route('attendance.notify', $attendance) }}" class="form-whatsapp">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success text-white btn-sm btn-notify" title="{{ __('Kirim WhatsApp') }}">
                                                        <i class="fa-brands fa-whatsapp"></i> <span class="d-none d-sm-inline ms-1">{{ __('Notifikasi') }}</span>
                                                    </button>
                                                </form>
                                                {{-- Single Delete dengan Trigger Class SweetAlert global --}}
                                                <form method="POST" action="{{ route('attendance.destroy', $attendance->id) }}" class="form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('Hapus') }}">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->hasRole('admin') ? 9 : 7 }}" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open d-block fs-3 mb-2 text-secondary"></i>
                                        {{ __('Tidak ada data absensi.') }}
                                    </td>
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

<!-- Modal absensi manual -->
<div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">{{ __('Tambah Absensi / Cuti') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('attendance.storeManual') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Pengguna') }} (Total: {{ $technicians->count() }})</label>
                        <select name="user_id" class="form-select js-search-select-modal" required>
                            <option value="">{{ __('Pilih Pengguna') }}</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }} ({{ $tech->role->name ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Status') }}</label>
                        <select name="status" class="form-select" required>
                            <option value="present">{{ __('Hadir') }}</option>
                            <option value="leave">{{ __('Cuti') }}</option>
                            <option value="permit">{{ __('Izin') }}</option>
                            <option value="sick">{{ __('Sakit') }}</option>
                            <option value="late">{{ __('Terlambat') }}</option>
                            <option value="alpha">{{ __('Alpha (Tanpa Keterangan)') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Catatan') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Data') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal bonus / kasbon -->
<div class="modal fade" id="salaryAdjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">{{ __('Add Bonus / Kasbon') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('salary-adjustments.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Pengguna') }} (Total: {{ $technicians->count() }})</label>
                        <select name="user_id" class="form-select js-search-select-modal" required>
                            <option value="">{{ __('Pilih Pengguna') }}</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }} ({{ $tech->role->name ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Tipe') }}</label>
                        <select name="type" id="adjustment_type" class="form-select" required onchange="updateAdjustmentCategories()">
                            <option value="bonus">{{ __('Bonus') }}</option>
                            <option value="kasbon">{{ __('Kasbon') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Kategori') }}</label>
                        <select name="category" id="adjustment_category" class="form-select" required onchange="updateAdjustmentDescription()">
                            <option value="disiplin">Bonus Disiplin</option>
                            <option value="tanggung jawab">Bonus Tanggung Jawab</option>
                            <option value="absensi">Bonus Absensi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Jumlah') }}</label>
                        <input type="number" name="amount" class="form-control" required min="0" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Deskripsi') }}</label>
                        <textarea name="description" id="adjustment_description" class="form-control" rows="2" readonly>Bonus Disiplin</textarea>
                        <small class="text-muted italic">Keterangan otomatis terisi berdasarkan kategori untuk rincian slip gaji.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
@endsection

{{-- Push Select2 & Custom Scripts ke Bottom Layout --}}
@if(Auth::user()->hasRole('admin'))
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Inisialisasi Select2 untuk Input Filter normal
            $('.js-search-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // 2. Inisialisasi Select2 di dalam Modal
            $('.js-search-select-modal').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('.modal.show')
            });

            // Re-initialize Select2 when modal opens
            $('#manualAttendanceModal').on('shown.bs.modal', function () {
                $('.js-search-select-modal').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#manualAttendanceModal')
                });
            });
            $('#salaryAdjustmentModal').on('shown.bs.modal', function () {
                $('.js-search-select-modal').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#salaryAdjustmentModal')
                });
            });
            
            // 3. Tangani Interupsi Hapus Single dengan SweetAlert
            document.querySelectorAll('.form-delete').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '{{ __('Apakah Anda yakin?') }}',
                        text: '{{ __('Data absensi yang dihapus tidak dapat dikembalikan!') }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '{{ __('Ya, Hapus!') }}',
                        cancelButtonText: '{{ __('Batal') }}',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // 4. Tangani Interupsi WhatsApp dengan SweetAlert
            document.querySelectorAll('.form-whatsapp').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '{{ __('Kirim Notifikasi WhatsApp?') }}',
                        text: '{{ __('Sistem akan mengirimkan ringkasan absensi ini ke nomor teknisi terkait.') }}',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754', // Warna sukses hijau WA
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '{{ __('Kirim') }}',
                        cancelButtonText: '{{ __('Batal') }}',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });

        function confirmRecapFinance() {
            Swal.fire({
                title: '{{ __('Catat Pengeluaran Gaji?') }}',
                text: '{{ __('Ini akan membuat transaksi pengeluaran di Keuangan berdasarkan filter saat ini.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('Ya, Catat Sekarang!') }}',
                cancelButtonText: '{{ __('Batal') }}'
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
                    title: '{{ __('Peringatan') }}',
                    text: '{{ __('Tidak ada data absensi yang dipilih.') }}'
                });
                return;
            }
            Swal.fire({
                title: '{{ __('Yakin ingin menghapus data terpilih?') }}',
                text: '{{ __('Semua data absensi yang dicentang akan dihapus permanen!') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Ya, Hapus Semuanya!') }}',
                cancelButtonText: '{{ __('Batal') }}'
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

        function updateAdjustmentCategories() {
            const type = document.getElementById('adjustment_type').value;
            const categorySelect = document.getElementById('adjustment_category');
            
            categorySelect.innerHTML = '';
            
            if (type === 'bonus') {
                const options = [
                    { value: 'disiplin', text: 'Bonus Disiplin' },
                    { value: 'tanggung jawab', text: 'Bonus Tanggung Jawab' },
                    { value: 'absensi', text: 'Bonus Absensi' }
                ];
                options.forEach(opt => {
                    const el = document.createElement('option');
                    el.value = opt.value;
                    el.textContent = opt.text;
                    categorySelect.appendChild(el);
                });
            } else if (type === 'kasbon') {
                const options = [
                    { value: 'bon kantor', text: 'Bon Kantor' },
                    { value: 'bon warung', text: 'Bon Warung' }
                ];
                options.forEach(opt => {
                    const el = document.createElement('option');
                    el.value = opt.value;
                    el.textContent = opt.text;
                    categorySelect.appendChild(el);
                });
            }
            
            updateAdjustmentDescription();
        }

        function updateAdjustmentDescription() {
            const categorySelect = document.getElementById('adjustment_category');
            const descriptionTextarea = document.getElementById('adjustment_description');
            
            // Tambahkan validasi defensif (mencegah error bila seleksi belum siap)
            if (categorySelect && categorySelect.options && categorySelect.selectedIndex > -1) {
                const selectedText = categorySelect.options[categorySelect.selectedIndex].text;
                descriptionTextarea.value = selectedText;
            } else {
                descriptionTextarea.value = '';
            }
        }
        </script>
    @endpush
@endif
