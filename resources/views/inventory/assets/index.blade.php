@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fa-solid fa-barcode me-2"></i> {{ __('Manage Assets') }}: {{ $item->name }}
        </h1>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Inventory') }}
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
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Item Details') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">{{ __('Name') }}</td>
                            <td>{{ $item->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('Category') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($item->category) }}</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('Stock (Total)') }}</td>
                            <td>{{ $item->stock }} {{ $item->unit }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('Registered Assets') }}</td>
                            <td>{{ $assets->count() }} Units</td>
                        </tr>
                    </table>
                    <div class="alert alert-info small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        {{ __('Register assets only for items that need individual tracking (SN, MAC). The count here does not affect total stock unless synced.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset List -->
        <div class="col-md-8 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Asset List') }}</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Register New Asset') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="assetsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Serial Number') }}</th>
                                    <th>{{ __('Holder / Location') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    <tr>
                                        <td>
                                            @if($asset->status == 'in_stock')
                                                <span class="badge bg-success">{{ __('In Stock') }}</span>
                                            @elseif($asset->status == 'deployed')
                                                <span class="badge bg-primary">{{ __('Deployed') }}</span>
                                            @elseif($asset->status == 'maintenance')
                                                <span class="badge bg-warning text-dark">{{ __('Maintenance') }}</span>
                                            @elseif($asset->status == 'broken')
                                                <span class="badge bg-danger">{{ __('Broken') }}</span>
                                            @elseif($asset->status == 'lost')
                                                <span class="badge bg-dark">{{ __('Lost') }}</span>
                                            @elseif($asset->status == 'pending_return')
                                                <span class="badge bg-info text-dark">{{ __('Return Pending') }}</span>
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
                                                <i class="fa-solid fa-warehouse me-1"></i> {{ __('Warehouse') }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                @if($asset->status == 'in_stock')
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="openAssignModal({{ $asset->id }}, '{{ $asset->serial_number }}')">
                                                        <i class="fa-solid fa-hand-holding-hand"></i> {{ __('Assign') }}
                                                    </button>
                                                @elseif($asset->status == 'deployed')
                                                    <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Return this asset to warehouse?') }}')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fa-solid fa-rotate-left"></i> {{ __('Return') }}
                                                        </button>
                                                    </form>
                                                @elseif($asset->status == 'pending_return')
                                                    <form action="{{ route('inventory.assets.return', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Approve return and add to stock?') }}')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fa-solid fa-check"></i> {{ __('Approve Return') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="openEditModal({{ $asset->id }}, '{{ $asset->serial_number }}', '{{ $asset->status }}', '{{ $asset->condition }}', '{{ $asset->mac_address }}', '{{ $asset->asset_code }}', {{ $asset->is_returnable ? 'true' : 'false' }})">
                                                            <i class="fa-solid fa-edit me-2"></i> {{ __('Edit Details') }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('inventory.assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('{{ __('Delete this asset record?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa-solid fa-trash me-2"></i> {{ __('Delete') }}
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
                                            {{ __('No individual assets registered yet.') }}
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

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('inventory.assets.store', $item->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Register New Asset') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Serial Number') }}</label>
                        <input type="text" name="serial_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Asset Code (Optional)') }}</label>
                        <input type="text" name="asset_code" class="form-control" placeholder="e.g. AST-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('MAC Address (Optional)') }}</label>
                        <input type="text" name="mac_address" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="in_stock">{{ __('In Stock') }}</option>
                                <option value="deployed">{{ __('Deployed') }}</option>
                                <option value="maintenance">{{ __('Maintenance') }}</option>
                                <option value="broken">{{ __('Broken') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Condition') }}</label>
                            <select name="condition" class="form-select">
                                <option value="good">{{ __('Good') }}</option>
                                <option value="damaged">{{ __('Damaged') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Register') }}</button>
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
                    <h5 class="modal-title">{{ __('Assign Asset') }} <span id="assignAssetSN" class="badge bg-secondary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        {{ __('This will mark the asset as "Deployed" and assign responsibility to the selected user.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Assign To (User/Coordinator/Technician)') }}</label>
                        <select name="user_id" class="form-select select2" required>
                            <option value="">-- Select User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Handover for project X"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Assign') }}</button>
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
                    <h5 class="modal-title">{{ __('Edit Asset Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Serial Number') }}</label>
                        <input type="text" name="serial_number" id="editSN" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Asset Code') }}</label>
                        <input type="text" name="asset_code" id="editCode" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('MAC Address') }}</label>
                        <input type="text" name="mac_address" id="editMAC" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="in_stock">{{ __('In Stock') }}</option>
                                <option value="deployed">{{ __('Deployed') }}</option>
                                <option value="maintenance">{{ __('Maintenance') }}</option>
                                <option value="broken">{{ __('Broken') }}</option>
                                <option value="lost">{{ __('Lost') }}</option>
                                <option value="pending_return">{{ __('Return Pending') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Condition') }}</label>
                            <select name="condition" id="editCondition" class="form-select">
                                <option value="good">{{ __('Good') }}</option>
                                <option value="damaged">{{ __('Damaged') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_returnable" value="1" id="editIsReturnable">
                            <label class="form-check-label" for="editIsReturnable">
                                {{ __('Wajib Dikembalikan? (Returnable)') }}
                            </label>
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
