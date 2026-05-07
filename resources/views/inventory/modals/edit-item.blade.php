{{-- Modal Edit Item --}}
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="editItemForm" action="" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Ubah Barang') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small">
                        <strong>{{ __('Edit Utama') }}:</strong> Perbarui data barang dan jumlah stok yang benar.
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">{{ __('Satuan') }}</label>
                            <input type="text" name="unit" id="editUnit" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">{{ __('Harga Modal') }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="price" id="editPrice" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3" id="editSellingPriceContainer">
                            <label class="form-label fw-bold small text-success text-uppercase">{{ __('Harga Jual') }}</label>
                            <div class="input-group border-success-subtle">
                                <span class="input-group-text bg-success-subtle text-success">Rp</span>
                                <input type="number" name="selling_price" id="editSellingPrice" class="form-control border-success-subtle" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">{{ __('Total Stok Saat Ini') }}</label>
                            <div class="input-group">
                                <input type="number" name="stock" id="editStock" class="form-control fw-bold text-primary" min="0" required>
                                <span class="input-group-text bg-light edit-unit-label">pcs</span>
                            </div>
                            <div class="form-text small">{{ __('Ganti nominal di atas jika ingin mengoreksi jumlah stok (tidak akan tercatat di riwayat).') }}</div>
                        </div>
                    </div>

                    {{-- Advanced Fields --}}
                    <div class="mt-3 border-top pt-3">
                        <h6 class="fw-bold small text-muted text-uppercase mb-3">{{ __('Detail Tambahan') }}</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Jenis') }}</label>
                                <input type="text" name="type" id="editType" class="form-control" placeholder="Contoh: ONU, Router, Kabel">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Merek') }}</label>
                                <input type="text" name="brand" id="editBrand" class="form-control" placeholder="Contoh: ZTE, Huawei, Fiberhome">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Model') }}</label>
                            <input type="text" name="model" id="editModel" class="form-control" placeholder="Contoh: F609, HG8245H">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Deskripsi') }}</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3" placeholder="Keterangan tambahan barang..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
