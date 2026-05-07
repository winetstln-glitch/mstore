{{-- Modal Edit Pickup --}}
<div class="modal fade" id="editPickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <form id="editPickupForm" action="" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Pengambilan') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nama Item') }}</label>
                        <input type="text" id="editPickupItemName" class="form-control bg-light" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Jumlah') }}</label>
                        <div class="input-group">
                            <input type="number" name="quantity" id="editPickupQuantity" class="form-control" min="1" required>
                            <span class="input-group-text" id="editPickupUnit">pcs</span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Keterangan') }}</label>
                        <textarea name="description" id="editPickupDescription" class="form-control" rows="3"></textarea>
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