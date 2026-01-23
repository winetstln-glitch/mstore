@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Pickup Item') }}</h6>
                    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.store-pickup') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label">{{ __('Select Items') }}</label>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Item') }}</th>
                                            <th style="width: 180px;">{{ __('Quantity') }}</th>
                                            <th style="width: 80px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-body">
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[0][inventory_item_id]" class="form-select item-select" required>
                                                    <option value="">{{ __('Choose an item...') }}</option>
                                                    @foreach($items->groupBy('type_group') as $group => $groupedItems)
                                                        <optgroup label="{{ ucfirst($group) }}">
                                                            @foreach($groupedItems as $item)
                                                                <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                                                    {{ $item->name }} (Stock: {{ $item->stock }} {{ $item->unit }})
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" name="items[0][quantity]" class="form-control quantity-input" min="1" required>
                                                    <span class="input-group-text unit-display">pcs</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-item-row">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" id="add-item-row" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Item') }}
                            </button>
                        </div>

                        <div class="mb-4">
                            <label for="usage" class="form-label">{{ __('Usage') }}</label>
                            <select name="usage" id="usage" class="form-select" required>
                                <option value="">{{ __('Select Usage...') }}</option>
                                <option value="New Installation">{{ __('New Installation') }}</option>
                                <option value="Replacement">{{ __('Replacement') }}</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="coordinator_id" class="form-label">{{ __('Coordinator') }}</label>
                            <select name="coordinator_id" id="coordinator_id" class="form-select">
                                <option value="">{{ __('Select Coordinator (Optional)') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}">
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Select the coordinator this pickup is associated with (if any).') }}</div>
                            <div class="alert alert-info mt-2">
                                <small>
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    <strong>{{ __('Important Note:') }}</strong><br>
                                    <ul>
                                        <li><strong>{{ __('For Technicians (Personal Use):') }}</strong> {{ __('Leave "Coordinator" field BLANK. The tool/asset will be assigned to YOU personally in "My Assigned Assets".') }}</li>
                                        <li><strong>{{ __('For Coordinators/Stock:') }}</strong> {{ __('Select a Coordinator ONLY if this item is for team stock or project inventory managed by that coordinator.') }}</li>
                                    </ul>
                                </small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="proof_image" class="form-label">{{ __('Proof of Pickup') }} ({{ __('Photo') }})</label>
                            <input type="file" name="proof_image" id="proof_image" class="form-control" accept="image/*" required>
                            <div class="form-text">{{ __('Upload a photo of the items taken or the signed receipt.') }}</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">{{ __('Notes / Description') }}</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{ __('Optional notes...') }}"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-check me-2"></i> {{ __('Submit Pickup') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateUnit(select) {
        var row = select.closest('.item-row');
        var option = select.options[select.selectedIndex];
        var unit = option.getAttribute('data-unit') || 'pcs';
        var span = row.querySelector('.unit-display');
        if (span) {
            span.textContent = unit;
        }
    }

    function refreshRemoveButtons() {
        var rows = document.querySelectorAll('#items-body .item-row');
        rows.forEach(function(row, index) {
            var btn = row.querySelector('.remove-item-row');
            if (btn) {
                btn.disabled = rows.length === 1;
            }
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-select')) {
            updateUnit(e.target);
        }
    });

    document.getElementById('add-item-row').addEventListener('click', function() {
        var body = document.getElementById('items-body');
        var rows = body.querySelectorAll('.item-row');
        var lastRow = rows[rows.length - 1];
        var newIndex = rows.length;
        var clone = lastRow.cloneNode(true);

        clone.querySelectorAll('select, input').forEach(function(el) {
            if (el.name && el.name.indexOf('items[') === 0) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + newIndex + ']');
                if (el.tagName === 'INPUT') {
                    el.value = '';
                }
                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                }
            }
        });

        var span = clone.querySelector('.unit-display');
        if (span) {
            span.textContent = 'pcs';
        }

        body.appendChild(clone);
        refreshRemoveButtons();
    });

    document.getElementById('items-body').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item-row') || e.target.closest('.remove-item-row')) {
            var btn = e.target.classList.contains('remove-item-row') ? e.target : e.target.closest('.remove-item-row');
            var row = btn.closest('.item-row');
            var body = document.getElementById('items-body');
            var rows = body.querySelectorAll('.item-row');
            if (rows.length > 1) {
                row.remove();
                refreshRemoveButtons();
            }
        }
    });

    refreshRemoveButtons();
</script>
@endpush
@endsection

@push('styles')
<style>
    @media (max-width: 768px) {
        /* Transform table into cards for mobile */
        #items-body tr {
            display: block;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        #items-body td {
            display: block;
            width: 100% !important;
            padding: 0.5rem 0;
            border: none;
        }

        /* Hide table header */
        .table thead {
            display: none;
        }

        /* Adjust inputs */
        .item-select {
            margin-bottom: 0.5rem;
            padding: 0.75rem; /* Larger touch area */
        }

        .quantity-input {
            height: 46px;
        }
        
        .unit-display {
            line-height: 32px;
        }

        /* Delete button full width */
        .remove-item-row {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        .remove-item-row::after {
            content: "Remove Item";
        }
    }
</style>
@endpush
