{{-- Modal Stock In --}}
<div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('inventory.stock-in.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Barang Masuk (Pembelian Stok)') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        {{ __('Gunakan form ini untuk pembelian stok agar histori dan biaya tercatat otomatis.') }}
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('Barang') }}</label>
                        <select name="inventory_item_id" id="stockInItemId" class="form-select" required>
                            <option value="">{{ __('Pilih barang') }}</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" data-unit="{{ $item->unit }}">
                                {{ $item->name }} (Stok: {{ $item->stock }} {{ $item->unit }})
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
                            <label class="form-label">{{ __('Harga Modal/Unit') }}</label>
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
                            <input type="text" name="supplier_name" class="form-control" placeholder="PT ABC">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('No Referensi/Invoice') }}</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="INV-001">
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label">{{ __('Keterangan') }}</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Simpan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
