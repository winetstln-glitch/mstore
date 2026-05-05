@extends('layouts.app')

@section('content')
@php
    $authUser = Auth::user();
    $isAdminOrFinance = $authUser->hasRole('admin') || $authUser->hasRole('finance');
    $hasPermission = fn($p) => $authUser->hasPermission($p);
    
    $movementPeriod = request('movement_period', 'day');
    $movementType = request('movement_type', '');
    $movementDay = request('movement_day', now()->toDateString());
    $movementMonth = request('movement_month', now()->format('Y-m'));
    
    $categoryOptions = [
        'device'  => 'Device (Perangkat Aktif)',
        'fiber'   => 'Fiber (Material Pasif)',
        'tool'    => 'Tool (Alat Kerja)',
        'vehicle' => 'Vehicle (Kendaraan)',
        'general' => 'General (Umum)',
    ];
@endphp

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">

            {{-- ==========================================
                HEADER: Compact & Professional Toolbar
                ========================================== --}}
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-body p-3">
                    {{-- Baris 1: Judul & Filter Utama --}}
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-warehouse text-primary fa-lg"></i>
                            <h5 class="mb-0 fw-bold">
                                @switch(request('type_group'))
                                    @case('tool') {{ __('Peralatan & Aset') }} @break
                                    @case('material') {{ __('Material & Perangkat') }} @break
                                    @default {{ __('Manajemen Inventaris') }}
                                @endswitch
                            </h5>
                        </div>
                        
                        <div class="btn-group btn-group-sm shadow-sm" role="group">
                            @foreach(['' => __('Semua'), 'tool' => __('Peralatan'), 'material' => __('Material')] as $key => $label)
                                <a href="{{ route('inventory.index', $key ? ['type_group' => $key] : []) }}" 
                                   class="btn {{ request('type_group') == $key ? 'btn-primary' : 'btn-outline-primary' }} px-3">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-3 opacity-10">

                    {{-- Baris 2: Toolbar Aksi --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        {{-- Group Kiri: Operasional --}}
                        <div class="d-flex gap-2">
                            <a href="{{ route('inventory.my_assets') }}" class="btn btn-sm btn-warning text-white">
                                <i class="fa-solid fa-rotate-left me-1"></i>{{ __('Kembali Alat') }}
                            </a>
                            @if($hasPermission('inventory.pickup'))
                            <a href="{{ route('inventory.pickup', ['type_group' => request('type_group')]) }}" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-box-open me-1"></i>{{ __('Ambil Barang') }}
                            </a>
                            @endif
                        </div>

                        {{-- Group Kanan: Admin Actions --}}
                        @if($hasPermission('inventory.manage'))
                        <div class="d-flex flex-wrap gap-2">
                            {{-- Dropdown Aksi --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-file-export me-1"></i>{{ __('Ekspor') }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item py-2" href="{{ route('inventory.export.excel') }}"><i class="fa-solid fa-file-excel me-2 text-success"></i>{{ __('Excel') }}</a></li>
                                    <li><a class="dropdown-item py-2" href="{{ route('inventory.export.pdf') }}" target="_blank"><i class="fa-solid fa-file-pdf me-2 text-danger"></i>{{ __('PDF') }}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#importItemModal"><i class="fa-solid fa-file-import me-2 text-primary"></i>{{ __('Impor Excel') }}</a></li>
                                </ul>
                            </div>

                            <div class="vr mx-1 d-none d-sm-block"></div>

                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="fa-solid fa-plus me-1"></i>{{ __('Barang Baru') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#stockInModal">
                                <i class="fa-solid fa-arrow-down me-1"></i>{{ __('Stok Masuk') }}
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            @if($hasPermission('inventory.manage') || $isAdminOrFinance)
            <div class="row g-2 g-md-3 mb-4">
                @foreach([
                    ['Nilai Stok', 'Rp ' . number_format($totalStockValue, 0, ',', '.'), 'fa-warehouse', 'primary'],
                    ['Total Barang', $totalItems, 'fa-boxes-stacked', 'success'],
                    ['Beli Alat', 'Rp ' . number_format($totalToolPurchases, 0, ',', '.'), 'fa-toolbox', 'info'],
                    ['Beli Material', 'Rp ' . number_format($totalMaterialPurchases, 0, ',', '.'), 'fa-microchip', 'secondary'],
                    ['Pemakaian', 'Rp ' . number_format($totalSales, 0, ',', '.'), 'fa-money-bill-transfer', 'warning'],
                ] as $stat)
                <div class="col-6 col-lg">
                    <div class="card card-body py-2 border-start border-3 border-{{ $stat[3] }} h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-uppercase fw-semibold text-{{ $stat[3] }}">{{ $stat[0] }}</small>
                                <div class="fw-bold mt-1">{{ $stat[1] }}</div>
                            </div>
                            <i class="fa-solid {{ $stat[2] }} text-{{ $stat[3] }} opacity-25 fa-lg"></i>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- My Assets Table --}}
            @if(isset($myAssets) && $myAssets->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header bg-transparent py-2">
                    <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-toolbox me-2 text-info"></i>{{ __('Aset Saya') }}</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Nama Aset') }}</th>
                                <th class="d-none d-md-table-cell">{{ __('Nomor Seri') }}</th>
                                <th>{{ __('Kondisi') }}</th>
                                <th class="d-none d-lg-table-cell">{{ __('Catatan') }}</th>
                                <th class="text-end">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myAssets as $asset)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $asset->item->name }}</div>
                                    <small class="text-muted">{{ $asset->asset_code }}</small>
                                </td>
                                <td class="d-none d-md-table-cell"><code>{{ $asset->serial_number }}</code></td>
                                <td>
                                    <span class="badge bg-{{ $asset->condition == 'good' ? 'success' : 'danger' }}">
                                        {{ $asset->condition == 'good' ? __('Baik') : __('Rusak') }}
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell text-muted small text-truncate" style="max-width:200px">
                                    {{ $asset->meta_data['assignment_note'] ?? '-' }}
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Kembalikan aset ini?') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="{{ __('Kembali') }}">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Inventory Items Table --}}
            @if($hasPermission('inventory.view') || $isAdminOrFinance)
            <div class="card mb-4">
                <div class="card-header bg-transparent py-2">
                    <h6 class="mb-0 fw-semibold">{{ __('Daftar Inventaris') }}</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:100px">{{ __('Tipe') }}</th>
                                <th>{{ __('Nama Barang') }}</th>
                                <th class="d-none d-md-table-cell">{{ __('Kategori') }}</th>
                                <th class="text-center" style="width:80px">{{ __('Stok') }}</th>
                                <th class="text-end pe-3" style="width:180px">{{ __('Informasi Harga') }}</th>
                                @if($hasPermission('inventory.manage'))
                                <th class="text-end pe-3" style="width:120px">{{ __('Aksi') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-{{ $item->type_group == 'tool' ? 'primary' : 'secondary' }} w-100">
                                        {{ $item->type_group == 'tool' ? __('Alat') : __('Material') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <div class="small text-muted d-none d-md-block">{{ Str::limit($item->brand . ' ' . $item->model, 30) }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="text-secondary small">{{ ucfirst($item->category) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-{{ $item->stock > 10 ? 'success' : 'danger' }} px-3">
                                        {{ $item->stock }}
                                    </span>
                                    <div class="x-small text-muted mt-1">{{ $item->unit }}</div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex flex-column align-items-end">
                                        <div class="small text-muted">
                                            <span class="x-small fw-normal">Modal:</span> Rp {{ number_format($item->price, 0, ',', '.') }}
                                        </div>
                                        @if($item->type_group !== 'tool')
                                        <div class="text-success fw-bold">
                                            <span class="x-small fw-normal text-muted">Jual:</span> Rp {{ number_format($item->selling_price ?? 0, 0, ',', '.') }}
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                @if($hasPermission('inventory.manage'))
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('inventory.assets.index', $item->id) }}" class="btn btn-outline-info" title="Assets">
                                            <i class="fa-solid fa-barcode"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editItemModal"
                                            @foreach($item->only(['id','name','category','type_group','type','brand','model','unit','stock','price','selling_price','description']) as $attr => $val)
                                            data-{{ $attr }}="{{ $val }}"
                                            @endforeach
                                            data-action="{{ route('inventory.update', $item->id) }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Hapus barang ini?') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-boxes-open fa-2x mb-2 opacity-25"></i>
                                    <div>{{ __('Belum ada data barang.') }}</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Stock Movements Table --}}
            @if($hasPermission('inventory.view') || $isAdminOrFinance)
            <div class="card shadow-sm border-0 inventory-panel mb-4">
                {{-- Header dengan Filter --}}
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex flex-column flex-xl-row gap-3 justify-content-between align-items-xl-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                            <h6 class="m-0 fw-bold text-dark">{{ __('Recent Stock Movements') }}</h6>
                        </div>
                        
                        <form method="GET" action="{{ route('inventory.index') }}" class="d-flex flex-wrap gap-2 align-items-center m-0">
                            {{-- Hidden fields --}}
                            @php $typeGroup = request('type_group'); @endphp
                            @if($typeGroup)<input type="hidden" name="type_group" value="{{ $typeGroup }}">@endif
                            @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif

                            <div class="d-flex flex-wrap gap-2">
                                {{-- Filter Tipe --}}
                                <div class="input-group input-group-sm" style="width: auto;">
                                    <span class="input-group-text bg-light x-small fw-bold text-muted text-uppercase">Tipe</span>
                                    <select name="movement_type" class="form-select" style="min-width: 100px;">
                                        <option value="">{{ __('Semua') }}</option>
                                        <option value="in" {{ request('movement_type') === 'in' ? 'selected' : '' }}>{{ __('Masuk') }}</option>
                                        <option value="out" {{ request('movement_type') === 'out' ? 'selected' : '' }}>{{ __('Keluar') }}</option>
                                    </select>
                                </div>

                                {{-- Filter Periode & Tanggal --}}
                                <div class="input-group input-group-sm" style="width: auto;">
                                    <select name="movement_period" id="movementPeriod" class="form-select" style="min-width: 100px;">
                                        <option value="day" {{ request('movement_period', 'day') === 'day' ? 'selected' : '' }}>{{ __('Harian') }}</option>
                                        <option value="month" {{ request('movement_period') === 'month' ? 'selected' : '' }}>{{ __('Bulanan') }}</option>
                                    </select>
                                    <input type="date" name="movement_day" id="movementDayFilter"
                                           class="form-control {{ request('movement_period', 'day') === 'day' ? '' : 'd-none' }}"
                                           value="{{ request('movement_day', now()->toDateString()) }}">
                                    <input type="month" name="movement_month" id="movementMonthFilter"
                                           class="form-control {{ request('movement_period') === 'month' ? '' : 'd-none' }}"
                                           value="{{ request('movement_month', now()->format('Y-m')) }}">
                                </div>
                            </div>

                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-dark px-3 shadow-xs">
                                    <i class="fa-solid fa-filter me-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('inventory.index', array_filter(['type_group' => request('type_group'), 'category' => request('category')])) }}" 
                                   class="btn btn-sm btn-outline-secondary px-2 shadow-xs" title="Reset Filter">
                                    <i class="fa-solid fa-undo"></i>
                                </a>
                                <div class="vr mx-1"></div>
                                <a href="{{ route('inventory.movements.export.excel', request()->except('page')) }}" 
                                   class="btn btn-sm btn-success px-3 shadow-xs">
                                    <i class="fa-solid fa-file-excel me-1"></i>{{ __('Export') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                {{-- Tabel --}}
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Date') }}</th>
                                    <th class="py-3">{{ __('Move') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Type') }}</th>
                                    <th class="d-none d-lg-table-cell py-3">{{ __('User') }}</th>
                                    <th class="py-3">{{ __('Item') }}</th>
                                    <th class="py-3 text-end">{{ __('Qty') }}</th>
                                    <th class="d-none d-lg-table-cell py-3">{{ __('Desc') }}</th>
                                    @if($hasPermission('inventory.manage'))
                                    <th class="d-none d-md-table-cell py-3 text-end">{{ __('Cost') }}</th>
                                    @endif
                                    <th class="d-none d-sm-table-cell pe-4 py-3 text-end">{{ __('Proof') }}</th>
                                    @if($hasPermission('inventory.manage'))
                                    <th class="pe-4 py-3 text-end" style="width: 100px;">{{ __('Actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                <tr>
                                    <td class="ps-4 small">{{ $trx->created_at->format('d M H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $trx->type === 'in' ? 'success' : 'danger' }}">
                                            {{ strtoupper($trx->type) }}
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-{{ $trx->item->type_group == 'tool' ? 'primary' : 'secondary' }} small">
                                            <i class="fa-solid fa-{{ $trx->item->type_group == 'tool' ? 'toolbox' : 'cube' }}"></i>
                                        </span>
                                    </td>
                                    <td class="d-none d-lg-table-cell small text-truncate" style="max-width: 100px;">
                                        {{ $trx->user->name }}
                                    </td>
                                    <td>
                                        <div class="fw-bold text-truncate" style="max-width: 150px;">{{ $trx->item->name }}</div>
                                    </td>
                                    <td class="text-end small text-{{ $trx->type === 'in' ? 'success' : 'danger' }}">
                                        {{ $trx->type === 'in' ? '+' : '-' }}{{ $trx->quantity }}
                                    </td>
                                    <td class="d-none d-lg-table-cell small text-muted text-truncate" style="max-width: 150px;">
                                        {{ $trx->description ?: '-' }}
                                    </td>
                                    @if($hasPermission('inventory.manage'))
                                    <td class="d-none d-md-table-cell text-end small">
                                        {{ $trx->total_cost ? 'Rp ' . number_format((float) $trx->total_cost, 0, ',', '.') : '-' }}
                                    </td>
                                    @endif
                                    <td class="d-none d-sm-table-cell pe-4 text-end">
                                        @if($trx->proof_image)
                                            <a href="{{ Storage::url($trx->proof_image) }}" target="_blank" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="fa-solid fa-image"></i>
                                            </a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    @if($hasPermission('inventory.manage'))
                                    <td class="pe-4 text-end">
                                        @if($trx->type === 'out' && (Auth::id() === $trx->user_id || $isAdminOrFinance))
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#editPickupModal"
                                                    data-id="{{ $trx->id }}"
                                                    data-item="{{ $trx->item->name }}"
                                                    data-quantity="{{ $trx->quantity }}"
                                                    data-unit="{{ $trx->item->unit }}"
                                                    data-description="{{ $trx->description }}"
                                                    data-action="{{ route('inventory.pickup.update', $trx->id) }}">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form action="{{ route('inventory.pickup.destroy', $trx->id) }}" 
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ __('Delete pickup?') }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-clock-rotate-left fa-2x mb-3 opacity-25"></i>
                                        <p class="mb-0">{{ __('No history found.') }}</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- Pagination --}}
                @if($transactions->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $transactions->links() }}
                </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>

@include('inventory.modals.stock-in', ['items' => $items, 'categoryOptions' => $categoryOptions])
@include('inventory.modals.add-item', ['categoryOptions' => $categoryOptions])
@include('inventory.modals.edit-item', ['categoryOptions' => $categoryOptions])
@include('inventory.modals.edit-pickup')
@include('inventory.modals.import')
@endsection

@push('styles')
<style>
    /* Ensure modal visibility */
    .modal.show {
        display: block !important;
        background: rgba(0,0,0,0.5);
    }
    .modal-dialog {
        z-index: 1060;
    }
    .x-small {
        font-size: 0.7rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // HELPERS
    // ========================================
    const InventoryHelper = {
        applyDefaults: function(typeGroup, categoryEl, unitEl) {
            if (!categoryEl || !unitEl) return;
            const toolCategories = ['tool', 'vehicle', 'general'];
            const materialCategories = ['device', 'fiber', 'general'];
            
            if (typeGroup === 'tool') {
                if (!toolCategories.includes(categoryEl.value)) categoryEl.value = 'tool';
                if (!unitEl.value) unitEl.value = 'unit';
            } else {
                if (!materialCategories.includes(categoryEl.value)) categoryEl.value = 'device';
                if (!unitEl.value) unitEl.value = 'pcs';
            }
        },
        toggleSellingPrice: function(typeGroup, container) {
            if (!container) return;
            if (typeGroup === 'tool') {
                container.classList.add('d-none');
            } else {
                container.classList.remove('d-none');
            }
        }
    };

    // ========================================
    // ADD ITEM MODAL
    // ========================================
    const addTypeGroup = document.getElementById('addTypeGroup');
    const addCategory = document.getElementById('addCategory');
    const addUnit = document.getElementById('addUnit');
    const addSellingPriceContainer = document.getElementById('addSellingPriceContainer');
    
    if (addTypeGroup) {
        addTypeGroup.addEventListener('change', function() {
            InventoryHelper.applyDefaults(this.value, addCategory, addUnit);
            InventoryHelper.toggleSellingPrice(this.value, addSellingPriceContainer);
        });
        InventoryHelper.applyDefaults(addTypeGroup.value, addCategory, addUnit);
        InventoryHelper.toggleSellingPrice(addTypeGroup.value, addSellingPriceContainer);
    }

    // ========================================
    // STOCK IN MODAL
    // ========================================
    const stockInSelect = document.getElementById('stockInItemId');
    const stockInUnit = document.getElementById('stockInUnit');
    const stockInSellingPriceContainer = document.getElementById('stockInSellingPriceContainer');
    
    if (stockInSelect) {
        stockInSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (stockInUnit) stockInUnit.textContent = selected?.getAttribute('data-unit') || 'pcs';
            
            const typeGroup = selected?.getAttribute('data-type_group');
            InventoryHelper.toggleSellingPrice(typeGroup, stockInSellingPriceContainer);
        });
    }

    // ========================================
    // EDIT ITEM MODAL
    // ========================================
    const editItemModal = document.getElementById('editItemModal');
    const editSellingPriceContainer = document.getElementById('editSellingPriceContainer');
    
    if (editItemModal) {
        editItemModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const form = this.querySelector('#editItemForm');
            if (!form) return;
            
            form.action = btn.getAttribute('data-action');
            
            const fieldMap = {
                'editName': 'data-name',
                'editCategory': 'data-category',
                'editTypeGroup': 'data-type_group',
                'editType': 'data-type',
                'editBrand': 'data-brand',
                'editModel': 'data-model',
                'editUnit': 'data-unit',
                'editPrice': 'data-price',
                'editSellingPrice': 'data-selling_price',
                'editDescription': 'data-description',
            };
            
            Object.entries(fieldMap).forEach(([fieldId, dataAttr]) => {
                const el = this.querySelector('#' + fieldId);
                if (el) el.value = btn.getAttribute(dataAttr) || '';
            });
            
            const stock = btn.getAttribute('data-stock') || 0;
            const stockEl = this.querySelector('#editStock');
            const unitLabel = this.querySelector('.edit-unit-label');
            
            if (stockEl) stockEl.value = stock;
            if (unitLabel) unitLabel.textContent = btn.getAttribute('data-unit') || 'pcs';
            
            const typeGroup = btn.getAttribute('data-type_group');
            InventoryHelper.applyDefaults(typeGroup, this.querySelector('#editCategory'), this.querySelector('#editUnit'));
            InventoryHelper.toggleSellingPrice(typeGroup, editSellingPriceContainer);
        });

        document.getElementById('editTypeGroup')?.addEventListener('change', function() {
            InventoryHelper.applyDefaults(this.value, document.getElementById('editCategory'), document.getElementById('editUnit'));
            InventoryHelper.toggleSellingPrice(this.value, editSellingPriceContainer);
        });
    }

    // ========================================
    // EDIT PICKUP MODAL
    // ========================================
    const editPickupModal = document.getElementById('editPickupModal');
    if (editPickupModal) {
        editPickupModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const form = this.querySelector('#editPickupForm');
            if (!form) return;
            
            form.action = btn.getAttribute('data-action');
            const fields = {
                'editPickupItemName': 'data-item',
                'editPickupQuantity': 'data-quantity',
                'editPickupDescription': 'data-description'
            };
            
            Object.entries(fields).forEach(([id, attr]) => {
                const el = this.querySelector('#' + id);
                if (el) el.value = btn.getAttribute(attr) || '';
            });
            
            const unitEl = this.querySelector('#editPickupUnit');
            if (unitEl) unitEl.textContent = btn.getAttribute('data-unit') || 'pcs';
        });
    }

    // ========================================
    // MOVEMENT FILTER
    // ========================================
    const movementPeriod = document.getElementById('movementPeriod');
    const dayFilter = document.getElementById('movementDayFilter');
    const monthFilter = document.getElementById('movementMonthFilter');
    
    if (movementPeriod) {
        const toggle = () => {
            if (dayFilter) dayFilter.classList.toggle('d-none', movementPeriod.value !== 'day');
            if (monthFilter) monthFilter.classList.toggle('d-none', movementPeriod.value !== 'month');
        };
        movementPeriod.addEventListener('change', toggle);
        toggle();
    }
});
</script>
@endpush
