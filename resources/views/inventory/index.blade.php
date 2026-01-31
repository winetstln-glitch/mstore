@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <!-- Header Section -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <h1 class="h3 mb-0 text-gray-800 text-center text-md-start">
                    @if(request('type_group') == 'tool')
                        {{ __('Tools & Assets Inventory') }}
                    @elseif(request('type_group') == 'material')
                        {{ __('Materials & Devices Inventory') }}
                    @else
                        {{ __('Inventory Management') }}
                    @endif
                </h1>
                
                <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                    <!-- Filter Group -->
                    <div class="btn-group" role="group">
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary {{ !request('type_group') ? 'active' : '' }}">{{ __('All') }}</a>
                        <a href="{{ route('inventory.index', ['type_group' => 'tool']) }}" class="btn btn-outline-secondary {{ request('type_group') == 'tool' ? 'active' : '' }}">{{ __('Tools') }}</a>
                        <a href="{{ route('inventory.index', ['type_group' => 'material']) }}" class="btn btn-outline-secondary {{ request('type_group') == 'material' ? 'active' : '' }}">{{ __('Materials') }}</a>
                    </div>

                    @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
                    <!-- Category Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-filter me-1"></i> <span class="d-none d-sm-inline">{{ request('category') ? ucfirst(request('category')) : __('Category') }}</span>
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
                            <i class="fa-solid fa-file-export"></i> <span class="d-none d-sm-inline ms-1">{{ __('Export') }}</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('inventory.export.excel') }}"><i class="fa-solid fa-file-excel me-2 text-success"></i> {{ __('Export Excel') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.export.pdf') }}" target="_blank"><i class="fa-solid fa-file-pdf me-2 text-danger"></i> {{ __('Export PDF') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importItemModal"><i class="fa-solid fa-file-import me-2 text-primary"></i> {{ __('Import Excel') }}</a></li>
                        </ul>
                    </div>

                    <!-- Add Button -->
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal" title="{{ __('Add New Item') }}">
                        <i class="fa-solid fa-plus"></i> <span class="d-none d-sm-inline ms-1">{{ __('Add') }}</span>
                    </button>
                    @endif

                    <!-- Return Tool Button -->
                    <a href="{{ route('inventory.my_assets') }}" class="btn btn-outline-warning" title="{{ __('Return Tool') }}">
                        <i class="fa-solid fa-rotate-left"></i> <span class="d-none d-sm-inline ms-1">{{ __('Return') }}</span>
                    </a>

                    <!-- Pickup Button -->
                    <a href="{{ route('inventory.pickup', ['type_group' => request('type_group')]) }}" class="btn btn-primary" title="{{ __('Pickup Item') }}">
                        <i class="fa-solid fa-box-open"></i> <span class="d-none d-sm-inline ms-1">{{ __('Pickup') }}</span>
                    </a>
                </div>
            </div>

            <!-- Dashboard Stats -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
            <div class="row mb-4 g-3">
                <div class="col-6 col-xl-3 mb-0">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        {{ __('Total Stock') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-warehouse fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3 mb-0">
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

                <div class="col-6 col-xl-3 mb-0">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        {{ __('Purchases') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa-solid fa-cart-shopping fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3 mb-0">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        {{ __('Sales/Usage') }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
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
            <div class="card shadow-sm border-0 mb-4 border-left-info">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info"><i class="fa-solid fa-toolbox me-2"></i>{{ __('My Assigned Assets / Tools') }}</h6>
                </div>
                <div class="card-body p-0">
                    <!-- Mobile View -->
                    <div class="d-md-none">
                        @foreach($myAssets as $asset)
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $asset->item->name }}</h6>
                                        <div class="small text-muted mb-1">{{ $asset->asset_code }}</div>
                                        <div class="small text-muted">{{ $asset->serial_number }}</div>
                                    </div>
                                    @if($asset->condition == 'good')
                                        <span class="badge bg-success">{{ __('Good') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Damaged') }}</span>
                                    @endif
                                </div>
                                
                                @if(isset($asset->meta_data['assignment_note']))
                                    <div class="alert alert-light p-2 mb-2 small border">
                                        <i class="fa-solid fa-quote-left text-muted me-1"></i> {{ $asset->meta_data['assignment_note'] }}
                                    </div>
                                @endif

                                <div class="d-grid mt-2">
                                    <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to return this asset?') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                                            <i class="fa-solid fa-rotate-left me-1"></i> {{ __('Return Asset') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desktop View -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Asset Name') }}</th>
                                    <th>{{ __('Serial Number') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Condition') }}</th>
                                    <th>{{ __('Assignment Note') }}</th>
                                    <th class="text-end pe-4">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myAssets as $asset)
                                    <tr>
                                        <td class="ps-4 fw-bold">
                                            {{ $asset->item->name }}
                                            <div class="small text-muted">{{ $asset->asset_code }}</div>
                                        </td>
                                        <td>{{ $asset->serial_number }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ __('Deployed (To You)') }}</span>
                                        </td>
                                        <td>
                                            @if($asset->condition == 'good')
                                                <span class="badge bg-success">{{ __('Good') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('Damaged') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($asset->meta_data['assignment_note']))
                                                <i class="fa-solid fa-quote-left text-muted me-1"></i> {{ $asset->meta_data['assignment_note'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to return this asset?') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('Return') }}
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
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Inventory Items') }}</h6>
                </div>
                <div class="card-body p-0">
                    <!-- Mobile View -->
                    <div class="d-md-none">
                        @forelse($items as $item)
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        @if($item->type_group == 'tool')
                                            <span class="badge bg-primary mb-1"><i class="fa-solid fa-toolbox me-1"></i> {{ __('Tool') }}</span>
                                        @else
                                            <span class="badge bg-secondary mb-1"><i class="fa-solid fa-cube me-1"></i> {{ __('Material') }}</span>
                                        @endif
                                        <h6 class="mb-0 fw-bold">{{ $item->name }}</h6>
                                        <div class="small text-muted">{{ $item->brand }} {{ $item->model }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="h5 mb-0 fw-bold {{ $item->stock > 10 ? 'text-success' : 'text-danger' }}">
                                            {{ $item->stock }} <small class="text-muted fs-6">{{ $item->unit }}</small>
                                        </div>
                                        <div class="small text-muted">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="badge bg-light text-dark border">{{ ucfirst($item->category) }}</span>
                                    
                                    <div class="btn-group">
                                        <a href="{{ route('inventory.assets.index', $item->id) }}" class="btn btn-sm btn-outline-info" title="{{ __('Manage Assets') }}">
                                            <i class="fa-solid fa-barcode"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editItemModal"
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
                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this item?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-0 rounded-end">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-boxes-stacked fa-2x mb-3 opacity-25"></i>
                                <p class="mb-0">{{ __('No items found.') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Desktop View -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Type') }}</th>
                                    <th class="py-3">{{ __('Name') }}</th>
                                    <th class="py-3">{{ __('Category') }}</th>
                                    <th class="py-3">{{ __('Brand/Model') }}</th>
                                    <th class="py-3">{{ __('Stock') }}</th>
                                    <th class="py-3">{{ __('Unit') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 150px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td class="ps-4">
                                            @if($item->type_group == 'tool')
                                                <span class="badge bg-primary"><i class="fa-solid fa-toolbox me-1"></i> {{ __('Tool') }}</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fa-solid fa-cube me-1"></i> {{ __('Material') }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-medium">
                                            {{ $item->name }}
                                            <div class="small text-muted">{{ Str::limit($item->description, 30) ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ ucfirst($item->category) }}</span>
                                            @if($item->type)
                                                <div class="small text-muted">{{ ucfirst($item->type) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $item->brand ?: '-' }}
                                            @if($item->model)
                                                <div class="small text-muted">{{ $item->model }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $item->stock > 10 ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                                {{ $item->stock }}
                                            </span>
                                        </td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ route('inventory.assets.index', $item->id) }}" class="btn btn-sm btn-outline-info me-1" title="{{ __('Manage Assets') }}">
                                                <i class="fa-solid fa-barcode"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editItemModal"
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
                                            <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this item?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
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
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Pickups') }}</h6>
                </div>
                <div class="card-body p-0">
                    <!-- Mobile View -->
                    <div class="d-md-none">
                        @forelse($transactions as $transaction)
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $transaction->item->name }}</h6>
                                        <div class="small text-muted">
                                            <i class="fa-regular fa-clock me-1"></i> {{ $transaction->created_at->format('d M Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-danger fs-5">-{{ $transaction->quantity }} {{ $transaction->item->unit }}</div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar avatar-xs bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                        <i class="fa-solid fa-user fa-2xs text-secondary"></i>
                                    </div>
                                    <span class="small fw-medium">{{ $transaction->user->name }}</span>
                                </div>

                                @if($transaction->description)
                                    <div class="small text-muted fst-italic mb-2">"{{ $transaction->description }}"</div>
                                @endif

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div>
                                        @if($transaction->proof_image)
                                            <a href="{{ Storage::url($transaction->proof_image) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fa-solid fa-image me-1"></i> {{ __('Proof') }}
                                            </a>
                                        @endif
                                    </div>

                                    @if(Auth::id() === $transaction->user_id || Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance'))
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
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
                                        <form action="{{ route('inventory.pickup.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this pickup?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-0 rounded-end">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-clock-rotate-left fa-2x mb-3 opacity-25"></i>
                                <p class="mb-0">{{ __('No history found.') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Desktop View -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Date') }}</th>
                                    <th class="py-3">{{ __('Type') }}</th>
                                    <th class="py-3">{{ __('User') }}</th>
                                    <th class="py-3">{{ __('Item') }}</th>
                                    <th class="py-3">{{ __('Quantity') }}</th>
                                    <th class="py-3">{{ __('Description') }}</th>
                                    <th class="pe-4 py-3 text-end">{{ __('Proof') }}</th>
                                    <th class="pe-4 py-3 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="ps-4">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                        <td>
                                            @if($transaction->item->type_group == 'tool')
                                                <span class="badge bg-primary"><i class="fa-solid fa-toolbox me-1"></i> {{ __('Tool') }}</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fa-solid fa-cube me-1"></i> {{ __('Material') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->user->name }}</td>
                                        <td>{{ $transaction->item->name }}</td>
                                        <td class="fw-bold text-danger">-{{ $transaction->quantity }} {{ $transaction->item->unit }}</td>
                                        <td>{{ $transaction->description ?: '-' }}</td>
                                        <td class="pe-4 text-end">
                                            @if($transaction->proof_image)
                                                <a href="{{ Storage::url($transaction->proof_image) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fa-solid fa-image"></i> {{ __('View') }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
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
                                            <form action="{{ route('inventory.pickup.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this pickup?') }}')">
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
                    <div class="mb-3">
                        <label class="form-label">{{ __('Charge To') }}</label>
                        <select name="expense_source" class="form-select" id="expenseSource" onchange="toggleInvestorSelect('expenseSource', 'investorSelect')">
                            <option value="company">{{ __('Company (Perusahaan)') }}</option>
                            <option value="investor">{{ __('Investor') }}</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="investorSelect">
                        <label class="form-label">{{ __('Select Investor') }}</label>
                        <select name="investor_id" class="form-select">
                            <option value="">-- {{ __('Select Investor') }} --</option>
                            @foreach(\App\Models\User::whereHas('role', function($q) { $q->where('name', 'investor'); })->get() as $investor)
                                <option value="{{ $investor->id }}">{{ $investor->name }}</option>
                            @endforeach
                        </select>
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
    <div class="modal-dialog">
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
                            <input type="text" name="type" id="editType" class="form-control" required>
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
                    <div class="mb-3">
                        <label class="form-label">{{ __('Charge To (For Stock Increase)') }}</label>
                        <select name="expense_source" class="form-select" id="editExpenseSource" onchange="toggleInvestorSelect('editExpenseSource', 'editInvestorSelect')">
                            <option value="company">{{ __('Company (Perusahaan)') }}</option>
                            <option value="investor">{{ __('Investor') }}</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="editInvestorSelect">
                        <label class="form-label">{{ __('Select Investor') }}</label>
                        <select name="investor_id" class="form-select">
                            <option value="">-- {{ __('Select Investor') }} --</option>
                            @foreach(\App\Models\User::whereHas('role', function($q) { $q->where('name', 'investor'); })->get() as $investor)
                                <option value="{{ $investor->id }}">{{ $investor->name }}</option>
                            @endforeach
                        </select>
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
    <div class="modal-dialog">
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
    <div class="modal-dialog">
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
        // ... (existing code)
    });

    function toggleInvestorSelect(sourceId, targetId) {
        var source = document.getElementById(sourceId);
        var target = document.getElementById(targetId);
        if (source.value === 'investor') {
            target.classList.remove('d-none');
        } else {
            target.classList.add('d-none');
        }
    }
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
