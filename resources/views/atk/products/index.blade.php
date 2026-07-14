@extends('layouts.app')

@section('title', __('Product Management'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Product Management') }}</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal" title="{{ __('Import Excel') }}">
                <i class="fa-solid fa-file-import"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Import Excel') }}</span>
            </button>
            <a href="{{ route('atk.products.export') }}" class="btn btn-success" title="{{ __('Export Excel') }}">
                <i class="fa-solid fa-file-export"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Export Excel') }}</span>
            </a>
            <a href="{{ route('atk.products.barcodes') }}" class="btn btn-dark" title="{{ __('Barcodes') }}">
                <i class="fa-solid fa-barcode"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Barcodes') }}</span>
            </a>
            <a href="{{ route('atk.products.create') }}" class="btn btn-primary" title="{{ __('Add Product') }}">
                <i class="fa-solid fa-plus"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Add Product') }}</span>
            </a>
            <button type="button" id="btnSelectAll" class="btn btn-outline-secondary" title="Select All">
                <i class="fa-regular fa-square-check me-1"></i><span class="d-none d-md-inline">Select All</span>
            </button>
            <form id="bulkDeleteForm" action="{{ route('atk.products.bulk_destroy') }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <input type="hidden" name="ids[]" id="bulkIds">
                <button type="submit" id="btnBulkDelete" class="btn btn-outline-danger" disabled title="Hapus Terpilih" onclick="return confirm('Hapus produk terpilih?')">
                    <i class="fa-solid fa-trash-can me-1"></i><span class="d-none d-md-inline">Hapus Terpilih</span>
                </button>
            </form>
        </div>
    </div>

    @include('atk._help-box', [
        'title' => 'Petunjuk Manajemen Produk',
        'content' => '<p><strong>Halaman ini untuk mengelola semua produk dan jasa toko ATK!</strong></p><ul class="mb-0"><li><strong>Tambah Produk:</strong> Klik "Add Product" untuk menambah produk baru.</li><li><strong>Import/Export:</strong> Gunakan tombol import untuk menambah produk secara massal, atau export untuk mengunduh daftar produk.</li><li><strong>Barcode:</strong> Klik "Barcodes" untuk melihat dan mencetak semua barcode produk.</li><li><strong>Edit/Hapus:</strong> Gunakan tombol di kolom aksi untuk mengubah atau menghapus produk.</li><li><strong>Hapus Massal:</strong> Centang beberapa produk, lalu klik "Hapus Terpilih".</li></ul>'
    ])

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">{{ __('Import Products') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('atk.products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="fw-bold mb-1">{{ __('Panduan Import') }}</div>
                            <ul class="mb-2">
                                <li>{{ __('Gunakan file format .xlsx (bukan .xls)') }}</li>
                                <li>{{ __('Minimal kolom Nama Produk; kolom lain opsional') }}</li>
                                <li>{{ __('Jika Code kosong, sistem akan membuat otomatis') }}</li>
                                <li>{{ __('Urutan kolom yang didukung: Code, Name, Category, Price, Cost Price, Stock, Unit, Description') }}</li>
                                <li>{{ __('Price/Cost Price angka; Stock bilangan bulat; Unit default pcs') }}</li>
                            </ul>
                            <a href="{{ route('atk.products.export') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-file-export me-1"></i> {{ __('Unduh Template (Export)') }}
                            </a>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label">{{ __('Choose Excel File') }}</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
                            <small class="text-muted">{{ __('Hanya menerima .xlsx') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('atk.products.index') }}" class="row g-2 mb-3">
                <div class="col-sm-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" id="productSearch" class="form-control" placeholder="{{ __('Cari Nama atau Barcode...') }}" value="{{ request('search') }}" autofocus>
                        <button type="button" class="btn btn-outline-primary" id="openAtkBarcodeScan" title="Scan barcode via kamera">
                            <i class="fa-solid fa-barcode"></i>
                        </button>
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <select name="category" class="form-select">
                        <option value="">{{ __('Semua Kategori') }}</option>
                        @php($opts = isset($categories) ? $categories : ['ATK','JASA POTOCOPY','JASA TRANSFER BANK'])
                        @foreach($opts as $opt)
                            <option value="{{ $opt }}" {{ request('category')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="input-group">
                        <label class="input-group-text" for="per_page">Tampil</label>
                        <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
                            @php($pp = request('per_page','10'))
                            <option value="10" {{ $pp=='10' ? 'selected' : '' }}>10</option>
                            <option value="50" {{ $pp=='50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $pp=='100' ? 'selected' : '' }}>100</option>
                            <option value="all" {{ $pp=='all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-2">
                    <button type="submit" class="btn btn-dark w-100" title="{{ __('Filter') }}">
                        <i class="fa-solid fa-filter"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Filter') }}</span>
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width:28px;">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Stock') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td>
                                <input type="checkbox" class="row-check" value="{{ $product->id }}">
                            </td>
                            <td>
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" width="50" height="50" class="img-thumbnail object-fit-cover">
                                @else
                                    <div class=" d-flex align-items-center justify-content-center text-muted border rounded" style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category ?? '-' }}</td>
                            <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $product->stock > 10 ? 'bg-success' : ($product->stock > 0 ? 'bg-warning' : 'bg-danger') }}">
                                    {{ $product->stock }} {{ $product->unit }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('atk.products.edit', $product) }}" class="btn btn-sm btn-info">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('atk.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No products found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="atkBarcodeScanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Barcode Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="atk-barcode-reader" class="atk-scan-reader" style="width:100%;"></div>
                <div id="atkScanStatus" class="small text-muted mt-2">
                    Arahkan kamera ke barcode pada produk.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="stopAtkBarcodeScan">Hentikan Scan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
    .atk-scan-reader video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
        border-radius: 0.5rem;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Existing selection logic
    const checkAll = document.getElementById('checkAll');
    const rowChecks = Array.from(document.querySelectorAll('.row-check'));
    const bulkForm = document.getElementById('bulkDeleteForm');
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const btnSelectAll = document.getElementById('btnSelectAll');

    function updateBulkState() {
        const selected = rowChecks.filter(cb => cb.checked).map(cb => cb.value);
        if (bulkForm) {
            bulkForm.querySelectorAll('input[name=\"ids[]\"]').forEach(e => e.remove());
        }
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            if (bulkForm) {
                bulkForm.appendChild(input);
            }
        });
        btnBulkDelete.disabled = selected.length === 0;
        checkAll.checked = selected.length > 0 && selected.length === rowChecks.length;
        checkAll.indeterminate = selected.length > 0 && selected.length < rowChecks.length;
    }
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
            updateBulkState();
        });
    }
    rowChecks.forEach(cb => cb.addEventListener('change', updateBulkState));
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function() {
            const allChecked = rowChecks.every(cb => cb.checked);
            rowChecks.forEach(cb => cb.checked = !allChecked);
            updateBulkState();
        });
    }

    // Barcode Scanner Logic
    let atkBarcodeScanner = null;
    let isAtkBarcodeScannerRunning = false;

    function setAtkScanStatus(message, type = 'muted') {
        const statusEl = document.getElementById('atkScanStatus');
        if (!statusEl) return;
        statusEl.classList.remove('text-muted', 'text-danger', 'text-success');
        if (type === 'error') {
            statusEl.classList.add('text-danger');
        } else if (type === 'success') {
            statusEl.classList.add('text-success');
        } else {
            statusEl.classList.add('text-muted');
        }
        statusEl.textContent = message;
    }

    async function stopAtkBarcodeScanner() {
        if (!atkBarcodeScanner || !isAtkBarcodeScannerRunning) return;
        try {
            await atkBarcodeScanner.stop();
            await atkBarcodeScanner.clear();
        } catch (error) {
            console.warn('Stop ATK barcode scanner error:', error);
        } finally {
            isAtkBarcodeScannerRunning = false;
        }
    }

    async function startAtkBarcodeScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            setAtkScanStatus('Library scanner belum tersedia.', 'error');
            return;
        }
        if (isAtkBarcodeScannerRunning) {
            setAtkScanStatus('Scanner sudah aktif.');
            return;
        }
        atkBarcodeScanner = atkBarcodeScanner || new Html5Qrcode('atk-barcode-reader');
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            disableFlip: true
        };

        const onDecoded = async (decodedText) => {
            const productSearchInput = document.getElementById('productSearch');
            if (productSearchInput) {
                productSearchInput.value = String(decodedText || '').trim();
                productSearchInput.form.submit(); // Auto submit on scan
            }
            setAtkScanStatus('Barcode berhasil dibaca.', 'success');
            await stopAtkBarcodeScanner();
            const modalEl = document.getElementById('atkBarcodeScanModal');
            if (modalEl && window.bootstrap) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.hide();
            }
        };

        const onDecodeError = () => {};

        try {
            await atkBarcodeScanner.start({ facingMode: "environment" }, config, onDecoded, onDecodeError);
            isAtkBarcodeScannerRunning = true;
            setAtkScanStatus('Arahkan kamera ke barcode.');
        } catch (err) {
            console.error(err);
            setAtkScanStatus('Gagal mengakses kamera.', 'error');
        }
    }

    const openScanBtn = document.getElementById('openAtkBarcodeScan');
    const stopScanBtn = document.getElementById('stopAtkBarcodeScan');
    const scanModalEl = document.getElementById('atkBarcodeScanModal');

    if (openScanBtn) {
        openScanBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(scanModalEl);
            modal.show();
        });
    }

    if (scanModalEl) {
        scanModalEl.addEventListener('shown.bs.modal', function() {
            startAtkBarcodeScanner();
        });
        scanModalEl.addEventListener('hidden.bs.modal', function() {
            stopAtkBarcodeScanner();
        });
    }

    if (stopScanBtn) {
        stopScanBtn.addEventListener('click', function() {
            stopAtkBarcodeScanner();
            const modalInstance = bootstrap.Modal.getInstance(scanModalEl);
            if (modalInstance) modalInstance.hide();
        });
    }
});
</script>
@endpush
@endsection
