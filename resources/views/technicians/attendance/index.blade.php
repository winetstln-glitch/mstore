@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <div class="d-flex flex-column gap-3">
                    <h5 class="mb-0 fw-bold">{{ __('Rekap Absensi Teknisi') }}</h5>
                    
                    <form action="{{ route('attendance.index') }}" method="GET" class="w-100">
                        <div class="row g-2">
                            <div class="col-12 col-md-auto">
                                <select name="user_id" class="form-select" data-bs-toggle="tooltip" title="{{ __('Semua Staf (Teknisi & Admin)') }}">
                                    <option value="">{{ __('Semua Staf (Teknisi & Admin)') }}</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ request('user_id') == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }} ({{ $tech->role->name ?? __('Pengguna') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-6 col-md-auto">
                                <input type="month" name="month" value="{{ request('month') }}" class="form-control" placeholder="{{ __('Bulan') }}" data-bs-toggle="tooltip" title="{{ __('Bulan') }}">
                            </div>
                            <div class="col-6 col-md-auto">
                                <input type="date" name="date" value="{{ request('date') }}" class="form-control" placeholder="{{ __('Tanggal') }}" data-bs-toggle="tooltip" title="{{ __('Tanggal') }}">
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <a href="{{ route('attendance.daily') }}" class="btn btn-info text-white" data-bs-toggle="tooltip" title="{{ __('Absensi Harian') }}">
                                    <i class="fa-solid fa-calendar-day"></i> <span class="d-none d-sm-inline ms-1">{{ __('Absensi Harian') }}</span>
                                </a>
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="{{ __('Terapkan') }}"><i class="fa-solid fa-filter"></i> <span class="d-none d-sm-inline ms-1">{{ __('Terapkan') }}</span></button>
                                <a href="{{ route('attendance.kiosk') }}" class="btn btn-dark" data-bs-toggle="tooltip" title="Kiosk Barcode">
                                    <i class="fa-solid fa-barcode"></i> <span class="d-none d-sm-inline ms-1">Kiosk Barcode</span>
                                </a>
                                @if(Auth::user()->hasRole('admin'))
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal" data-bs-toggle="tooltip" title="{{ __('Tambah') }}">
                                    <i class="fa-solid fa-plus"></i> <span class="d-none d-sm-inline ms-1">{{ __('Tambah') }}</span>
                                </button>
                                <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#salaryAdjustmentModal" data-bs-toggle="tooltip" title="{{ __('Bonus/Kasbon') }}">
                                    <i class="fa-solid fa-money-bill-transfer"></i> <span class="d-none d-sm-inline ms-1">{{ __('Bonus/Kasbon') }}</span>
                                </button>
                                @endif
                                <a href="{{ route('attendance.pdf', request()->all()) }}" class="btn btn-danger" target="_blank" data-bs-toggle="tooltip" title="{{ __('PDF') }}">
                                    <i class="fa-solid fa-file-pdf"></i> <span class="d-none d-sm-inline ms-1">{{ __('PDF') }}</span>
                                </a>
                                <a href="{{ route('attendance.excel', request()->all()) }}" class="btn btn-success" target="_blank" data-bs-toggle="tooltip" title="{{ __('Excel') }}">
                                    <i class="fa-solid fa-file-excel"></i> <span class="d-none d-sm-inline ms-1">{{ __('Excel') }}</span>
                                </a>
                                @if(Auth::user()->hasRole('admin'))
                                <button type="button" class="btn btn-warning text-dark" onclick="confirmRecapFinance()" data-bs-toggle="tooltip" title="{{ __('Catat Gaji') }}">
                                    <i class="fa-solid fa-money-bill-wave"></i> <span class="d-none d-sm-inline ms-1">{{ __('Catat Gaji') }}</span>
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="submitBulkDelete()" data-bs-toggle="tooltip" title="{{ __('Hapus Terpilih') }}">
                                    <i class="fa-regular fa-trash-can"></i> <span class="d-none d-sm-inline ms-1">{{ __('Hapus Terpilih') }}</span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <!-- Ringkasan -->
                <div class="row g-2 mb-4">
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-success-subtle border border-success rounded text-center">
                            <div class="h4 mb-0 text-success fw-bold">{{ $stats['present'] }}</div>
                            <div class="small text-success-emphasis fw-bold">{{ __('Hadir') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-warning-subtle border border-warning rounded text-center">
                            <div class="h4 mb-0 text-warning fw-bold">{{ $stats['late'] }}</div>
                            <div class="small text-warning-emphasis fw-bold">{{ __('Terlambat') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-info-subtle border border-info rounded text-center">
                            <div class="h4 mb-0 text-info fw-bold">{{ $stats['leave'] }}</div>
                            <div class="small text-info-emphasis fw-bold">{{ __('Cuti') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-primary-subtle border border-primary rounded text-center">
                            <div class="h4 mb-0 text-primary fw-bold">{{ $stats['permit'] }}</div>
                            <div class="small text-primary-emphasis fw-bold">{{ __('Izin') }}</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="p-2 bg-secondary-subtle border border-secondary rounded text-center">
                            <div class="h4 mb-0 text-secondary fw-bold">{{ $stats['sick'] }}</div>
                            <div class="small text-secondary-emphasis fw-bold">{{ __('Sakit') }}</div>
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
                    <table class="table table-hover align-middle table-responsive-mobile">
                        <thead class="table-light">
                            <tr>
                                @if(Auth::user()->hasRole('admin'))
                                <th class="ps-3">
                                    <input type="checkbox" id="selectAllAttendance" onclick="toggleSelectAll()">
                                </th>
                                @endif
                                <th>{{ __('Teknisi') }}</th>
                                <th>{{ __('Tanggal') }}</th>
                                <th>{{ __('Jam Masuk') }}</th>
                                <th>{{ __('Jam Pulang') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="pe-3">{{ __('Foto') }}</th>
                                @if(Auth::user()->hasRole('admin'))
                                <th class="text-end pe-3">{{ __('Aksi') }}</th>
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
                                                <button type="submit" class="btn btn-success text-white btn-sm" title="{{ __('Send WhatsApp Notification') }}" onclick="return confirm('{{ __('Send WhatsApp notification?') }}')" data-bs-toggle="tooltip">
                                                    <i class="fa-brands fa-whatsapp"></i> <span class="d-none d-sm-inline ms-1">{{ __('Notifikasi') }}</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('attendance.destroy', $attendance->id) }}" onsubmit="return confirm('{{ __('Yakin ingin menghapus data ini?') }}')">
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
                                    <td colspan="{{ Auth::user()->hasRole('admin') ? 8 : 6 }}" class="text-center py-4 text-muted">{{ __('Tidak ada data absensi.') }}</td>
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
                        <label class="form-label fw-medium">{{ __('Pengguna') }}</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">{{ __('Pilih Pengguna') }}</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
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
                        <label class="form-label fw-medium">{{ __('Pengguna') }}</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">{{ __('Pilih Pengguna') }}</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Tipe') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="bonus">{{ __('Bonus') }}</option>
                            <option value="kasbon">{{ __('Kasbon') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Jumlah') }}</label>
                        <input type="number" name="amount" class="form-control" required min="0" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Deskripsi') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
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
            title: '{{ __('Tidak ada data absensi yang dipilih.') }}'
        });
        return;
    }
    Swal.fire({
        title: '{{ __('Yakin ingin menghapus data ini?') }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __('Ya, Hapus') }}',
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
</script>
@endif

@endsection
