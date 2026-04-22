{{-- Modal Impor Barang --}}
<div class="modal fade" id="importItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Impor Barang') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <small>{{ __('Gunakan template file untuk impor.') }}</small>
                        <br>
                        <a href="{{ route('inventory.import.template') }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fa-solid fa-download me-1"></i> {{ __('Unduh Template') }}
                        </a>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('File Excel') }}</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Impor') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
