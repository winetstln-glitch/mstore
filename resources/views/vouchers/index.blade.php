@extends('layouts.app')

@section('title', 'Voucher Hotspot')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div>
        <h4 class="mb-1 fw-bold text-primary">Voucher Hotspot</h4>
        <div class="small text-muted">Bulk generate voucher terintegrasi FreeRADIUS & MikroTik.</div>
    </div>
    <div class="btn-group">
        <a href="{{ route('vouchers.export.pdf', request()->query()) }}" class="btn btn-outline-danger"><i class="fa-regular fa-file-pdf me-1"></i>PDF</a>
        <a href="{{ route('vouchers.export.excel', request()->query()) }}" class="btn btn-outline-success"><i class="fa-regular fa-file-excel me-1"></i>Excel</a>
        <a href="{{ route('vouchers.export.csv', request()->query()) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-file-csv me-1"></i>CSV</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light fw-semibold">Generate Voucher (Bulk)</div>
    <div class="card-body">
        <form action="{{ route('vouchers.generate') }}" method="POST" class="row g-2">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Profile Rate Limit</label>
                <input type="text" name="profile" class="form-control" placeholder="1M/1M 2M/2M" value="{{ old('profile','1M/1M') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Durasi</label>
                <input type="text" name="duration" class="form-control" placeholder="1 jam / 1 hari" value="{{ old('duration','1 hari') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Quota MB</label>
                <input type="number" name="quota_mb" min="0" class="form-control" value="{{ old('quota_mb') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Jumlah</label>
                <select name="count" class="form-select">
                    @foreach([100,500,1000] as $num)
                        <option value="{{ $num }}">{{ $num }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label d-block">Password</label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" checked name="password_same" id="password_same" value="1">
                    <label class="form-check-label" for="password_same">Sama username</label>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="fa-solid fa-gears me-1"></i>Generate</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari username voucher" value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach(['unused','used','expired'] as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-dark"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Profile</th>
                        <th>Durasi</th>
                        <th>Quota</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->username }}</td>
                            <td>{{ $voucher->password }}</td>
                            <td>{{ $voucher->profile ?: '-' }}</td>
                            <td>{{ $voucher->duration_seconds ? $voucher->duration_seconds.' detik' : '-' }}</td>
                            <td>{{ $voucher->quota_mb ? $voucher->quota_mb.' MB' : '-' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($voucher->status) }}</span></td>
                            <td class="text-end">
                                <form action="{{ route('vouchers.disconnect') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="username" value="{{ $voucher->username }}">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-plug-circle-xmark"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">Belum ada voucher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $vouchers->links() }}
    </div>
</div>
@endsection
