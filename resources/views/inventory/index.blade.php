@extends('layouts.app')

@section('content')
{{-- ============================================
    INVENTORY MANAGEMENT PAGE
    ============================================ --}}

{{-- Pre-set variables untuk filter movements --}}
@php
    $isAdminOrFinance = Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance');
    $movementPeriod = request('movement_period', 'day');
    $movementType = request('movement_type', '');
    $movementDay = request('movement_day', now()->toDateString());
    $movementMonth = request('movement_month', now()->format('Y-m'));
    
    // Category options (bisa dipindah ke config/controller)
    $categoryOptions = [
        'device'  => 'Device (Perangkat Aktif)',
        'fiber'   => 'Fiber (Material Pasif)',
        'tool'    => 'Tool (Alat Kerja)',
        'vehicle' => 'Vehicle (Kendaraan)',
        'general' => 'General (Umum)',
    ];
@endphp

<div class="container-fluid inventory-page py-2 py-md-3">
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- ==========================================
                HEADER: Judul & Toolbar
                ========================================== --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                
                {{-- Judul Halaman --}}
                <h1 class="h3 mb-0 text-body text-truncate" style="max-width: 100%;">
                    @switch(request('type_group'))
                        @case('tool') {{ __('Peralatan & Aset') }} @break
                        @case('material') {{ __('Material & Perangkat') }} @break
                        @default {{ __('Manajemen Inventaris') }}
                    @endswitch
                </h1>
                
                {{-- Toolbar Buttons --}}
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end inventory-toolbar">
                    
                    {{-- Filter Group (All/Tools/Materials) --}}
                    <div class="btn-group" role="group">
                        @php
                            $filterLinks = [
                                '' => ['label' => __('Semua'), 'param' => []],
                                'tool' => ['label' => __('Peralatan'), 'param' => ['type_group' => 'tool']],
                                'material' => ['label' => __('Material'), 'param' => ['type_group' => 'material']],
                            ];
                        @endphp
                        @foreach($filterLinks as $key => $link)
                            <a href="{{ route('inventory.index', $link['param']) }}" 
                               class="btn btn-outline-secondary {{ request('type_group') == $key ? 'active' : '' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>

                    @if($isAdminOrFinance)
                        {{-- Dropdown Kategori --}}
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-filter d-md-none"></i>
                                <span class="d-none d-md-inline">
                                    {{ request('category') ? ucfirst(request('category')) : __('Kategori') }}
                                </span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.index', ['type_group' => request('type_group')]) }}">
                                        {{ __('Semua Kategori') }}
                                    </a>
                                </li>
                                @foreach($categories as $cat)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventory.index', ['category' => $cat, 'type_group' => request('type_group')]) }}">
                                            {{ ucfirst($cat) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Dropdown Aksi (Export/Import) --}}
                        <div class="dropdown">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical d-md-none"></i>
                                <span class="d-none d-md-inline">
                                    <i class="fa-solid fa-file-export me-1"></i> {{ __('Aksi') }}
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.export.excel') }}">
                                        <i class="fa-solid fa-file-excel me-2 text-success"></i> {{ __('Ekspor Excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.export.pdf') }}" target="_blank">
                                        <i class="fa-solid fa-file-pdf me-2 text-danger"></i> {{ __('Ekspor PDF') }}
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importItemModal">
                                        <i class="fa-solid fa-file-import me-2 text-primary"></i> {{ __('Impor Excel') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        {{-- Tombol Tambah Item --}}
                        <button type="button" class="btn btn-success flex-grow-1 flex-md-grow-0" 
                                data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fa-solid fa-plus me-1"></i> 
                            <span class="d-none d-sm-inline">{{ __('Tambah Barang') }}</span>
                        </button>
                        
                        {{-- Tombol Stock Masuk --}}
                        <button type="button" class="btn btn-outline-success flex-grow-1 flex-md-grow-0" 
                                data-bs-toggle="modal" data-bs-target="#stockInModal">
                            <i class="fa-solid fa-arrow-down-wide-short me-1"></i> 
                            <span class="d-none d-sm-inline">{{ __('Stok Masuk') }}</span>
                        </button>
                    @endif
                    
                    {{-- Tombol Return --}}
                    <a href="{{ route('inventory.my_assets') }}" class="btn btn-outline-warning" title="{{ __('Kembalikan Alat') }}">
                        <i class="fa-solid fa-rotate-left"></i> 
                        <span class="d-none d-sm-inline ms-1">{{ __('Kembali') }}</span>
                    </a>
                    
                    {{-- Tombol Pickup --}}
                    <a href="{{ route('inventory.pickup', ['type_group' => request('type_group')]) }}" 
                       class="btn btn-primary flex-grow-1 flex-md-grow-0" title="{{ __('Ambil Barang') }}">
                        <i class="fa-solid fa-box-open me-1"></i> 
                        <span class="d-none d-sm-inline">{{ __('Ambil') }}</span>
                    </a>
                </div>
            </div>

            {{-- ==========================================
                DASHBOARD STATS (Admin/Finance Only)
                ========================================== --}}
            @if($isAdminOrFinance)
            <div class="row mb-4">
                @php
                    $stats = [
                        [
                            'label' => __('Nilai Stok'),
                            'value' => 'Rp ' . number_format($totalStockValue, 0, ',', '.'),
                            'icon'  => 'fa-warehouse',
                            'color' => 'primary',
                        ],
                        [
                            'label' => __('Total Barang'),
                            'value' => $totalItems,
                            'icon'  => 'fa-boxes-stacked',
                            'color' => 'success',
                        ],
                        [
                            'label' => __('Beli Alat'),
                            'value' => 'Rp ' . number_format($totalToolPurchases, 0, ',', '.'),
                            'icon'  => 'fa-toolbox',
                            'color' => 'info',
                        ],
                        [
                            'label' => __('Beli Material'),
                            'value' => 'Rp ' . number_format($totalMaterialPurchases, 0, ',', '.'),
                            'icon'  => 'fa-microchip',
                            'color' => 'primary',
                        ],
                        [
                            'label' => __('Pemakaian'),
                            'value' => 'Rp ' . number_format($totalSales, 0, ',', '.'),
                            'icon'  => 'fa-money-bill-transfer',
                            'color' => 'warning',
                        ],
                    ];
                @endphp
                @foreach($stats as $stat)
                <div class="col-6 col-md-4 col-xl mb-3">
                    <div class="card border-start-{{ $stat['color'] }} border-start-3 shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs fw-bold text-{{ $stat['color'] }} text-uppercase mb-1">
                                        {{ $stat['label'] }}
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800 {{ isset($stat['small']) ? 'small' : '' }}">
                                        {{ $stat['value'] }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid {{ $stat['icon'] }} fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ==========================================
                MY ASSETS TABLE (Teknisi/Koordinator)
                ========================================== --}}
            @if(isset($myAssets) && $myAssets->isNotEmpty())
            <div class="card shadow-sm border-0 mb-4 border-start-info inventory-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-info">
                        <i class="fa-solid fa-toolbox me-2"></i>{{ __('Aset Saya') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Nama Aset') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Nomor Seri') }}</th>
                                    <th class="py-3">{{ __('Status') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Kondisi') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Catatan') }}</th>
                                    <th class="text-end pe-4 py-3" style="width: 100px;">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myAssets as $asset)
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        {{ $asset->item->name }}
                                        <div class="small text-muted d-none d-md-block">{{ $asset->asset_code }}</div>
                                    </td>
                                    <td class="d-none d-md-table-cell font-monospace small">{{ $asset->serial_number }}</td>
                                    <td><span class="badge bg-primary small">{{ __('Dipakai') }}</span></td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-{{ $asset->condition == 'good' ? 'success' : 'danger' }} small">
                                            {{ $asset->condition == 'good' ? __('Baik') : __('Rusak') }}
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell small text-muted text-truncate" style="max-width: 150px;">
                                        {{ $asset->meta_data['assignment_note'] ?? '-' }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="{{ route('inventory.assets.return', $asset->id) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('{{ __('Kembalikan aset ini?') }}')">
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
            </div>
            @endif

            {{-- ==========================================
                INVENTORY ITEMS TABLE (Admin/Finance Only)
                ========================================== --}}
            @if($isAdminOrFinance)
            <div class="card shadow-sm border-0 mb-4 inventory-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">{{ __('Daftar Inventaris') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Tipe') }}</th>
                                    <th class="py-3">{{ __('Nama') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Kategori') }}</th>
                                    <th class="d-none d-lg-table-cell py-3">{{ __('Merek/Model') }}</th>
                                    <th class="py-3 text-center">{{ __('Stok') }}</th>
                                    <th class="py-3 text-end">{{ __('Harga Modal') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Satuan') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 140px;">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-{{ $item->type_group == 'tool' ? 'primary' : 'secondary' }}">
                                            <i class="fa-solid fa-{{ $item->type_group == 'tool' ? 'toolbox' : 'cube' }} d-none d-md-inline me-1"></i>
                                            {{ $item->type_group == 'tool' ? __('Alat') : __('Material') }}
                                        </span>
                                    </td>
                                    <td class="fw-medium">
                                        {{ $item->name }}
                                        <div class="small text-muted d-none d-md-block text-truncate" style="max-width: 200px;">
                                            {{ Str::limit($item->description, 30) ?: '-' }}
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge text-dark border small">{{ ucfirst($item->category) }}</span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        {{ $item->brand ?: '-' }}
                                        @if($item->model)
                                            <div class="small text-muted text-truncate" style="max-width: 120px;">
                                                {{ $item->model }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $item->stock > 10 ? 'success' : 'danger' }} rounded-pill px-3">
                                            {{ $item->stock }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        Rp {{ number_format($item->price, 0, ',', '.') }}
                                    </td>
                                    <td class="d-none d-md-table-cell small">{{ $item->unit }}</td>
                                    <td class="pe-4 text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('inventory.assets.index', $item->id) }}" 
                                               class="btn btn-sm btn-outline-info" title="{{ __('Assets') }}">
                                                <i class="fa-solid fa-barcode"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#editItemModal"
                                                    @foreach($item->only(['id','name','category','type_group','type','brand','model','unit','stock','price','description']) as $attr => $val)
                                                    data-{{ $attr }}="{{ $val }}"
                                                    @endforeach
                                                    data-action="{{ route('inventory.update', $item->id) }}">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form action="{{ route('inventory.destroy', $item->id) }}" 
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ __('Delete this item?') }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-boxes-stacked fa-2x mb-3 opacity-25"></i>
                                        <p class="mb-0">{{ __('No items found.') }}</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- ==========================================
                STOCK MOVEMENTS TABLE
                ========================================== --}}
            <div class="card shadow-sm border-0 inventory-panel">
                {{-- Header dengan Filter --}}
                <div class="card-header bg-white py-3">
                    <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between align-items-lg-center">
                        <h6 class="m-0 fw-bold text-primary">{{ __('Recent Stock Movements') }}</h6>
                        
                        <form method="GET" action="{{ route('inventory.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                            {{-- Hidden fields untuk mempertahankan filter lain --}}
                            @if(request('type_group'))
                                <input type="hidden" name="type_group" value="{{ request('type_group') }}">
                            @endif
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif

                            {{-- Filter Tipe --}}
                            <select name="movement_type" class="form-select form-select-sm" style="min-width: 130px;">
                                <option value="">{{ __('Semua') }}</option>
                                <option value="in" {{ $movementType === 'in' ? 'selected' : '' }}>{{ __('Barang Masuk') }}</option>
                                <option value="out" {{ $movementType === 'out' ? 'selected' : '' }}>{{ __('Barang Keluar') }}</option>
                            </select>

                            {{-- Filter Periode --}}
                            <select name="movement_period" id="movementPeriod" class="form-select form-select-sm" style="min-width: 120px;">
                                <option value="day" {{ $movementPeriod === 'day' ? 'selected' : '' }}>{{ __('Per Hari') }}</option>
                                <option value="month" {{ $movementPeriod === 'month' ? 'selected' : '' }}>{{ __('Per Bulan') }}</option>
                            </select>

                            {{-- Input Tanggal --}}
                            <input type="date" name="movement_day" id="movementDayFilter"
                                   class="form-control form-control-sm {{ $movementPeriod === 'day' ? '' : 'd-none' }}"
                                   value="{{ $movementDay }}">

                            <input type="month" name="movement_month" id="movementMonthFilter"
                                   class="form-control form-control-sm {{ $movementPeriod === 'month' ? '' : 'd-none' }}"
                                   value="{{ $movementMonth }}">

                            {{-- Action Buttons --}}
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-filter me-1"></i>{{ __('Filter') }}
                            </button>
                            <a href="{{ route('inventory.index', array_filter(['type_group' => request('type_group'), 'category' => request('category')])) }}" 
                               class="btn btn-sm btn-outline-secondary">
                                {{ __('Reset') }}
                            </a>
                            <a href="{{ route('inventory.movements.export.excel', request()->except('page')) }}" 
                               class="btn btn-sm btn-success">
                                <i class="fa-solid fa-file-excel me-1"></i>{{ __('Download') }}
                            </a>
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
                                    <th class="d-none d-md-table-cell py-3 text-end">{{ __('Cost') }}</th>
                                    <th class="d-none d-sm-table-cell pe-4 py-3 text-end">{{ __('Proof') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 100px;">{{ __('Actions') }}</th>
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
                                    <td class="d-none d-md-table-cell text-end small">
                                        {{ $trx->total_cost ? 'Rp ' . number_format((float) $trx->total_cost, 0, ',', '.') : '-' }}
                                    </td>
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

        </div>
    </div>
</div>

{{-- ==========================================
    MODALS - Dipisah untuk kemudahan maintenance
    ========================================== --}}

@include('inventory.modals.stock-in', ['items' => $items, 'categoryOptions' => $categoryOptions])
@include('inventory.modals.add-item', ['categoryOptions' => $categoryOptions])
@include('inventory.modals.edit-item', ['categoryOptions' => $categoryOptions])
@include('inventory.modals.edit-pickup')
@include('inventory.modals.import')

@endsection

{{-- ==========================================
    JAVASCRIPT - Dipisah ke section tersendiri
    ========================================== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // HELPER: Set default category & unit berdasarkan type_group
    // ========================================
    function applyDefaultsByTypeGroup(typeGroup, categoryEl, unitEl) {
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
    }

    // ========================================
    // ADD ITEM MODAL: Auto-fill category & unit
    // ========================================
    const addTypeGroup = document.getElementById('addTypeGroup');
    const addCategory = document.getElementById('addCategory');
    const addUnit = document.getElementById('addUnit');
    
    if (addTypeGroup) {
        addTypeGroup.addEventListener('change', function() {
            applyDefaultsByTypeGroup(this.value, addCategory, addUnit);
        });
        // Init on load
        applyDefaultsByTypeGroup(addTypeGroup.value, addCategory, addUnit);
    }

    // ========================================
    // STOCK IN MODAL: Update unit label saat pilih item
    // ========================================
    const stockInSelect = document.getElementById('stockInItemId');
    const stockInUnit = document.getElementById('stockInUnit');
    
    if (stockInSelect && stockInUnit) {
        stockInSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            stockInUnit.textContent = selected?.getAttribute('data-unit') || 'pcs';
        });
    }

    // ========================================
    // EDIT ITEM MODAL: Populate form dari data attribute
    // ========================================
    const editItemModal = document.getElementById('editItemModal');
    
    if (editItemModal) {
        editItemModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const form = this.querySelector('#editItemForm');
            
            // Set form action
            form.action = btn.getAttribute('data-action');
            
            // Map data attributes ke form fields
            const fieldMap = {
                'editName': 'data-name',
                'editCategory': 'data-category',
                'editTypeGroup': 'data-type_group',
                'editType': 'data-type',
                'editBrand': 'data-brand',
                'editModel': 'data-model',
                'editUnit': 'data-unit',
                'editPrice': 'data-price',
                'editDescription': 'data-description',
            };
            
            Object.entries(fieldMap).forEach(([fieldId, dataAttr]) => {
                const el = this.querySelector('#' + fieldId);
                if (el) el.value = btn.getAttribute(dataAttr) || '';
            });
            
            // Stock fields
            const stock = btn.getAttribute('data-stock') || 0;
            this.querySelector('#editCurrentStock').value = stock;
            this.querySelector('#editStock').value = stock;
            this.querySelector('#editStockAdjustment').value = 0;
            
            // Apply defaults
            applyDefaultsByTypeGroup(
                btn.getAttribute('data-type_group'),
                this.querySelector('#editCategory'),
                this.querySelector('#editUnit')
            );
        });

        // Type group change handler
        const editTypeGroup = document.getElementById('editTypeGroup');
        if (editTypeGroup) {
            editTypeGroup.addEventListener('change', function() {
                applyDefaultsByTypeGroup(this.value, document.getElementById('editCategory'), document.getElementById('editUnit'));
            });
        }

        // Submit handler: validate stock tidak minus
        const editItemForm = document.getElementById('editItemForm');
        if (editItemForm) {
            editItemForm.addEventListener('submit', function(e) {
                const current = parseInt(document.getElementById('editCurrentStock').value) || 0;
                const adjustment = parseInt(document.getElementById('editStockAdjustment').value) || 0;
                const finalStock = current + adjustment;
                
                if (finalStock < 0) {
                    e.preventDefault();
                    alert('{{ __("Stok akhir tidak boleh minus!") }}');
                    return;
                }
                document.getElementById('editStock').value = finalStock;
            });
        }
    }

    // ========================================
    // EDIT PICKUP MODAL: Populate form
    // ========================================
    const editPickupModal = document.getElementById('editPickupModal');
    
    if (editPickupModal) {
        editPickupModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const form = this.querySelector('#editPickupForm');
            
            form.action = btn.getAttribute('data-action');
            this.querySelector('#editPickupItemName').value = btn.getAttribute('data-item');
            this.querySelector('#editPickupQuantity').value = btn.getAttribute('data-quantity');
            this.querySelector('#editPickupUnit').textContent = btn.getAttribute('data-unit');
            this.querySelector('#editPickupDescription').value = btn.getAttribute('data-description') || '';
        });
    }

    // ========================================
    // MOVEMENT FILTER: Toggle date/month input
    // ========================================
    const movementPeriod = document.getElementById('movementPeriod');
    const movementDayFilter = document.getElementById('movementDayFilter');
    const movementMonthFilter = document.getElementById('movementMonthFilter');
    
    if (movementPeriod) {
        const toggleDateInput = function() {
            movementDayFilter.classList.toggle('d-none', movementPeriod.value !== 'day');
            movementMonthFilter.classList.toggle('d-none', movementPeriod.value !== 'month');
        };
        
        movementPeriod.addEventListener('change', toggleDateInput);
        toggleDateInput(); // Init
    }
});
</script>
@endpush
