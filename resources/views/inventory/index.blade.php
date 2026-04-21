@extends('layouts.app')

@section('content')
<div class="container-fluid inventory-page py-2 py-md-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Responsive Header: Stacks on mobile, Horizontal on desktop -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <h1 class="h3 mb-0 text-body text-truncate" style="max-width: 100%;">
                    @if(request('type_group') == 'tool')
                        {{ __('Tools & Assets') }}
                    @elseif(request('type_group') == 'material')
                        {{ __('Materials & Devices') }}
                    @else
                        {{ __('Inventory Management') }}
                    @endif
                </h1>
                
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end inventory-toolbar">
                    <!-- Filter Group: Stays together but allows wrapping if needed -->
                    <div class="btn-group" role="group">
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary {{ !request('type_group') ? 'active' : '' }}">{{ __('All') }}</a>
                        <a href="{{ route('inventory.index', ['type_group' => 'tool']) }}" class="btn btn-outline-secondary {{ request('type_group') == 'tool' ? 'active' : '' }}">{{ __('Tools') }}</a>
                        <a href="{{ route('inventory.index', ['type_group' => 'material']) }}" class="btn btn-outline-secondary {{ request('type_group') == 'material' ? 'active' : '' }}">{{ __('Materials') }}</a>
                    </div>

                    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
                    <!-- Category Dropdown: Icon only on small screens to save space -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-filter d-md-none"></i>
                            <span class="d-none d-md-inline">{{ request('category') ? ucfirst(request('category')) : __('Categories') }}</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('inventory.index', ['type_group' => request('type_group')]) }}">{{ __('All Categories') }}</a></li>
                            @foreach($categories as $cat)
                                <li><a class="dropdown-item" href="{{ route('inventory.index', ['category' => $cat, 'type_group' => request('type_group')]) }}">{{ ucfirst($cat) }}</a></li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Export/Import Dropdown -->
                    <div class="dropdown">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical d-md-none"></i>
                            <span class="d-none d-md-inline"><i class="fa-solid fa-file-export me-1"></i> {{ __('Actions') }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('inventory.export.excel') }}"><i class="fa-solid fa-file-excel me-2 text-success"></i> {{ __('Export Excel') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.export.pdf') }}" target="_blank"><i class="fa-solid fa-file-pdf me-2 text-danger"></i> {{ __('Export PDF') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importItemModal"><i class="fa-solid fa-file-import me-2 text-primary"></i> {{ __('Import Excel') }}</a></li>
                        </ul>
                    </div>
                    
                    <!-- Add Button -->
                    <button type="button" class="btn btn-success flex-grow-1 flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-sm-inline">{{ __('Add Item') }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-success flex-grow-1 flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#stockInModal">
                        <i class="fa-solid fa-arrow-down-wide-short me-1"></i> <span class="d-none d-sm-inline">{{ __('Stock In') }}</span>
                    </button>
                    @endif
                    
                    <!-- Return Button: Icon only on mobile -->
                    <a href="{{ route('inventory.my_assets') }}" class="btn btn-outline-warning" title="{{ __('Return Tool') }}">
                        <i class="fa-solid fa-rotate-left"></i> <span class="d-none d-sm-inline ms-1">{{ __('Return') }}</span>
                    </a>
                    
                    <!-- Pickup Button: Icon only on mobile -->
                    <a href="{{ route('inventory.pickup', ['type_group' => request('type_group')]) }}" class="btn btn-primary flex-grow-1 flex-md-grow-0" title="{{ __('Pickup Item') }}">
                        <i class="fa-solid fa-box-open me-1"></i> <span class="d-none d-sm-inline">{{ __('Pickup') }}</span>
                    </a>
                </div>
            </div>

            <!-- Dashboard Stats: 2 columns on mobile (col-6), 4 on desktop -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <div class="row mb-4">
                <div class="col-6 col-md-6 col-xl-3 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        {{ __('Stock Value') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 small">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-warehouse fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-xl-3 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        {{ __('Total Items') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalItems }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-boxes-stacked fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-xl-3 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        {{ __('Purchases') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 small">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-cart-shopping fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-xl-3 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        {{ __('Sales/Usage') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 small">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-money-bill-transfer fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- My Assigned Assets (For Technicians/Coordinators) -->
            @if(isset($myAssets) && $myAssets->count() > 0)
            <div class="card shadow-sm border-0 mb-4 border-left-info inventory-panel">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info"><i class="fa-solid fa-toolbox me-2"></i>{{ __('My Assets') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-responsive-mobile">
                            <thead class="">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Asset Name') }}</th>
                                    <!-- Hidden on mobile to save space -->
                                    <th class="d-none d-md-table-cell">{{ __('Serial') }}</th>
                                    <th class="py-3">{{ __('Status') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell">{{ __('Condition') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell">{{ __('Note') }}</th>
                                    <th class="text-end pe-4" style="width: 110px;">{{ __('Actions') }}</th>
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
                                        <td>
                                            <span class="badge bg-primary small">{{ __('Deployed') }}</span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            @if($asset->condition == 'good')
                                                <span class="badge bg-success small">{{ __('Good') }}</span>
                                            @else
                                                <span class="badge bg-danger small">{{ __('Damaged') }}</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell small text-muted text-truncate" style="max-width: 150px;">
                                            {{ $asset->meta_data['assignment_note'] ?? '-' }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                            <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Return this asset?') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning inventory-action-btn" title="{{ __('Return') }}">
                                                    <i class="fa-solid fa-rotate-left"></i>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Items List -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <div class="card shadow-sm border-0 mb-4 inventory-panel">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Inventory Items') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-responsive-mobile">
                            <thead class="">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Type') }}</th>
                                    <th class="py-3">{{ __('Name') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Category') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Brand/Model') }}</th>
                                    <th class="py-3 text-center">{{ __('Stock') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Unit') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 140px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td class="ps-4">
                                            @if($item->type_group == 'tool')
                                                <span class="badge bg-primary"><i class="fa-solid fa-toolbox d-none d-md-inline me-1"></i> {{ __('Tool') }}</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fa-solid fa-cube d-none d-md-inline me-1"></i> {{ __('Mat') }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-medium">
                                            {{ $item->name }}
                                            <div class="small text-muted d-none d-md-block text-truncate" style="max-width: 200px;">{{ Str::limit($item->description, 30) ?: '-' }}</div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge  text-dark border small">{{ ucfirst($item->category) }}</span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            {{ $item->brand ?: '-' }}
                                            @if($item->model)
                                                <div class="small text-muted text-truncate" style="max-width: 120px;">{{ $item->model }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $item->stock > 10 ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                                {{ $item->stock }}
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell small">{{ $item->unit }}</td>
                                        <td class="pe-4 text-end">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <a href="{{ route('inventory.assets.index', $item->id) }}" class="btn btn-sm btn-outline-info inventory-action-btn" title="{{ __('Assets') }}">
                                                    <i class="fa-solid fa-barcode"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary inventory-action-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#editItemModal"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->name }}"
                                                    data-category="{{ $item->category }}"
                                                    data-type_group="{{ $item->type_group }}"
                                                    data-type="{{ $item->type }}"
                                                    data-brand="{{ $item->brand }}"
                                                    data-model="{{ $item->model }}"
                                                    data-unit="{{ $item->unit }}"
                                                    data-stock="{{ $item->stock }}"
                                                    data-price="{{ $item->price }}"
                                                    data-description="{{ $item->description }}"
                                                    data-action="{{ route('inventory.update', $item->id) }}">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger inventory-action-btn">
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

            @php
                $movementPeriod = request('movement_period', 'day');
                $movementType = request('movement_type', '');
                $movementDay = request('movement_day', now()->toDateString());
                $movementMonth = request('movement_month', now()->format('Y-m'));
            @endphp

            <!-- Stock Movements -->
            <div class="card shadow-sm border-0 inventory-panel">
                <div class="card-header bg-white py-3 d-flex flex-column flex-lg-row gap-2 justify-content-between align-items-lg-center">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Stock Movements') }}</h6>
                    <form method="GET" action="{{ route('inventory.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                        @if(request('type_group'))
                            <input type="hidden" name="type_group" value="{{ request('type_group') }}">
                        @endif
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif

                        <select name="movement_type" class="form-select form-select-sm" style="min-width: 130px;">
                            <option value="">{{ __('Semua') }}</option>
                            <option value="in" {{ $movementType === 'in' ? 'selected' : '' }}>{{ __('Barang Masuk') }}</option>
                            <option value="out" {{ $movementType === 'out' ? 'selected' : '' }}>{{ __('Barang Keluar') }}</option>
                        </select>

                        <select name="movement_period" id="movementPeriod" class="form-select form-select-sm" style="min-width: 120px;">
                            <option value="day" {{ $movementPeriod === 'day' ? 'selected' : '' }}>{{ __('Per Hari') }}</option>
                            <option value="month" {{ $movementPeriod === 'month' ? 'selected' : '' }}>{{ __('Per Bulan') }}</option>
                        </select>

                        <input
                            type="date"
                            name="movement_day"
                            id="movementDayFilter"
                            class="form-control form-control-sm {{ $movementPeriod === 'day' ? '' : 'd-none' }}"
                            value="{{ $movementDay }}"
                        >

                        <input
                            type="month"
                            name="movement_month"
                            id="movementMonthFilter"
                            class="form-control form-control-sm {{ $movementPeriod === 'month' ? '' : 'd-none' }}"
                            value="{{ $movementMonth }}"
                        >

                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-filter me-1"></i>{{ __('Filter') }}
                        </button>
                        <a href="{{ route('inventory.index', array_filter(['type_group' => request('type_group'), 'category' => request('category')])) }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('Reset') }}
                        </a>
                        <a href="{{ route('inventory.movements.export.excel', request()->except('page')) }}" class="btn btn-sm btn-success">
                            <i class="fa-solid fa-file-excel me-1"></i>{{ __('Download Excel') }}
                        </a>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Date') }}</th>
                                    <th class="py-3">{{ __('Move') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Item Type') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('User') }}</th>
                                    <th class="py-3">{{ __('Item') }}</th>
                                    <th class="py-3 text-end">{{ __('Qty') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Desc') }}</th>
                                    <th class="d-none d-md-table-cell py-3 text-end">{{ __('Cost') }}</th>
                                    <th class="pe-4 py-3 text-end">{{ __('Proof') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 110px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="ps-4 small">{{ $transaction->created_at->format('d M H:i') }}</td>
                                        <td>
                                            @if($transaction->type === 'in')
                                                <span class="badge bg-success">IN</span>
                                            @else
                                                <span class="badge bg-danger">OUT</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            @if($transaction->item->type_group == 'tool')
                                                <span class="badge bg-primary small"><i class="fa-solid fa-toolbox"></i></span>
                                            @else
                                                <span class="badge bg-secondary small"><i class="fa-solid fa-cube"></i></span>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell small text-truncate" style="max-width: 100px;">{{ $transaction->user->name }}</td>
                                        <td>
                                            <div class="fw-bold text-truncate" style="max-width: 150px;">{{ $transaction->item->name }}</div>
                                        </td>
                                        <td class="text-end small {{ $transaction->type === 'in' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'in' ? '+' : '-' }}{{ $transaction->quantity }}
                                        </td>
                                        <td class="d-none d-md-table-cell small text-muted text-truncate" style="max-width: 150px;">{{ $transaction->description ?: '-' }}</td>
                                        <td class="d-none d-md-table-cell text-end small">
                                            @if(!is_null($transaction->total_cost))
                                                Rp {{ number_format((float) $transaction->total_cost, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            @if($transaction->proof_image)
                                                <a href="{{ Storage::url($transaction->proof_image) }}" target="_blank" class="btn btn-sm btn-outline-info inventory-action-btn">
                                                    <i class="fa-solid fa-image"></i>
                                                </a>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            @if($transaction->type === 'out' && (Auth::id() === $transaction->user_id || Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance')))
                                            <div class="d-inline-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary inventory-action-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editPickupModal"
                                                data-id="{{ $transaction->id }}"
                                                data-item="{{ $transaction->item->name }}"
                                                data-quantity="{{ $transaction->quantity }}"
                                                data-unit="{{ $transaction->item->unit }}"
                                                data-description="{{ $transaction->description }}"
                                                data-action="{{ route('inventory.pickup.update', $transaction->id) }}">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form action="{{ route('inventory.pickup.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete pickup?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger inventory-action-btn">
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
                @if($transactions->hasPages())
                    <div class="card-footer bg-white py-3">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('inventory.stock-in.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Barang Masuk (Pembelian Stok)') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        {{ __('Gunakan form ini untuk pembelian stok agar histori gudang dan biaya pembelian tercatat otomatis.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item') }}</label>
                        <select name="inventory_item_id" id="stockInItemId" class="form-select" required>
                            <option value="">{{ __('Pilih item') }}</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                    {{ $item->name }} ({{ __('Stok') }}: {{ $item->stock }} {{ $item->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Qty Masuk') }}</label>
                            <div class="input-group">
                                <input type="number" name="quantity" min="1" class="form-control" required>
                                <span class="input-group-text" id="stockInUnit">pcs</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Harga Modal / Unit') }}</label>
                            <input type="number" name="unit_cost" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Tanggal Beli') }}</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Supplier') }}</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="Contoh: PT ABC">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('No Referensi / Invoice') }}</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="INV-001/PO-001">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Keterangan') }}</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Opsional') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Simpan Barang Masuk') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Item') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        <strong>{{ __('Simple Input') }}:</strong> {{ __('Isi nama, kelompok, kategori, unit, stok awal, dan harga modal. Kolom lainnya opsional.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Kabel Fiber 1 Core" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Type Group') }}</label>
                            <select name="type_group" id="addTypeGroup" class="form-select" required>
                                <option value="material" {{ request('type_group') == 'material' ? 'selected' : '' }}>{{ __('Material / Device') }}</option>
                                <option value="tool" {{ request('type_group') == 'tool' ? 'selected' : '' }}>{{ __('Tool / Asset') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="category" id="addCategory" class="form-select" required>
                                <option value="device">Device (Perangkat Aktif)</option>
                                <option value="fiber">Fiber (Material Pasif)</option>
                                <option value="tool">Tool (Alat Kerja)</option>
                                <option value="vehicle">Vehicle (Kendaraan)</option>
                                <option value="general">General (Umum)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Unit') }}</label>
                            <input type="text" name="unit" id="addUnit" class="form-control" placeholder="pcs, meter, roll, unit" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Initial Stock') }}</label>
                            <input type="number" name="stock" class="form-control" value="0" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Price') }} ({{ __('Modal per unit') }})</label>
                        <input type="number" name="price" class="form-control" value="0" min="0" step="0.01" required>
                    </div>

                    <button type="button" class="btn btn-link px-0" data-bs-toggle="collapse" data-bs-target="#addAdvancedFields" aria-expanded="false">
                        {{ __('Advanced Details (Optional)') }}
                    </button>
                    <div class="collapse" id="addAdvancedFields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Type') }}</label>
                                <input type="text" name="type" class="form-control" placeholder="Contoh: Router, Cable">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Brand') }}</label>
                                <input type="text" name="brand" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form id="editItemForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Item') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        <strong>{{ __('Simple Edit') }}:</strong> {{ __('Ubah data utama dan gunakan penyesuaian stok (+/-).') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Name') }}</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Type Group') }}</label>
                            <select name="type_group" id="editTypeGroup" class="form-select" required>
                                <option value="material">{{ __('Material / Device') }}</option>
                                <option value="tool">{{ __('Tool / Asset') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="category" id="editCategory" class="form-select" required>
                                <option value="device">Device (Perangkat Aktif)</option>
                                <option value="fiber">Fiber (Material Pasif)</option>
                                <option value="tool">Tool (Alat Kerja)</option>
                                <option value="vehicle">Vehicle (Kendaraan)</option>
                                <option value="general">General (Umum)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Unit') }}</label>
                            <input type="text" name="unit" id="editUnit" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Price') }} ({{ __('Modal per unit') }})</label>
                            <input type="number" name="price" id="editPrice" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Current Stock') }}</label>
                            <input type="number" id="editCurrentStock" class="form-control" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Stock Adjustment (+/-)') }}</label>
                            <input type="number" name="stock_adjustment" id="editStockAdjustment" class="form-control" value="0">
                            <div class="form-text">{{ __('Contoh: +10 untuk barang masuk, -2 untuk koreksi keluar.') }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="stock" id="editStock">

                    <button type="button" class="btn btn-link px-0" data-bs-toggle="collapse" data-bs-target="#editAdvancedFields" aria-expanded="false">
                        {{ __('Advanced Details (Optional)') }}
                    </button>
                    <div class="collapse" id="editAdvancedFields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Type') }}</label>
                                <input type="text" name="type" id="editType" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Brand') }}</label>
                                <input type="text" name="brand" id="editBrand" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" id="editModel" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Pickup Modal -->
<div class="modal fade" id="editPickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form id="editPickupForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Pickup') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Name') }}</label>
                        <input type="text" id="editPickupItemName" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quantity') }}</label>
                        <div class="input-group">
                            <input type="number" name="quantity" id="editPickupQuantity" class="form-control" min="1" required>
                            <span class="input-group-text" id="editPickupUnit"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="editPickupDescription" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Import Item Modal -->
<div class="modal fade" id="importItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Import Items') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <small>{{ __('Please use the template file to import items.') }}</small>
                        <br>
                        <a href="{{ route('inventory.import.template') }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fa-solid fa-download me-1"></i> {{ __('Download Template') }}
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Excel File') }}</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function applyDefaultByTypeGroup(typeGroupValue, categorySelect, unitInput) {
            if (!categorySelect || !unitInput) {
                return;
            }

            if (typeGroupValue === 'tool') {
                if (!['tool', 'vehicle', 'general'].includes(categorySelect.value)) {
                    categorySelect.value = 'tool';
                }
                if (!unitInput.value) {
                    unitInput.value = 'unit';
                }
            } else {
                if (!['device', 'fiber', 'general'].includes(categorySelect.value)) {
                    categorySelect.value = 'device';
                }
                if (!unitInput.value) {
                    unitInput.value = 'pcs';
                }
            }
        }

        var addTypeGroup = document.getElementById('addTypeGroup');
        var addCategory = document.getElementById('addCategory');
        var addUnit = document.getElementById('addUnit');
        if (addTypeGroup && addCategory && addUnit) {
            addTypeGroup.addEventListener('change', function () {
                applyDefaultByTypeGroup(addTypeGroup.value, addCategory, addUnit);
            });
            applyDefaultByTypeGroup(addTypeGroup.value, addCategory, addUnit);
        }

        var stockInItemSelect = document.getElementById('stockInItemId');
        var stockInUnit = document.getElementById('stockInUnit');
        if (stockInItemSelect && stockInUnit) {
            stockInItemSelect.addEventListener('change', function () {
                var option = stockInItemSelect.options[stockInItemSelect.selectedIndex];
                stockInUnit.textContent = option ? (option.getAttribute('data-unit') || 'pcs') : 'pcs';
            });
        }

        var editItemModal = document.getElementById('editItemModal');
        editItemModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var action = button.getAttribute('data-action');
            var name = button.getAttribute('data-name');
            var category = button.getAttribute('data-category');
            var typeGroup = button.getAttribute('data-type_group');
            var type = button.getAttribute('data-type');@extends('layouts.app')

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
                        @case('tool') {{ __('Tools & Assets') }} @break
                        @case('material') {{ __('Materials & Devices') }} @break
                        @default {{ __('Inventory Management') }}
                    @endswitch
                </h1>
                
                {{-- Toolbar Buttons --}}
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end inventory-toolbar">
                    
                    {{-- Filter Group (All/Tools/Materials) --}}
                    <div class="btn-group" role="group">
                        @php
                            $filterLinks = [
                                '' => ['label' => __('All'), 'param' => []],
                                'tool' => ['label' => __('Tools'), 'param' => ['type_group' => 'tool']],
                                'material' => ['label' => __('Materials'), 'param' => ['type_group' => 'material']],
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
                                    {{ request('category') ? ucfirst(request('category')) : __('Categories') }}
                                </span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.index', ['type_group' => request('type_group')]) }}">
                                        {{ __('All Categories') }}
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
                                    <i class="fa-solid fa-file-export me-1"></i> {{ __('Actions') }}
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.export.excel') }}">
                                        <i class="fa-solid fa-file-excel me-2 text-success"></i> {{ __('Export Excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.export.pdf') }}" target="_blank">
                                        <i class="fa-solid fa-file-pdf me-2 text-danger"></i> {{ __('Export PDF') }}
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importItemModal">
                                        <i class="fa-solid fa-file-import me-2 text-primary"></i> {{ __('Import Excel') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        {{-- Tombol Tambah Item --}}
                        <button type="button" class="btn btn-success flex-grow-1 flex-md-grow-0" 
                                data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fa-solid fa-plus me-1"></i> 
                            <span class="d-none d-sm-inline">{{ __('Add Item') }}</span>
                        </button>
                        
                        {{-- Tombol Stock Masuk --}}
                        <button type="button" class="btn btn-outline-success flex-grow-1 flex-md-grow-0" 
                                data-bs-toggle="modal" data-bs-target="#stockInModal">
                            <i class="fa-solid fa-arrow-down-wide-short me-1"></i> 
                            <span class="d-none d-sm-inline">{{ __('Stock In') }}</span>
                        </button>
                    @endif
                    
                    {{-- Tombol Return --}}
                    <a href="{{ route('inventory.my_assets') }}" class="btn btn-outline-warning" title="{{ __('Return Tool') }}">
                        <i class="fa-solid fa-rotate-left"></i> 
                        <span class="d-none d-sm-inline ms-1">{{ __('Return') }}</span>
                    </a>
                    
                    {{-- Tombol Pickup --}}
                    <a href="{{ route('inventory.pickup', ['type_group' => request('type_group')]) }}" 
                       class="btn btn-primary flex-grow-1 flex-md-grow-0" title="{{ __('Pickup Item') }}">
                        <i class="fa-solid fa-box-open me-1"></i> 
                        <span class="d-none d-sm-inline">{{ __('Pickup') }}</span>
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
                            'label' => __('Stock Value'),
                            'value' => 'Rp ' . number_format($totalStockValue, 0, ',', '.'),
                            'icon'  => 'fa-warehouse',
                            'color' => 'primary',
                        ],
                        [
                            'label' => __('Total Items'),
                            'value' => $totalItems,
                            'icon'  => 'fa-boxes-stacked',
                            'color' => 'success',
                        ],
                        [
                            'label' => __('Purchases'),
                            'value' => 'Rp ' . number_format($totalPurchases, 0, ',', '.'),
                            'icon'  => 'fa-cart-shopping',
                            'color' => 'info',
                        ],
                        [
                            'label' => __('Sales/Usage'),
                            'value' => 'Rp ' . number_format($totalSales, 0, ',', '.'),
                            'icon'  => 'fa-money-bill-transfer',
                            'color' => 'warning',
                        ],
                    ];
                @endphp
                @foreach($stats as $stat)
                <div class="col-6 col-xl-3 mb-3">
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
                        <i class="fa-solid fa-toolbox me-2"></i>{{ __('My Assets') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Asset Name') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Serial') }}</th>
                                    <th class="py-3">{{ __('Status') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Condition') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Note') }}</th>
                                    <th class="text-end pe-4 py-3" style="width: 100px;">{{ __('Actions') }}</th>
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
                                    <td><span class="badge bg-primary small">{{ __('Deployed') }}</span></td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-{{ $asset->condition == 'good' ? 'success' : 'danger' }} small">
                                            {{ $asset->condition == 'good' ? __('Good') : __('Damaged') }}
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell small text-muted text-truncate" style="max-width: 150px;">
                                        {{ $asset->meta_data['assignment_note'] ?? '-' }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="{{ route('inventory.assets.return', $asset->id) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('{{ __('Return this asset?') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="{{ __('Return') }}">
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
                    <h6 class="m-0 fw-bold text-primary">{{ __('Inventory Items') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Type') }}</th>
                                    <th class="py-3">{{ __('Name') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Category') }}</th>
                                    <th class="d-none d-lg-table-cell py-3">{{ __('Brand/Model') }}</th>
                                    <th class="py-3 text-center">{{ __('Stock') }}</th>
                                    <th class="d-none d-md-table-cell py-3">{{ __('Unit') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 140px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-{{ $item->type_group == 'tool' ? 'primary' : 'secondary' }}">
                                            <i class="fa-solid fa-{{ $item->type_group == 'tool' ? 'toolbox' : 'cube' }} d-none d-md-inline me-1"></i>
                                            {{ $item->type_group == 'tool' ? __('Tool') : __('Mat') }}
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
            var brand = button.getAttribute('data-brand');
            var model = button.getAttribute('data-model');
            var unit = button.getAttribute('data-unit');
            var stock = button.getAttribute('data-stock');
            var price = button.getAttribute('data-price');
            var description = button.getAttribute('data-description');

            var form = editItemModal.querySelector('#editItemForm');
            form.action = action;
            
            editItemModal.querySelector('#editName').value = name;
            editItemModal.querySelector('#editCategory').value = category;
            if(typeGroup) {
                editItemModal.querySelector('#editTypeGroup').value = typeGroup;
            }
            editItemModal.querySelector('#editType').value = type;
            editItemModal.querySelector('#editBrand').value = brand;
            editItemModal.querySelector('#editModel').value = model;
            editItemModal.querySelector('#editUnit').value = unit;
            editItemModal.querySelector('#editCurrentStock').value = stock;
            editItemModal.querySelector('#editStock').value = stock;
            editItemModal.querySelector('#editStockAdjustment').value = 0;
            editItemModal.querySelector('#editPrice').value = price;
            editItemModal.querySelector('#editDescription').value = description;

            applyDefaultByTypeGroup(editItemModal.querySelector('#editTypeGroup').value, editItemModal.querySelector('#editCategory'), editItemModal.querySelector('#editUnit'));
        });

        var editTypeGroup = document.getElementById('editTypeGroup');
        if (editTypeGroup) {
            editTypeGroup.addEventListener('change', function () {
                applyDefaultByTypeGroup(editTypeGroup.value, document.getElementById('editCategory'), document.getElementById('editUnit'));
            });
        }

        var editItemForm = document.getElementById('editItemForm');
        if (editItemForm) {
            editItemForm.addEventListener('submit', function (e) {
                var currentStock = parseInt(document.getElementById('editCurrentStock').value || '0', 10);
                var adjustment = parseInt(document.getElementById('editStockAdjustment').value || '0', 10);
                var finalStock = currentStock + adjustment;
                if (finalStock < 0) {
                    e.preventDefault();
                    alert('{{ __("Stock akhir tidak boleh minus.") }}');
                    return;
                }
                document.getElementById('editStock').value = finalStock;
            });
        });

        var editPickupModal = document.getElementById('editPickupModal');
        editPickupModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var action = button.getAttribute('data-action');
            var itemName = button.getAttribute('data-item');
            var quantity = button.getAttribute('data-quantity');
            var unit = button.getAttribute('data-unit');
            var description = button.getAttribute('data-description');

            var form = editPickupModal.querySelector('#editPickupForm');
            form.action = action;

            editPickupModal.querySelector('#editPickupItemName').value = itemName;
            editPickupModal.querySelector('#editPickupQuantity').value = quantity;
            editPickupModal.querySelector('#editPickupUnit').textContent = unit;
            editPickupModal.querySelector('#editPickupDescription').value = description;
        });

        var movementPeriod = document.getElementById('movementPeriod');
        var movementDayFilter = document.getElementById('movementDayFilter');
        var movementMonthFilter = document.getElementById('movementMonthFilter');
        if (movementPeriod && movementDayFilter && movementMonthFilter) {
            var toggleMovementPeriodInput = function () {
                if (movementPeriod.value === 'month') {
                    movementMonthFilter.classList.remove('d-none');
                    movementDayFilter.classList.add('d-none');
                } else {
                    movementDayFilter.classList.remove('d-none');
                    movementMonthFilter.classList.add('d-none');
                }
            };
            movementPeriod.addEventListener('change', toggleMovementPeriodInput);
            toggleMovementPeriodInput();
        }
    });
</script>
@endsection
