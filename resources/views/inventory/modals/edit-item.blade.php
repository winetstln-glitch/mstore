{{-- Modal Edit Item --}}
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form id="editItemForm" action="" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Ubah Barang') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        <strong>{{ __('Edit Utama') }}:</strong> Ubah data dan gunakan penyesuaian stok (+/-).
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nama Barang') }}</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Kelompok') }}</label>
                            <select name="type_group" id="editTypeGroup" class="form-select" required>
                                <option value="material">{{ __('Material / Perangkat') }}</option>
                                <option value="tool">{{ __('Alat / Aset') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Kategori') }}</label>
                            <select name="category" id="editCategory" class="form-select" required>
                                @foreach($categoryOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Satuan') }}</label>
                            <input type="text" name="unit" id="editUnit" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Harga Modal/Unit') }}</label>
                            <input type="number" name="price" id="editPrice" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Stok Saat Ini') }}</label>
                            <input type="number" id="editCurrentStock" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Penyesuaian Stok (+/-)') }}</label>
                            <input type="number" name="stock_adjustment" id="editStockAdjustment" class="form-control" value="0">
                            <div class="form-text">{{ __('Contoh: +10 stok masuk, -2 koreksi keluar') }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="stock" id="editStock">

                    {{-- Advanced Fields --}}
                    <button type="button" class="btn btn-link px-0 text-decoration-none" 
                            data-bs-toggle="collapse" data-bs-target="#editAdvancedFields">
                        <i class="fa-solid fa-chevron-down me-1"></i> {{ __('Detail Tambahan') }}
                    </button>
                    <div class="collapse mt-2" id="editAdvancedFields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Jenis') }}</label>
                                <input type="text" name="type" id="editType" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Merek') }}</label>
                                <input type="text" name="brand" id="editBrand" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" id="editModel" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Deskripsi') }}</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
