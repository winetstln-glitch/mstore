@extends('layouts.app')

@section('title', 'Data Karyawan')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div>
        <h4 class="mb-1 fw-bold text-primary">Data Karyawan</h4>
        <div class="text-muted small">Kelola data master karyawan perusahaan.</div>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('employees.sync') }}" method="POST" onsubmit="return confirm('Sinkronkan data dari teknisi, wash, dan user sekarang?')">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="fa-solid fa-rotate me-1"></i> Sinkronisasi
            </button>
        </form>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Karyawan
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Cari nama, NIK, email, jabatan, departemen">
            </div>
            <div class="col-md-3">
                <select name="department" class="form-select">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dep)
                        <option value="{{ $dep }}" {{ $department === $dep ? 'selected' : '' }}>{{ $dep }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-dark"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-group">
                    <a href="{{ route('employees.print.cards', request()->only(['search','department','status'])) }}" target="_blank" class="btn btn-outline-dark">
                        <i class="fa-solid fa-id-card me-1"></i> Print ID Cards
                    </a>
                    <a href="{{ route('employees.export.pdf', request()->only(['search','department','status'])) }}" class="btn btn-outline-danger">
                        <i class="fa-regular fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ route('employees.export.excel', request()->only(['search','department','status'])) }}" class="btn btn-outline-success">
                        <i class="fa-regular fa-file-excel me-1"></i> Excel
                    </a>
                    <a href="{{ route('employees.export.csv', request()->only(['search','department','status'])) }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-file-csv me-1"></i> CSV
                    </a>
                </div>
            </div>
        </form>

        <form method="GET" action="{{ route('employees.print.cards') }}" target="_blank" id="printSelectedForm">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="department" value="{{ $department }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="d-flex justify-content-end mb-2">
                <button type="submit" class="btn btn-sm btn-outline-dark" id="printSelectedBtn" disabled>
                    <i class="fa-solid fa-id-card me-1"></i> Print Pilihan
                </button>
            </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" class="form-check-input" id="checkAllEmployees">
                        </th>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Jabatan</th>
                        <th>Departemen</th>
                        <th>No HP</th>
                        <th>Status</th>
                        <th>Integrasi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input employee-check" name="selected_ids[]" value="{{ $employee->id }}">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $employee->full_name }}</div>
                                <div class="small text-muted">{{ $employee->email }}</div>
                            </td>
                            <td>{{ $employee->nik }}</td>
                            <td>{{ $employee->position }}</td>
                            <td>{{ $employee->department }}</td>
                            <td>{{ $employee->phone }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">{{ $employee->employment_status }}</span>
                            </td>
                            <td>
                                @if($employee->user_id)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">User</span>
                                @endif
                                @if($employee->wash_employee_id)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Wash</span>
                                @endif
                                @if(!$employee->user_id && !$employee->wash_employee_id)
                                    <span class="text-muted small">Manual</span>
                                @endif
                            </td>
                            <td class="text-end">
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
                            <td colspan="11" class="text-center text-muted py-3">Belum ada data karyawan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </form>

        {{ $employees->links() }}
    </div>
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
