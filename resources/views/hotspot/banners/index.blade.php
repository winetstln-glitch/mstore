@extends('layouts.app')

@section('title', __('Banner Hotspot'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">
            <i class="fa-solid fa-images me-2 text-primary"></i>
            Banner Hotspot
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('hotspot.banners.index') }}" class="d-flex gap-2 align-items-center">
                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari banner..." style="min-width:180px;">
                <select name="page_target" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Halaman</option>
                    <option value="all" @selected($pageTarget==='all')>Semua Halaman</option>
                    <option value="login" @selected($pageTarget==='login')>Login</option>
                    <option value="landing" @selected($pageTarget==='landing')>Landing</option>
                    <option value="voucher" @selected($pageTarget==='voucher')>Voucher</option>
                    <option value="member" @selected($pageTarget==='member')>Member</option>
                    <option value="residential" @selected($pageTarget==='residential')>Rumahan</option>
                </select>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" @selected($status==='active')>Aktif</option>
                    <option value="inactive" @selected($status==='inactive')>Tidak Aktif</option>
                </select>
            </form>
            <a href="{{ route('hotspot.banners.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Tambah Banner
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm border-top border-4 border-primary">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">#</th>
                            <th style="width:140px;">Gambar</th>
                            <th>Judul</th>
                            <th>Target Halaman</th>
                            <th>Urutan</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-end" style="width:200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banners as $idx => $banner)
                            <tr>
                                <td>{{ $banners->firstItem() + $idx }}</td>
                                <td>
                                    @if($banner->image_url)
                                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="rounded" style="width:120px;height:60px;object-fit:cover;border:1px solid #dee2e6;">
                                    @else
                                        <span class="badge bg-secondary">Tidak ada gambar</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $banner->title }}</div>
                                    @if($banner->subtitle)
                                        <small class="text-muted d-block mt-1">{{ $banner->subtitle }}</small>
                                    @endif
                                    @if($banner->url_cta)
                                        <small class="text-primary d-block mt-1">
                                            <i class="fa-solid fa-link"></i>
                                            {{ $banner->cta_text ?: 'CTA' }}: {{ Str::limit($banner->url_cta, 40) }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $pageLabels = [
                                            'all' => ['label' => 'Semua', 'badge' => 'bg-secondary'],
                                            'login' => ['label' => 'Login', 'badge' => 'bg-primary'],
                                            'landing' => ['label' => 'Landing', 'badge' => 'bg-info'],
                                            'voucher' => ['label' => 'Voucher', 'badge' => 'bg-success'],
                                            'member' => ['label' => 'Member', 'badge' => 'bg-purple text-white'],
                                            'residential' => ['label' => 'Rumahan', 'badge' => 'bg-warning text-dark'],
                                        ];
                                        $pg = $pageLabels[$banner->page_target] ?? ['label' => $banner->page_target, 'badge' => 'bg-secondary'];
                                    @endphp
                                    <span class="badge {{ $pg['badge'] }}">{{ $pg['label'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $banner->sort_order }}</span>
                                </td>
                                <td>
                                    @if($banner->start_date || $banner->end_date)
                                        <small class="d-block">
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            {{ $banner->start_date?->format('d/m/Y') ?? '-' }}
                                            <span class="mx-1">s/d</span>
                                            {{ $banner->end_date?->format('d/m/Y') ?? '-' }}
                                        </small>
                                    @else
                                        <small class="text-muted">Tidak ada batas waktu</small>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('hotspot.banners.toggle', $banner) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-toggle {{ $banner->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}" style="--bs-btn-padding-y:.15rem;--bs-btn-padding-x:.5rem;--bs-btn-font-size:.75rem;">
                                            {{ $banner->is_active ? '✓ Aktif' : '✗ Nonaktif' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('hotspot.banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('hotspot.banners.destroy', $banner) }}" onsubmit="return confirm('Hapus banner ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-image fa-3x mb-3 opacity-25"></i>
                                    <div class="fw-semibold">Belum ada banner</div>
                                    <div class="small mt-1">Klik tombol "Tambah Banner" untuk membuat banner pertama.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($banners->hasPages())
            <div class="card-footer border-top bg-white py-3">
                {{ $banners->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
