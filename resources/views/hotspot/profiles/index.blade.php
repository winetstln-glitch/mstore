@extends('layouts.app')

@section('title', __('Paket Internet'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">
            <i class="fa-solid fa-box-open me-2 text-primary"></i>
            Paket Internet
        </h4>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('hotspot.profiles.index') }}" class="d-flex gap-2 align-items-center">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Tipe</option>
                    <option value="pppoe" @selected(in_array(request('type'), ['pppoe','paketb','rumahan','residential','home','bisnis'], true))>PPPoE Rumahan/Bisnis</option>
                    <option value="voucher" @selected(request('type')==='voucher')>Voucher</option>
                    <option value="member" @selected(in_array(request('type'), ['member','membership','hotspot'], true))>Member Hotspot</option>
                </select>
            </form>
            <a href="{{ route('hotspot.profiles.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Tambah Paket
            </a>
        </div>
    </div>

    @php
        $activeFilter = request('type');
        $countPppoe = \App\Models\HotspotProfile::query()->paketb()->count();
        $countVoucher = \App\Models\HotspotProfile::query()->vouchers()->count();
        $countMember = \App\Models\HotspotProfile::query()->memberships()->count();
        $countAll = $countPppoe + $countVoucher  + $countMember;
    @endphp
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <div class="d-flex flex-wrap gap-2" role="tablist">
                @php
                    $tabs = [
                        ['val' => '',          'label' => 'Semua',       'count' => $countAll,    'icon' => 'fa-solid fa-layer-group',   'badge' => 'bg-secondary'],
                        ['val' => 'pppoe',     'label' => 'PPPoE Rumahan/Bisnis', 'count' => $countPppoe,  'icon' => 'fa-solid fa-network-wired', 'badge' => 'bg-primary'],
                        ['val' => 'voucher',   'label' => 'Voucher',     'count' => $countVoucher,'icon' => 'fa-solid fa-ticket',        'badge' => 'bg-success'],
                        ['val' => 'member',    'label' => 'Member Hotspot', 'count' => $countMember, 'icon' => 'fa-solid fa-id-badge',      'badge' => 'bg-purple text-white'],
                    ];
                    $baseUrl = route('hotspot.profiles.index');
                @endphp
                @foreach($tabs as $tab)
                    @php
                        $isActive = $activeFilter === $tab['val'] || ($tab['val'] === '' && !$activeFilter);
                        if ($tab['val'] === 'pppoe') {
                            $isActive = in_array($activeFilter, ['pppoe','paketb','rumahan','residential','home','bisnis'], true);
                        } elseif ($tab['val'] === 'member') {
                            $isActive = in_array($activeFilter, ['member','membership','hotspot'], true);
                        }
                        $href = $tab['val'] === '' ? $baseUrl : $baseUrl . '?type=' . $tab['val'];
                    @endphp
                    <a href="{{ $href }}" class="btn btn-sm {{ $isActive ? 'btn-dark' : 'btn-outline-secondary' }} d-flex align-items-center gap-2 border-0">
                        <i class="{{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="badge {{ $tab['badge'] }} rounded-pill" style="font-size:0.7rem">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm border-top border-4 border-primary">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nama Paket</th>
                            <th>Tipe</th>
                            <th>RouterOS Profile</th>
                            <th>Harga</th>
                            <th>Speed</th>
                            <th>Durasi</th>
                            <th>Masa Aktif</th>
                            <th>Quota</th>
                            <th>User</th>
                            <th>Router</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packages as $idx => $p)
                            <tr>
                                <td>{{ $packages->firstItem() + $idx }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($p->color_badge)
                                            <span class="d-inline-block rounded"
                                                  style="width:14px;height:14px;background:{{ in_array($p->color_badge,['green','lime']) ? '#28a745' : ($p->color_badge==='blue' ? '#007bff' : ($p->color_badge==='orange' ? '#fd7e14' : ($p->color_badge==='gold' ? '#d4a017' : ($p->color_badge==='purple' ? '#6f42c1' : ($p->color_badge==='gray'||$p->color_badge==='silver'?'#adb5bd':'#6c757d'))))) }};"></span>
                                        @endif
                                        <div>
                                            <b>{{ $p->name }}</b>
                                            @if($p->sort_order)
                                                <small class="text-muted ms-2">order #{{ $p->sort_order }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    @if($p->description)
                                        <small class="text-muted d-block mt-1">{{ Str::limit($p->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $typeMap = [
                                            'pppoe' => ['label' => 'PPPoE Rumahan/Bisnis', 'bg' => 'bg-primary'],
                                            'voucher' => ['label' => 'Voucher', 'bg' => 'bg-success'],
                                            'member' => ['label' => 'Member', 'bg' => 'bg-purple text-white'],
                                            'membership' => ['label' => 'Member', 'bg' => 'bg-purple text-white'],
                                            'hotspot' => ['label' => 'Member', 'bg' => 'bg-purple text-white'],
                                            'residential' => ['label' => 'PPPoE Rumahan/Bisnis', 'bg' => 'bg-primary'],
                                            'home' => ['label' => 'PPPoE Rumahan/Bisnis', 'bg' => 'bg-primary'],
                                            'rumahan' => ['label' => 'PPPoE Rumahan/Bisnis', 'bg' => 'bg-primary'],
                                        ];
                                        $t = $typeMap[$p->package_type] ?? ['label' => $p->package_type, 'bg' => 'bg-secondary'];
                                    @endphp
                                    <span class="badge {{ $t['bg'] }}">{{ strtoupper($t['label']) }}</span>
                                </td>
                                <td>
                                    <code class="small">{{ $p->mikrotik_profile_name }}</code>
                                </td>
                                <td>
                                    <b>Rp {{ number_format((int) $p->price, 0, ',', '.') }}</b>
                                </td>
                                <td>
                                    @if($p->rate_limit_mbps)
                                        <b>{{ $p->rate_limit_mbps }} Mbps</b>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->limit_uptime)
                                        <span class="fw-semibold">{{ $p->limit_uptime }}</span>
                                    @elseif($p->duration_seconds)
                                        <span class="text-muted">
                                            @if($p->duration_seconds >= 86400) {{ floor($p->duration_seconds/86400) }}d @endif
                                            @if(($p->duration_seconds % 86400) >= 3600) {{ floor(($p->duration_seconds % 86400)/3600) }}j @endif
                                            @if(($p->duration_seconds % 3600) >= 60) {{ floor(($p->duration_seconds % 3600)/60) }}m @endif
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($p->validity_seconds)
                                        <span class="text-muted">
                                            @if($p->validity_seconds >= 86400) {{ floor($p->validity_seconds/86400) }} hari @endif
                                            @if(($p->validity_seconds % 86400) >= 3600) {{ floor(($p->validity_seconds % 86400)/3600) }} jam @endif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Unlimited</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->quota_mb)
                                        {{ $p->quota_mb >= 1024 ? round($p->quota_mb/1024,1).' GB' : $p->quota_mb.' MB' }}
                                    @else
                                        <span class="badge bg-info">Unlimited</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $p->shared_users>1 ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $p->shared_users ?? 1 }} device
                                    </span>
                                </td>
                                <td>
                                    @if($p->router)
                                        <small>{{ $p->router->name }}</small>
                                    @else
                                        <span class="text-danger fw-bold">Default</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('hotspot.profiles.edit', $p) }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                    </a>
                                    <form action="{{ route('hotspot.profiles.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus paket &quot;{{ $p->name }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fa-solid fa-trash me-1"></i>Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                                    Belum ada paket.
                                    <div class="mt-2">
                                        <a href="{{ route('hotspot.profiles.create') }}" class="btn btn-sm btn-primary">Buat paket pertama</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($packages->hasPages())
                <div class="px-3 py-2 border-top">
                    {{ $packages->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body small text-muted">
            <i class="fa-solid fa-circle-info me-1"></i>
            Perubahan pada halaman ini langsung tercermin di:
            <ol class="mb-0 mt-1">
                <li><b>PPPoE Rumahan/Bisnis</b> → Halaman captive portal <code>paketb.html</code>.</li>
                <li><b>Voucher Harian</b> → Halaman captive portal <code>paketc.html</code> (untuk login).</li>
                <li><b>Member Hotspot</b> → Halaman captive portal <code>paketa.html</code>.</li>
            </ol>
        </div>
    </div>
</div>

<style>.bg-purple{background:#6f42c1 !important}</style>
@endsection
