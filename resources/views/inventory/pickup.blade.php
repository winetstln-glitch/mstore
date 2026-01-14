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
                            <label for="inventory_item_id" class="form-label">{{ __('Select Item') }}</label>
                            <select name="inventory_item_id" id="inventory_item_id" class="form-select" required>
                                <option value="">{{ __('Choose an item...') }}</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                        {{ $item->name }} (Stock: {{ $item->stock }} {{ $item->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="quantity" class="form-label">{{ __('Quantity') }}</label>
                            <div class="input-group">
                                <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                                <span class="input-group-text" id="unit-display">pcs</span>
                            </div>
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
    document.getElementById('inventory_item_id').addEventListener('change', function() {
        var option = this.options[this.selectedIndex];
        var unit = option.getAttribute('data-unit') || 'pcs';
        document.getElementById('unit-display').textContent = unit;
    });
</script>
@endpush
@endsection
