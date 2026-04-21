{{-- Modal Add Item --}}
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Tambah Item Baru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        <strong>{{ __('Isi Utama') }}:</strong> Nama, kelompok, kategori, unit, stok awal, dan harga modal.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nama Item') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="Kabel Fiber 1 Core" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Kelompok') }}</label>
                            <select name="type_group" id="addTypeGroup" class="form-select" required>
                                <option value="material" {{ request('type_group') == 'material' ? 'selected' : '' }}>
                                    {{ __('Material / Device') }}
                                </option>
                                <option value="tool" {{ request('type_group') == 'tool' ? 'selected' : '' }}>
                                    {{ __('Tool / Asset') }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Kategori') }}</label>
                            <select name="category" id="addCategory" class="form-select" required>
                                @foreach($categoryOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Satuan') }}</label>
                            <input type="text" name="unit" id="addUnit" class="form-control" placeholder="pcs, meter, roll" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Stok Awal') }}</label>
                            <input type="number" name="stock" class="form-control" value="0" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('Harga Modal/Unit') }}</label>
                        <input type="number" name="price" class="form-control" value="0" min="0" step="0.01" required>
                    </div>

                    {{-- Advanced Fields (Collapsible) --}}
                    <button type="button" class="btn btn-link px-0 text-decoration-none" 
                            data-bs-toggle="collapse" data-bs-target="#addAdvancedFields">
                        <i class="fa-solid fa-chevron-down me-1"></i> {{ __('Detail Tambahan (Opsional)') }}
                    </button>
                    <div class="collapse mt-2" id="addAdvancedFields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Tipe') }}</label>
                                <input type="text" name="type" class="form-control" placeholder="Router, Cable">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Merek') }}</label>
                                <input type="text" name="brand" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Deskripsi') }}</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>