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
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
        <span>Profile Paket Voucher</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#voucherTemplateModal">
            <i class="fa-solid fa-plus me-1"></i>Tambah Paket
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Paket</th>
                        <th>Rate Limit</th>
                        <th>Durasi</th>
                        <th>Quota</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->rate_limit ?: '-' }}</td>
                            <td>{{ $template->duration_seconds ? $template->duration_seconds.' detik' : '-' }}</td>
                            <td>{{ $template->quota_mb ? $template->quota_mb.' MB' : '-' }}</td>
                            <td>Rp {{ number_format((float) $template->price, 0, ',', '.') }}</td>
                            <td>{!! $template->is_active ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>' !!}</td>
                            <td class="text-end">
                                <form action="{{ route('vouchers.templates.delete', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus profile paket ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">Belum ada profile paket voucher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
        <span>Generate Voucher (Bulk)</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#voucherGenerateModal">
            <i class="fa-solid fa-gears me-1"></i>Generate Voucher
        </button>
    </div>
    <div class="card-body">
        <div class="text-muted small">
            Klik tombol <strong>Generate Voucher</strong> untuk membuat voucher massal berdasarkan profile paket atau custom manual.
        </div>
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

<div class="modal fade" id="voucherTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('vouchers.templates.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Profile Paket Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nama Paket</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Voucher 1 Hari" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rate Limit</label>
                            <input type="text" name="rate_limit" class="form-control" placeholder="1M/1M">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Durasi (detik)</label>
                            <input type="number" min="0" name="duration_seconds" class="form-control" placeholder="86400">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quota (MB)</label>
                            <input type="number" min="0" name="quota_mb" class="form-control" placeholder="1024">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Harga</label>
                            <input type="number" min="0" step="0.01" name="price" class="form-control" placeholder="5000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" checked name="is_active" id="template_is_active" value="1">
                                <label class="form-check-label" for="template_is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save me-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="voucherGenerateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('vouchers.generate') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Voucher (Bulk)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Profile Paket Voucher</label>
                            <select name="voucher_template_id" class="form-select">
                                <option value="">Custom Manual</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">
                                        {{ $template->name }} @if($template->rate_limit) - {{ $template->rate_limit }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Rate Limit</label>
                            <input type="text" name="profile" class="form-control" placeholder="1M/1M 2M/2M" value="{{ old('profile','1M/1M') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durasi</label>
                            <input type="text" name="duration" class="form-control" placeholder="1 jam / 1 hari" value="{{ old('duration','1 hari') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quota MB</label>
                            <input type="number" name="quota_mb" min="0" class="form-control" value="{{ old('quota_mb') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Voucher</label>
                            <select name="count" class="form-select">
                                @foreach([100,500,1000] as $num)
                                    <option value="{{ $num }}">{{ $num }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Password</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" checked name="password_same" id="password_same_modal" value="1">
                                <label class="form-check-label" for="password_same_modal">Password sama dengan username</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-gears me-1"></i>Generate</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
