@extends('layouts.app')

@section('content')
<div class="container-fluid inventory-assets-page py-2 py-md-3">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-body">
            <i class="fa-solid fa-barcode me-2"></i> {{ __('Kelola Aset') }}: {{ $item->name }}
        </h1>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Inventaris') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Item Info -->
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100 inventory-assets-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Detail Barang') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">{{ __('Nama') }}</td>
                            <td>{{ $item->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('Kategori') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($item->category) }}</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('Total Stok') }}</td>
                            <td>{{ $item->stock }} {{ $item->unit }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('Aset Terdaftar') }}</td>
                            <td>{{ $assets->count() }} {{ __('Unit') }}</td>
                        </tr>
                    </table>
                    <div class="alert alert-info small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        {{ __('Daftarkan aset hanya untuk barang yang perlu pelacakan per unit seperti SN atau MAC. Jumlah di sini tidak mengubah total stok kecuali disinkronkan.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset List -->
        <div class="col-md-8 mb-4">
            <div class="card shadow h-100 inventory-assets-panel">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Daftar Aset') }}</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Daftarkan Aset Baru') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="assetsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Nomor Seri') }}</th>
                                    <th>{{ __('Pemegang / Lokasi') }}</th>
                                    <th>{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    <tr>
                                        <td>
                                            @if($asset->status == 'in_stock')
                                                <span class="badge bg-success">{{ __('Tersedia') }}</span>
                                            @elseif($asset->status == 'deployed')
                                                <span class="badge bg-primary">{{ __('Dipakai') }}</span>
                                            @elseif($asset->status == 'maintenance')
                                                <span class="badge bg-warning text-dark">{{ __('Perawatan') }}</span>
                                            @elseif($asset->status == 'broken')
                                                <span class="badge bg-danger">{{ __('Rusak') }}</span>
                                            @elseif($asset->status == 'lost')
                                                <span class="badge bg-dark">{{ __('Hilang') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $asset->serial_number }}</div>
                                            <small class="text-muted">{{ $asset->asset_code }}</small>
                                        </td>
                                        <td>
                                            @if($asset->holder)
                                                <i class="fa-solid fa-user me-1"></i> {{ $asset->holder->name }}
                                                @if(isset($asset->meta_data['assignment_note']))
                                                    <div class="small text-muted fst-italic">"{{ $asset->meta_data['assignment_note'] }}"</div>
                                                @endif
                                            @else
                                                <i class="fa-solid fa-warehouse me-1"></i> {{ __('Gudang') }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                @if($asset->status == 'in_stock')
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="openAssignModal({{ $asset->id }}, '{{ $asset->serial_number }}')">
                                                        <i class="fa-solid fa-hand-holding-hand"></i> {{ __('Serahkan') }}
                                                    </button>
                                                @elseif($asset->status == 'deployed')
                                                    <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Kembalikan aset ini ke gudang?') }}')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fa-solid fa-rotate-left"></i> {{ __('Kembalikan') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="openEditModal({{ $asset->id }}, '{{ $asset->serial_number }}', '{{ $asset->status }}', '{{ $asset->condition }}', '{{ $asset->mac_address }}', '{{ $asset->asset_code }}')">
                                                            <i class="fa-solid fa-edit me-2"></i> {{ __('Ubah Detail') }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('inventory.assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('{{ __('Hapus data aset ini?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa-solid fa-trash me-2"></i> {{ __('Hapus') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            {{ __('Belum ada aset per unit yang terdaftar.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .inventory-assets-page .inventory-assets-panel {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 1.2rem;
        overflow: hidden;
    }

    .inventory-assets-page .inventory-assets-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.18);
    }

    .inventory-assets-page #assetsTable thead th {
        background: rgba(148, 163, 184, 0.12);
    }

    [data-bs-theme="dark"] .inventory-assets-page .inventory-assets-panel {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .inventory-assets-page .inventory-assets-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-bottom-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .inventory-assets-page #assetsTable thead th {
        background: rgba(51, 65, 85, 0.5);
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .inventory-assets-page #assetsTable td {
        border-color: #334155;
    }

    @media (max-width: 767.98px) {
        .inventory-assets-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .inventory-assets-page .btn {
            width: 100%;
        }

        .inventory-assets-page .inventory-assets-panel {
            border-radius: 1rem;
        }

        .inventory-assets-page #assetsTable {
            min-width: 640px;
        }
    }
</style>
@endpush

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('inventory.assets.store', $item->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Daftarkan Aset Baru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nomor Seri') }}</label>
                        <input type="text" name="serial_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Kode Aset (Opsional)') }}</label>
                        <input type="text" name="asset_code" class="form-control" placeholder="Contoh: AST-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Alamat MAC (Opsional)') }}</label>
                        <input type="text" name="mac_address" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="in_stock">{{ __('Tersedia') }}</option>
                                <option value="deployed">{{ __('Dipakai') }}</option>
                                <option value="maintenance">{{ __('Perawatan') }}</option>
                                <option value="broken">{{ __('Rusak') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Kondisi') }}</label>
                            <select name="condition" class="form-select">
                                <option value="good">{{ __('Baik') }}</option>
                                <option value="damaged">{{ __('Rusak Ringan') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Daftarkan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Assign Asset Modal -->
<div class="modal fade" id="assignAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="assignAssetForm" action="" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Serahkan Aset') }} <span id="assignAssetSN" class="badge bg-secondary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        {{ __('Aset akan ditandai sebagai dipakai dan tanggung jawabnya diberikan ke pengguna yang dipilih.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Serahkan Kepada (User/Pengurus/Teknisi)') }}</label>
                        <select name="user_id" class="form-select select2" required>
                            <option value="">{{ __('-- Pilih Pengguna --') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Catatan') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Serah terima untuk proyek X"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Serahkan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Asset Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editAssetForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Ubah Detail Aset') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nomor Seri') }}</label>
                        <input type="text" name="serial_number" id="editSN" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Kode Aset') }}</label>
                        <input type="text" name="asset_code" id="editCode" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Alamat MAC') }}</label>
                        <input type="text" name="mac_address" id="editMAC" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="in_stock">{{ __('Tersedia') }}</option>
                                <option value="deployed">{{ __('Dipakai') }}</option>
                                <option value="maintenance">{{ __('Perawatan') }}</option>
                                <option value="broken">{{ __('Rusak') }}</option>
                                <option value="lost">{{ __('Hilang') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Kondisi') }}</label>
                            <select name="condition" id="editCondition" class="form-select">
                                <option value="good">{{ __('Baik') }}</option>
                                <option value="damaged">{{ __('Rusak Ringan') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(id, sn) {
    var form = document.getElementById('assignAssetForm');
    form.action = '/inventory/assets/' + id + '/assign';
    document.getElementById('assignAssetSN').textContent = sn;
    var modal = new bootstrap.Modal(document.getElementById('assignAssetModal'));
    modal.show();
}

function openEditModal(id, sn, status, condition, mac, code) {
    var form = document.getElementById('editAssetForm');
    form.action = '/inventory/assets/' + id;
    
    document.getElementById('editSN').value = sn;
    document.getElementById('editStatus').value = status;
    document.getElementById('editCondition').value = condition;
    document.getElementById('editMAC').value = mac || '';
    document.getElementById('editCode').value = code || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
    modal.show();
}
</script>
@endsection
