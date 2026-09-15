@extends('layouts.app')

@section('title', 'Paket Member (Berlangganan)')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <h4 class="mb-2 mb-md-0 fs-5"><i class="fa-solid fa-box-open me-2 text-primary"></i>Daftar Paket Member</h4>
        @if(auth()->user()->hasPermission('wash.package.manage'))
        <a href="{{ route('wash.member-packages.create') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Paket
        </a>
        @endif
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Kode</th>
                            <th>Nama Paket</th>
                            <th>Tipe</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packages as $pkg)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $pkg->code }}</td>
                            <td>{{ $pkg->name }}</td>
                            <td>
                                @if($pkg->type === 'wash')
                                <span class="badge bg-info text-dark">Wash</span>
                                @elseif($pkg->type === 'wifi')
                                <span class="badge bg-secondary">WiFi</span>
                                @else
                                <span class="badge bg-primary">Kombinasi</span>
                                @endif
                            </td>
                            <td>Rp {{ number_format($pkg->price, 0, ',', '.') }}</td>
                            <td>
                                @if($pkg->is_active)
                                <span class="badge bg-success">Aktif</span>
                                @else
                                <span class="badge bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if(auth()->user()->hasPermission('wash.package.manage'))
                                <a href="{{ route('wash.member-packages.edit', $pkg->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('wash.member-packages.destroy', $pkg->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada paket member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($packages->hasPages())
        <div class="card-footer bg-white pt-3 pb-1 border-top-0">
            {{ $packages->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
