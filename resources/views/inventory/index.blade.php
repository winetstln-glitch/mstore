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
                                    <th class="text-end pe-4" style="width: 80px;">{{ __('Act') }}</th>
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
                                            <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Return this asset?') }}')">
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
                                    <th class="pe-4 py-3 text-end" style="width: 120px;">{{ __('Actions') }}</th>
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
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('inventory.assets.index', $item->id) }}" class="btn btn-sm btn-outline-info" title="{{ __('Assets') }}">
                                                    <i class="fa-solid fa-barcode"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
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

            <!-- Recent Transactions -->
            <div class="card shadow-sm border-0 inventory-panel">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Pickups') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Date') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Type') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('User') }}</th>
                                    <th class="py-3">{{ __('Item') }}</th>
                                    <th class="py-3 text-end">{{ __('Qty') }}</th>
                                    <!-- Hidden on mobile -->
                                    <th class="d-none d-md-table-cell py-3">{{ __('Desc') }}</th>
                                    <th class="pe-4 py-3 text-end">{{ __('Proof') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 80px;">{{ __('Act') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="ps-4 small">{{ $transaction->created_at->format('d M') }}</td>
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
                                        <td class="text-end small text-danger">-{{ $transaction->quantity }}</td>
                                        <td class="d-none d-md-table-cell small text-muted text-truncate" style="max-width: 150px;">{{ $transaction->description ?: '-' }}</td>
                                        <td class="pe-4 text-end">
                                            @if($transaction->proof_image)
                                                <a href="{{ Storage::url($transaction->proof_image) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fa-solid fa-image"></i>
                                                </a>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            @if(Auth::id() === $transaction->user_id || Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
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
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
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

@push('styles')
<style>
    .inventory-page .inventory-panel {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(59, 130, 246, 0.12);
        border-radius: 1.2rem;
        overflow: hidden;
    }

    .inventory-page .inventory-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.03) 100%);
        border-bottom: 1px solid rgba(59, 130, 246, 0.18);
    }

    .inventory-page .table thead th {
        background: rgba(148, 163, 184, 0.12);
    }

    .inventory-page .text-gray-800 {
        color: var(--bs-body-color) !important;
    }

    [data-bs-theme="dark"] .inventory-page .inventory-panel {
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .inventory-page .inventory-panel .card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-bottom-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .inventory-page .table thead th {
        background: rgba(51, 65, 85, 0.5);
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .inventory-page .table tbody td {
        border-color: #334155;
    }

    @media (max-width: 767.98px) {
        .inventory-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .inventory-page .inventory-toolbar {
            display: grid !important;
            grid-template-columns: 1fr 1fr;
        }

        .inventory-page .inventory-toolbar .btn-group,
        .inventory-page .inventory-toolbar .dropdown {
            grid-column: span 2;
            width: 100%;
        }

        .inventory-page .inventory-toolbar .btn-group .btn {
            flex: 1 1 0;
        }

        .inventory-page .inventory-toolbar > .btn,
        .inventory-page .inventory-toolbar > a.btn {
            width: 100%;
        }

        .inventory-page .inventory-panel {
            border-radius: 1rem;
        }
    }
</style>
@endpush

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Item') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type Group') }}</label>
                        <select name="type_group" class="form-select" required>
                            <option value="material" {{ request('type_group') == 'material' ? 'selected' : '' }}>{{ __('Material / Device (Consumable)') }}</option>
                            <option value="tool" {{ request('type_group') == 'tool' ? 'selected' : '' }}>{{ __('Tool / Asset (Returnable)') }}</option>
                        </select>
                        <div class="form-text small text-muted">
                            {{ __('Select "Material" for consumables/devices given to customers. Select "Tool" for equipment used by technicians.') }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="category" class="form-select" required>
                                <option value="device">Device (Perangkat Aktif)</option>
                                <option value="fiber">Fiber (Material Pasif)</option>
                                <option value="tool">Tool (Alat Kerja)</option>
                                <option value="vehicle">Vehicle (Kendaraan)</option>
                                <option value="general">General (Umum)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Type') }}</label>
                            <input type="text" name="type" class="form-control" placeholder="e.g. Router, Cable">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Brand') }}</label>
                            <input type="text" name="brand" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Unit') }}</label>
                        <input type="text" name="unit" class="form-control" placeholder="e.g. pcs, meter, roll" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Initial Stock') }}</label>
                        <input type="number" name="stock" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Price') }} (per unit)</label>
                        <input type="number" name="price" class="form-control" value="0" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
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
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Name') }}</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type Group') }}</label>
                        <select name="type_group" id="editTypeGroup" class="form-select" required>
                            <option value="material">{{ __('Material / Device (Consumable)') }}</option>
                            <option value="tool">{{ __('Tool / Asset (Returnable)') }}</option>
                        </select>
                    </div>
                    <div class="row">
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Type') }}</label>
                            <input type="text" name="type" id="editType" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Brand') }}</label>
                            <input type="text" name="brand" id="editBrand" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" id="editModel" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Unit') }}</label>
                        <input type="text" name="unit" id="editUnit" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Stock') }}</label>
                        <input type="number" name="stock" id="editStock" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Price') }}</label>
                        <input type="number" name="price" id="editPrice" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
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
        var editItemModal = document.getElementById('editItemModal');
        editItemModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var action = button.getAttribute('data-action');
            var name = button.getAttribute('data-name');
            var category = button.getAttribute('data-category');
            var typeGroup = button.getAttribute('data-type_group');
            var type = button.getAttribute('data-type');
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
            editItemModal.querySelector('#editStock').value = stock;
            editItemModal.querySelector('#editPrice').value = price;
            editItemModal.querySelector('#editDescription').value = description;
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
    });
</script>
@endsection
