@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="h3 mb-0 text-gray-800">{{ __('Inventory Management') }}</h1>
                <div>
                    @if(Auth::user()->hasRole('admin'))
                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fa-solid fa-plus me-1"></i> <span class="d-none d-md-inline">{{ __('Add New Item') }}</span>
                    </button>
                    @endif
                    <a href="{{ route('inventory.pickup') }}" class="btn btn-primary">
                        <i class="fa-solid fa-box-open me-1"></i> <span class="d-none d-md-inline">{{ __('Pickup Item') }}</span>
                    </a>
                </div>
            </div>

            <!-- Items List -->
            @if(Auth::user()->hasRole('admin'))
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Inventory Items') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Name') }}</th>
                                    <th class="py-3">{{ __('Description') }}</th>
                                    <th class="py-3">{{ __('Stock') }}</th>
                                    <th class="py-3">{{ __('Unit') }}</th>
                                    <th class="pe-4 py-3 text-end" style="width: 150px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td class="ps-4 fw-medium">{{ $item->name }}</td>
                                        <td>{{ $item->description ?: '-' }}</td>
                                        <td>
                                            <span class="badge {{ $item->stock > 10 ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                                {{ $item->stock }}
                                            </span>
                                        </td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="pe-4 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editItemModal"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
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
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">{{ __('Date') }}</th>
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
                                            @if(Auth::id() === $transaction->user_id || Auth::user()->hasRole('admin'))
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editItemModal = document.getElementById('editItemModal');
        editItemModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var action = button.getAttribute('data-action');
            var name = button.getAttribute('data-name');
            var unit = button.getAttribute('data-unit');
            var stock = button.getAttribute('data-stock');
            var price = button.getAttribute('data-price');
            var description = button.getAttribute('data-description');

            var form = editItemModal.querySelector('#editItemForm');
            form.action = action;
            
            editItemModal.querySelector('#editName').value = name;
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
