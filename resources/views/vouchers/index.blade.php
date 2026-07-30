@extends('layouts.app')

@section('title', 'Voucher Hotspot')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div>
        <h4 class="mb-1 fw-bold text-primary">Voucher Hotspot</h4>
        <div class="small text-muted">Bulk generate voucher terintegrasi FreeRADIUS &amp; MikroTik.</div>
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

<div class="alert alert-info shadow-sm border-0 d-flex gap-2 align-items-start">
    <i class="fa-solid fa-circle-info text-info fs-4 mt-1"></i>
    <div>
        <div class="fw-semibold">Master Paket Voucher dipindah!</div>
        <div class="small text-muted">
            Data paket voucher sekarang dikelola terpusat di menu
            <a href="{{ route('hotspot.profiles.index', ['type' => 'voucher']) }}" class="alert-link fw-semibold">Paket Internet &rarr; Tab Voucher</a>.
            Gunakan fitur Generate Voucher di bawah ini untuk mencetak stok voucher berdasarkan paket yang sudah dibuat di sana.
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
        <span>Daftar Paket Voucher (Aktif)</span>
        <a href="{{ route('hotspot.profiles.index', ['type' => 'voucher']) }}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Kelola Paket
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Paket</th>
                        <th>MikroTik Profile</th>
                        <th>Speed</th>
                        <th>Durasi</th>
                        <th>Quota</th>
                        <th>User/Device</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hotspotProfiles as $p)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($p->color_badge)
                                        <span class="d-inline-block rounded" style="width:12px;height:12px;background:{{ in_array($p->color_badge,['green','lime']) ? '#28a745' : ($p->color_badge==='blue' ? '#007bff' : ($p->color_badge==='orange' ? '#fd7e14' : ($p->color_badge==='gold' ? '#d4a017' : ($p->color_badge==='purple' ? '#6f42c1' : ($p->color_badge==='gray'||$p->color_badge==='silver'?'#adb5bd':'#6c757d'))))) }};"></span>
                                    @endif
                                    <span class="fw-semibold">{{ $p->name }}</span>
                                    @if($p->sort_order)<small class="text-muted ms-1">#{{ $p->sort_order }}</small>@endif
                                </div>
                            </td>
                            <td><code class="small">{{ $p->mikrotik_profile_name }}</code></td>
                            <td>@if($p->rate_limit_mbps)<b>{{ $p->rate_limit_mbps }} Mbps</b>@else<span class="text-muted">-</span>@endif</td>
                            <td>{{ $p->formatted_uptime }}</td>
                            <td>
                                @if($p->quota_mb)
                                    {{ $p->quota_mb >= 1024 ? round($p->quota_mb/1024,1).' GB' : $p->quota_mb.' MB' }}
                                @else
                                    <span class="badge bg-info text-white">Unlimited</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $p->shared_users > 1 ? 'bg-primary' : 'bg-secondary' }}">{{ $p->shared_users ?? 1 }} device</span></td>
                            <td><b class="text-primary">{{ $p->formatted_price }}</b></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            <i class="fa-solid fa-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                            Belum ada paket voucher aktif.
                            <div class="mt-2"><a href="{{ route('hotspot.profiles.create') }}" class="btn btn-sm btn-primary">Buat Paket Voucher Pertama</a></div>
                        </td></tr>
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
            Klik tombol <strong>Generate Voucher</strong> untuk membuat voucher massal berdasarkan paket dari master Paket Internet (Voucher) atau custom manual.
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
                    @foreach(['unused','used','expired','sold'] as $st)
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
                            <td>{{ $voucher->duration_seconds ? format_duration($voucher->duration_seconds) : '-' }}</td>
                            <td>{{ $voucher->quota_mb ? $voucher->quota_mb.' MB' : '-' }}</td>
                            <td>
                                @php
                                    $statusBadge = [
                                        'unused'    => 'bg-success-subtle text-success',
                                        'sold'      => 'bg-info-subtle text-info',
                                        'used'      => 'bg-warning-subtle text-warning',
                                        'expired'   => 'bg-secondary-subtle text-secondary',
                                    ];
                                    $badgeClass = $statusBadge[$voucher->status] ?? 'bg-secondary-subtle text-secondary';
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ strtoupper($voucher->status) }}</span>
                            </td>
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
                            <label class="form-label">Paket Voucher (dari Master Paket Internet)</label>
                            <select name="hotspot_profile_id" id="gen_hotspot_profile" class="form-select" onchange="autoFillFromProfile(this.value)">
                                <option value="">Custom Manual</option>
                                @foreach($hotspotProfiles as $p)
                                    <option value="{{ $p->id }}"
                                        data-profile="{{ $p->mikrotik_profile_name }}"
                                        data-duration="{{ $p->formatted_uptime }}"
                                        data-quota="{{ $p->quota_mb }}"
                                        data-rate="{{ $p->rate_limit_mbps }}">
                                        {{ $p->name }} — {{ $p->formatted_price }}
                                        @if($p->rate_limit_mbps) — {{ $p->rate_limit_mbps }} Mbps @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Pilih paket untuk otomatis mengisi Rate Limit, Durasi, dan Quota di bawah ini.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">MikroTik Rate Limit / Profile Name</label>
                            <input type="text" name="profile" id="gen_profile" class="form-control" placeholder="Contoh: 1M/1M  atau nama profile">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durasi</label>
                            <input type="text" name="duration" id="gen_duration" class="form-control" placeholder="1 jam / 1 hari / 30 menit">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quota MB</label>
                            <input type="number" name="quota_mb" id="gen_quota_mb" min="0" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Voucher</label>
                            <select name="count" class="form-select" required>
                                @foreach([1,10,25,50,100,250,500,1000,2000] as $num)
                                    <option value="{{ $num }}" {{ $num === 100 ? 'selected' : '' }}>{{ $num }}</option>
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

<script>
const profileData = {!! $profileDataJson !!};

function autoFillFromProfile(val) {
    if (!val || !profileData || !profileData[val]) {
        document.getElementById('gen_profile').value = '';
        document.getElementById('gen_duration').value = '';
        document.getElementById('gen_quota_mb').value = '';
        return;
    }
    const d = profileData[val];
    document.getElementById('gen_profile').value = d.mikrotik_profile_name || '';
    document.getElementById('gen_duration').value = d.formatted_uptime && d.formatted_uptime !== 'Unlimited' ? d.formatted_uptime : '';
    document.getElementById('gen_quota_mb').value = d.quota_mb !== null && d.quota_mb !== undefined ? d.quota_mb : '';
}
</script>
@endsection
