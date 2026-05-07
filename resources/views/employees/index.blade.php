@extends('layouts.app')

@section('title', 'Data Karyawan')

@section('content')
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="card-body p-3">
        {{-- Row 1: Judul & Utama --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-users text-primary fa-lg"></i>
                <div>
                    <h5 class="mb-0 fw-bold">Data Karyawan</h5>
                    <div class="text-muted x-small">Kelola data master dan ID Card karyawan.</div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <form action="{{ route('employees.sync') }}" method="POST" onsubmit="return confirm('Sinkronkan data sekarang?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm">
                        <i class="fa-solid fa-rotate me-1"></i> Sinkron
                    </button>
                </form>
                <a href="{{ route('employees.create') }}" class="btn btn-sm btn-primary shadow-sm px-3">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Baru
                </a>
            </div>
        </div>

        <hr class="my-3 opacity-10">

        {{-- Row 2: Filter & Search --}}
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="{{ $search }}" placeholder="Cari nama, NIK, jabatan...">
                </div>
            </div>
            <div class="col-md-2">
                <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Dept</option>
                    @foreach($departments as $dep)
                        <option value="{{ $dep }}" {{ $department === $dep ? 'selected' : '' }}>{{ $dep }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark px-3">Filter</button>
            </div>

            {{-- Row 2 Right: Export Actions --}}
            <div class="col-auto ms-auto d-flex gap-1">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-file-export me-1"></i> Ekspor
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item py-2" href="{{ route('employees.export.pdf', request()->only(['search','department','status'])) }}"><i class="fa-regular fa-file-pdf me-2 text-danger"></i> PDF</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('employees.export.excel', request()->only(['search','department','status'])) }}"><i class="fa-regular fa-file-excel me-2 text-success"></i> Excel</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('employees.export.csv', request()->only(['search','department','status'])) }}"><i class="fa-solid fa-file-csv me-2 text-secondary"></i> CSV</a></li>
                    </ul>
                </div>
                <a href="{{ route('employees.print.cards', request()->only(['search','department','status'])) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                    <i class="fa-solid fa-print me-1"></i> ID Cards
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">Daftar Karyawan</h6>
        <form method="GET" action="{{ route('employees.print.cards') }}" target="_blank" id="printSelectedForm" class="m-0">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="department" value="{{ $department }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <button type="submit" class="btn btn-xs btn-outline-dark" id="printSelectedBtn" disabled>
                <i class="fa-solid fa-id-card me-1"></i> Print Pilihan
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light-subtle">
                    <tr class="small text-uppercase text-muted">
                        <th class="ps-3" style="width:40px;">
                            <input type="checkbox" class="form-check-input" id="checkAllEmployees">
                        </th>
                        <th>Karyawan</th>
                        <th>NIK</th>
                        <th>Posisi / Dept</th>
                        <th>No HP</th>
                        <th>Status</th>
                        <th>Integrasi</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td class="ps-3">
                                <input type="checkbox" class="form-check-input employee-check" name="selected_ids[]" value="{{ $employee->id }}" form="printSelectedForm">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($employee->id_card_photo_path)
                                        <img src="{{ asset('storage/'.$employee->id_card_photo_path) }}" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border text-muted" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-user x-small"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-dark mb-0">{{ $employee->full_name }}</div>
                                        <div class="x-small text-muted">{{ $employee->user->username ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small fw-medium">{{ $employee->nik }}</td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $employee->position }}</div>
                                <div class="x-small text-muted">{{ $employee->department }}</div>
                            </td>
                            <td class="small">{{ $employee->phone }}</td>
                            <td>
                                <span class="badge {{ $employee->employment_status === 'Tetap' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} x-small px-2">
                                    {{ $employee->employment_status }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($employee->user_id)
                                        <span class="badge bg-primary-subtle text-primary x-small border border-primary-subtle" title="User Account: {{ $employee->user->username }}">User</span>
                                    @endif
                                    @if($employee->wash_employee_id)
                                        <span class="badge bg-info-subtle text-info x-small border border-info-subtle">Wash</span>
                                    @endif
                                    @if(!$employee->user_id && !$employee->wash_employee_id)
                                        <span class="text-muted x-small italic">Manual</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('employees.id-card', $employee) }}" class="btn btn-sm btn-outline-dark" title="ID Card">
                                        <i class="fa-solid fa-id-card"></i>
                                    </a>
                                    @if($employee->document_path)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ asset('storage/'.$employee->document_path) }}" target="_blank" title="Dokumen">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-user-slash fa-2x opacity-25 d-block mb-2"></i>
                                Belum ada data karyawan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
        <div class="card-footer bg-white py-2 border-0">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAllEmployees');
    const checks = Array.from(document.querySelectorAll('.employee-check'));
    const printBtn = document.getElementById('printSelectedBtn');

    function refreshPrintButton() {
        const selectedCount = checks.filter((el) => el.checked).length;
        printBtn.disabled = selectedCount === 0;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checks.forEach((el) => { el.checked = checkAll.checked; });
            refreshPrintButton();
        });
    }

    checks.forEach((el) => {
        el.addEventListener('change', function () {
            if (!el.checked && checkAll) {
                checkAll.checked = false;
            } else if (checkAll) {
                checkAll.checked = checks.length > 0 && checks.every((node) => node.checked);
            }
            refreshPrintButton();
        });
    });

    refreshPrintButton();
});
</script>
@endpush
