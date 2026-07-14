{{-- resources/views/olt/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manajemen OLT')

@section('content')
<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen OLT</h4>
            <p class="text-muted small mb-0">Kelola seluruh server OLT dan perangkat ONU</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('olt.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Tambah OLT
            </a>
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="fa-solid fa-rotate me-1"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <p class="text-muted small mb-1">Total OLT</p>
                    <h3 class="fw-bold mb-0 text-primary">{{ $stats['total_olts'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <p class="text-muted small mb-1">OLT Online</p>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['online_olts'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <p class="text-muted small mb-1">Total ONU Terdaftar</p>
                    <h3 class="fw-bold mb-0 text-info">{{ $stats['total_onts'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <p class="text-muted small mb-1">ONU Online</p>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['online_onts'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel OLT --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3 py-3">Nama OLT</th>
                            <th class="py-3">IP Address</th>
                            <th class="py-3">Vendor</th>
                            <th class="py-3">Model</th>
                            <th class="py-3">Lokasi</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">ONU</th>
                            <th class="py-3">Last Polling</th>
                            <th class="pe-3 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($olts as $olt)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('olt.show', $olt->id) }}" class="fw-medium text-decoration-none">
                                    {{ $olt->name }}
                                </a>
                            </td>
                            <td><code>{{ $olt->ip_address }}</code></td>
                            <td>
                                <span class="badge bg-secondary">{{ strtoupper($olt->vendor) }}</span>
                            </td>
                            <td>{{ $olt->model ?? '-' }}</td>
                            <td class="small">{{ $olt->location ?? '-' }}</td>
                            <td>
                                @if($olt->status === 'online')
                                    <span class="badge bg-success">Online</span>
                                @elseif($olt->status === 'warning')
                                    <span class="badge bg-warning text-dark">Warning</span>
                                @elseif($olt->status === 'critical')
                                    <span class="badge bg-danger">Critical</span>
                                @else
                                    <span class="badge bg-secondary">Offline</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    {{ $olt->onts_count ?? 0 }} ONU
                                </span>
                            </td>
                            <td class="small">
                                @if($olt->last_polled_at)
                                    {{ $olt->last_polled_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">Belum pernah</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('olt.show', $olt->id) }}" class="btn btn-outline-primary" title="Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('olt.onts', $olt->id) }}" class="btn btn-outline-info" title="Daftar ONU">
                                        <i class="fa-solid fa-list"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-success" 
                                            onclick="pollOlt({{ $olt->id }})" title="Polling">
                                        <i class="fa-solid fa-rotate"></i>
                                    </button>
                                    <a href="{{ route('olt.edit', $olt->id) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteOlt({{ $olt->id }}, '{{ $olt->name }}')" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fa-solid fa-server fa-3x mb-3 text-secondary"></i>
                                    <p class="mb-1">Belum ada data OLT</p>
                                    <small>Klik "Tambah OLT" untuk menambahkan server baru</small>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($olts instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $olts->firstItem() ?? 0 }} - {{ $olts->lastItem() ?? 0 }} dari {{ $olts->total() }} data
                </small>
                {{ $olts->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Form Delete --}}
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function pollOlt(id) {
    Swal.fire({
        title: 'Polling OLT',
        text: 'Memulai polling data dari OLT...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`/olt/${id}/poll`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Polling Berhasil',
                text: `Ditemukan ${data.onts_found} ONU dalam ${data.duration_ms}ms`,
                timer: 3000,
                showConfirmButton: false
            });
            setTimeout(() => location.reload(), 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Polling Gagal',
                text: data.error || 'Terjadi kesalahan koneksi',
            });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    });
}

function deleteOlt(id, name) {
    Swal.fire({
        title: 'Hapus OLT',
        html: `Yakin ingin menghapus <b>${name}</b>?<br><small class="text-danger">Semua data ONU terkait juga akan dihapus</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-form');
            form.action = `/olt/${id}`;
            form.submit();
        }
    });
}
</script>
@endpush