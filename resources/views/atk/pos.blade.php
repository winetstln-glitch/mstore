@extends('layouts.app')

@section('title', __('ATK POS'))

@section('content')
<div class="atk-pos-page">
    <div class="atk-pos-shell">
        <header class="atk-pos-header">
            <span id="current-time" class="atk-current-time"></span>
        </header>

        <div class="row g-4">
            <div class="col-12 col-lg-8 order-2 order-lg-1">
                <div class="atk-card">
                    <div class="atk-card-header">
                        <h2 class="atk-card-title"><i class="fas fa-th-large"></i> Pilih Produk</h2>
                        <div class="atk-filter-group">
                            <button class="filter-btn active" data-tab="products" type="button" onclick="switchTab('products')">Produk</button>
                            <button class="filter-btn" data-tab="services" type="button" onclick="switchTab('services')">Jasa</button>
                            <button class="filter-btn" data-tab="bank" type="button" onclick="switchTab('bank')">Bank</button>
                            <button class="filter-btn" data-tab="cash-out" type="button" onclick="switchTab('cash-out')">Tarik Tunai</button>
                            <button class="filter-btn" data-tab="top-up" type="button" onclick="switchTab('top-up')">Top Up</button>
                            <button class="filter-btn" data-tab="ppob" type="button" onclick="switchTab('ppob')">PPOB</button>
                            <button class="filter-btn" data-tab="customer-payments" type="button" onclick="switchTab('customer-payments')">Pembayaran</button>
                        </div>
                        <div class="mt-2">
                            <div class="input-group">
                                <span class="input-group-text atk-input-group-text"><i class="fas fa-search text-body-secondary"></i></span>
                                <input type="text" id="productSearch" class="form-control form-control-sm atk-input" placeholder="Cari produk cepat...">
                                <button type="button" class="btn atk-secondary-btn" id="openAtkBarcodeScan" title="Scan barcode via kamera">
                                    <i class="fa-solid fa-barcode"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="atk-card-body">
                        <!-- Products Tab -->
                        <div id="productList" class="row g-3">
                            @foreach($products as $product)
                            <div class="col-6 col-md-4 product-item" data-name="{{ strtolower($product->name) }}" data-code="{{ strtolower($product->code) }}">
                                <div class="product-card product-card-product" data-fasttap data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}" data-type="product" data-stock="{{ $product->stock }}">
                                    <div class="product-image-wrap">
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" class="img-fluid product-image" alt="{{ $product->name }}">
                                        @else
                                            <i class="fas fa-box product-fallback-icon"></i>
                                        @endif
                                    </div>
                                    <h5 class="product-title">{{ $product->name }}</h5>
                                    <div class="product-description-chips">
                                        <span class="product-description-chip">Stok: {{ $product->stock }}</span>
                                    </div>
                                    <div class="product-meta">
                                        <span class="product-price">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @if($products->isEmpty())
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Belum ada produk. <a href="{{ route('atk.products.create') }}">Tambah Produk</a></p>
                            </div>
                            @endif
                        </div>

                        <!-- Services Tab -->
                        <div id="serviceList" class="row g-3 d-none">
                            @foreach(($services ?? []) as $service)
                            <div class="col-6 col-md-4 service-item" data-name="{{ strtolower($service->name) }}" data-code="{{ strtolower($service->code) }}">
                                <div class="product-card product-card-service" data-fasttap data-id="{{ $service->id }}" data-name="{{ $service->name }}" data-price="{{ $service->price }}" data-type="service" data-stock="999999">
                                    <div class="product-image-wrap">
                                        @if($service->image)
                                            <img src="{{ Storage::url($service->image) }}" class="img-fluid product-image" alt="{{ $service->name }}">
                                        @else
                                            <i class="fas fa-print product-fallback-icon"></i>
                                        @endif
                                    </div>
                                    <h5 class="product-title">{{ $service->name }}</h5>
                                    <div class="product-description-chips">
                                        <span class="product-description-chip product-description-chip-empty">Jasa</span>
                                    </div>
                                    <div class="product-meta">
                                        <span class="product-price">Rp {{ number_format($service->price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @if(empty($services) || count($services) === 0)
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Belum ada jasa. <a href="{{ route('atk.products.create') }}">Tambah Jasa</a></p>
                            </div>
                            @endif
                        </div>

                        <!-- Customer Payments Tab -->
                        <div id="customerPaymentList" class="row g-3 d-none">
                            <div class="col-12">
                                <div class="p-3">
                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Pelanggan</label>
                                            <select id="customerPaymentCustomerId" class="form-select atk-input">
                                                <option value="">Pilih Pelanggan</option>
                                                @foreach(($customers ?? []) as $customer)
                                                    <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }}{{ !empty($customer->phone) ? ' - '.$customer->phone : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Nominal Pembayaran</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="customerPaymentNominal" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn atk-primary-btn w-100" onclick="addCustomerPaymentToCart()">
                                        <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                                @if(empty($customers) || count($customers) === 0)
                                <div class="text-center py-5">
                                    <p class="text-muted">Belum ada data pelanggan. <a href="{{ route('customers.create') }}">Tambah Pelanggan</a></p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Bank Tab -->
                        <div id="bankPanel" class="row g-3 d-none">
                            <div class="col-12">
                                <div class="p-3">
                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Jenis Layanan</label>
                                            <select id="bankServiceId" class="form-select atk-input">
                                                @foreach(($bankServices ?? []) as $bs)
                                                    <option value="{{ $bs->id }}">{{ $bs->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Nominal Transfer</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="bankNominal" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="bankFee" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn atk-primary-btn w-100" onclick="addBankToCart()">
                                        <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Out Tab -->
                        <div id="cashOutPanel" class="row g-3 d-none">
                            <div class="col-12">
                                <div class="p-3">
                                    <div class="row g-2 mb-3">
                                        <div class="col-12">
                                            <label class="atk-field-label">Akun Float</label>
                                            <select id="cashOutFloatAccountId" class="form-select atk-input">
                                                <option value="">Pilih Akun Float</option>
                                                @foreach(($floatAccounts ?? []) as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }} (Saldo: Rp {{ number_format($account->current_balance, 0, ',', '.') }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Nominal Tarik Tunai</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="cashOutNominal" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="cashOutFee" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn atk-primary-btn w-100" onclick="addCashOutToCart()">
                                        <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Top Up Tab -->
                        <div id="topUpPanel" class="row g-3 d-none">
                            <div class="col-12">
                                <div class="p-3">
                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">E-Wallet</label>
                                            <select id="topUpWallet" class="form-select atk-input">
                                                <option value="dana">DANA</option>
                                                <option value="ovo">OVO</option>
                                                <option value="gopay">GoPay</option>
                                                <option value="shopeePay">ShopeePay</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Nominal Top Up</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="topUpNominal" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="topUpFee" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn atk-primary-btn w-100" onclick="addTopUpToCart()">
                                        <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- PPOB Tab -->
                        <div id="ppobPanel" class="row g-3 d-none">
                            <div class="col-12">
                                <div class="p-3">
                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Jenis Layanan</label>
                                            <select id="ppobService" class="form-select atk-input">
                                                <option value="pulsa">Pulsa</option>
                                                <option value="data">Paket Data</option>
                                                <option value="listrik">Listrik PLN</option>
                                                <option value="bpjs">BPJS</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Nomor Pelanggan</label>
                                            <input type="text" id="ppobNumber" class="form-control atk-input" placeholder="08xxxxxxxxxx">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Nominal</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="ppobNominal" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="atk-field-label">Fee</label>
                                            <div class="input-group">
                                                <span class="input-group-text atk-input-group-text">Rp</span>
                                                <input type="number" id="ppobFee" class="form-control atk-input" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn atk-primary-btn w-100" onclick="addPPOBToCart()">
                                        <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 order-1 order-lg-2">
                <div class="atk-card atk-checkout-card">
                    <div class="atk-card-header">
                        <h2 class="atk-card-title"><i class="fas fa-shopping-cart"></i> Rincian Transaksi</h2>
                    </div>
                    <div class="atk-card-body">
                        <form id="checkoutForm" class="checkout-form">
                            <div class="mb-3">
                                <label class="atk-field-label">No. WhatsApp (opsional)</label>
                                <input type="text" class="form-control atk-input" id="customerPhone" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-check mb-3 atk-inline-check">
                                <input class="form-check-input" type="checkbox" value="" id="sendWhatsapp">
                                <label class="form-check-label" for="sendWhatsapp">Kirim nota via WhatsApp</label>
                            </div>

                            <div id="cartItems" class="mb-3 custom-scrollbar">
                                <p class="text-center text-muted py-4 mb-0" id="emptyCartMsg">Keranjang masih kosong</p>
                            </div>

                            <div class="atk-summary-row atk-summary-divider">
                                <span>Total Item</span>
                                <span id="totalItems">0</span>
                            </div>
                            <div class="atk-summary-row atk-summary-total">
                                <span>Total Akhir</span>
                                <span id="totalAmount">Rp 0</span>
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-12">
                                    <label class="atk-field-label">Metode Pembayaran</label>
                                    <select class="form-select atk-input" id="paymentMethod">
                                        <option value="cash">💵 Tunai</option>
                                        <option value="transfer">🏦 Transfer</option>
                                        <option value="qris">📱 QRIS</option>
                                        <option value="hutang">📜 Hutang</option>
                                    </select>
                                </div>
                                <div class="col-12" id="cashInputDiv">
                                    <label class="atk-field-label">Uang Diterima</label>
                                    <div class="input-group">
                                        <span class="input-group-text atk-input-group-text">Rp</span>
                                        <input type="number" class="form-control atk-input" id="cashAmount" placeholder="0" oninput="calculateChange()">
                                    </div>
                                </div>
                            </div>

                            <div id="changeDisplay" class="atk-change-box mt-2">
                                <span>Kembalian</span>
                                <strong id="changeAmount">Rp 0</strong>
                            </div>

                            <div id="pengurusDiv" class="mt-3" style="display: none;">
                                <div class="card card-body bg-light">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user-tie me-1"></i> Pengurus</h6>
                                    <label for="coordinatorId" class="atk-field-label">Pilih Pengurus</label>
                                    <select class="form-select atk-input" id="coordinatorId">
                                        <option value="">Pilih Pengurus (wajib untuk hutang jasa potocopy)</option>
                                        @foreach(($coordinators ?? []) as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="button" class="btn atk-primary-btn w-100 mt-3" id="btnCheckout" onclick="processTransaction()" disabled>Proses Pembayaran</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="atkBarcodeScanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Barcode Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="atk-barcode-reader" class="atk-scan-reader" style="width:100%;"></div>
                <div id="atkScanStatus" class="small text-muted mt-2">
                    Arahkan kamera ke barcode pada produk.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn atk-secondary-btn" id="stopAtkBarcodeScan">Hentikan Scan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let cart = [];
let atkBarcodeScanner = null;
let isAtkBarcodeScannerRunning = false;

document.addEventListener('DOMContentLoaded', function () {
    const openScanBtn = document.getElementById('openAtkBarcodeScan');
    const stopScanBtn = document.getElementById('stopAtkBarcodeScan');
    const scanModalEl = document.getElementById('atkBarcodeScanModal');

    if (openScanBtn && scanModalEl && window.bootstrap) {
        openScanBtn.addEventListener('click', function () {
            const modalInstance = window.bootstrap.Modal.getOrCreateInstance(scanModalEl);
            modalInstance.show();
        });
    }
    if (scanModalEl) {
        scanModalEl.addEventListener('shown.bs.modal', async function () {
            await startAtkBarcodeScanner();
        });
    }
    if (stopScanBtn) {
        stopScanBtn.addEventListener('click', async function () {
            await stopAtkBarcodeScanner();
            setAtkScanStatus('Scan dihentikan.');
        });
    }
    if (scanModalEl) {
        scanModalEl.addEventListener('hidden.bs.modal', async function () {
            await stopAtkBarcodeScanner();
            setAtkScanStatus('Arahkan kamera ke barcode pada produk.');
        });
    }

    // Attach click handlers to product cards
    document.querySelectorAll('.product-card[data-id]').forEach(function (el) {
        el.addEventListener('click', function () {
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const maxStock = parseInt(this.dataset.stock);
            const type = this.dataset.type || 'product';
            addToCart(id, name, price, maxStock, type);
        });
    });

    // Product search
    document.getElementById('productSearch').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        let activeList = '.product-item';
        if (!document.getElementById('serviceList').classList.contains('d-none')) {
            activeList = '.service-item';
        } else if (!document.getElementById('customerPaymentList').classList.contains('d-none')) {
            return;
        }
        document.querySelectorAll(activeList).forEach(item => {
            const name = String(item.dataset.name || '').toLowerCase();
            const code = String(item.dataset.code || '').toLowerCase();
            item.style.display = (name.includes(search) || code.includes(search)) ? '' : 'none';
        });
    });

    const timeEl = document.getElementById('current-time');
    if (timeEl) {
        const updateTime = () => {
            timeEl.textContent = new Date().toLocaleString('id-ID', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        };
        updateTime();
        setInterval(updateTime, 60000);
    }

    updatePengurusVisibility();
});

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

async function applyAtkTrackConstraints(track, constraints) {
    if (!track || typeof track.applyConstraints !== 'function') {
        return false;
    }
    try {
        await track.applyConstraints(constraints);
        return true;
    } catch (error) {
        return false;
    }
}

async function optimizeAtkScannerTrack() {
    const atkScannerTrack = atkBarcodeScanner?.getRunningTrack?.() || null;
    if (!atkScannerTrack) {
        return;
    }

    const optimizations = [
        { advanced: [{ focusMode: 'continuous' }] },
        { advanced: [{ exposureMode: 'continuous' }] },
        { advanced: [{ whiteBalanceMode: 'continuous' }] },
        { advanced: [{ brightness: 0.2 }] },
        { advanced: [{ contrast: 0.3 }] }
    ];
    for (const constraint of optimizations) {
        await applyAtkTrackConstraints(atkScannerTrack, constraint);
    }
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
    const scannerFormats = [];
    if (window.Html5QrcodeSupportedFormats) {
        scannerFormats.push(
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.QR_CODE
        );
    }
    const config = {
        fps: 12,
        qrbox: { width: 260, height: 260 },
        aspectRatio: 1.333334,
        disableFlip: true
    };
    if (scannerFormats.length > 0) {
        config.formatsToSupport = scannerFormats;
    }
    const onDecoded = async (decodedText) => {
        const productSearchInput = document.getElementById('productSearch');
        if (productSearchInput) {
            switchTab('products');
            productSearchInput.value = String(decodedText || '').trim();
            productSearchInput.dispatchEvent(new Event('input'));
        }
        setAtkScanStatus('Barcode berhasil dibaca.', 'success');
        await stopAtkBarcodeScanner();
        const modalEl = document.getElementById('atkBarcodeScanModal');
        if (modalEl && window.bootstrap) {
            const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.hide();
        }
    };

    const onDecodeError = () => {};

    const startByConstraints = async (constraints) => {
        await atkBarcodeScanner.start(constraints, config, onDecoded, onDecodeError);
    };

    try {
        const cameras = (typeof Html5Qrcode.getCameras === 'function')
            ? await Html5Qrcode.getCameras()
            : [];
        const sortedCameras = Array.isArray(cameras) ? [...cameras].sort((a, b) => {
            const backRegex = /(back|rear|environment|belakang|traseira|trasera)/i;
            const aBack = backRegex.test(String(a?.label || '')) ? 1 : 0;
            const bBack = backRegex.test(String(b?.label || '')) ? 1 : 0;
            return bBack - aBack;
        }) : [];

        let started = false;
        for (const camera of sortedCameras) {
            try {
                await startByConstraints(camera.id);
                started = true;
                break;
            } catch (cameraError) {}
        }

        if (!started) {
            await startByConstraints({
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            });
        }
    } catch (primaryError) {
        await atkBarcodeScanner.start(
            {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            config,
            onDecoded,
            onDecodeError
        );
    }

    isAtkBarcodeScannerRunning = true;
    setAtkScanStatus('Arahkan kamera ke barcode pada produk.');
    setTimeout(() => {
        optimizeAtkScannerTrack();
    }, 280);
}

function switchTab(tab) {
    const pList = document.getElementById('productList');
    const sList = document.getElementById('serviceList');
    const cpList = document.getElementById('customerPaymentList');
    const bPanel = document.getElementById('bankPanel');
    const coPanel = document.getElementById('cashOutPanel');
    const tuPanel = document.getElementById('topUpPanel');
    const ppPanel = document.getElementById('ppobPanel');
    const filterBtns = document.querySelectorAll('.atk-filter-group .filter-btn');

    // Reset all
    pList.classList.add('d-none');
    sList.classList.add('d-none');
    cpList.classList.add('d-none');
    bPanel.classList.add('d-none');
    coPanel.classList.add('d-none');
    tuPanel.classList.add('d-none');
    ppPanel.classList.add('d-none');
    filterBtns.forEach(btn => btn.classList.remove('active'));

    if (tab === 'products') {
        pList.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'products') btn.classList.add('active'); });
    } else if (tab === 'services') {
        sList.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'services') btn.classList.add('active'); });
    } else if (tab === 'bank') {
        bPanel.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'bank') btn.classList.add('active'); });
    } else if (tab === 'cash-out') {
        coPanel.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'cash-out') btn.classList.add('active'); });
    } else if (tab === 'top-up') {
        tuPanel.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'top-up') btn.classList.add('active'); });
    } else if (tab === 'ppob') {
        ppPanel.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'ppob') btn.classList.add('active'); });
    } else if (tab === 'customer-payments') {
        cpList.classList.remove('d-none');
        filterBtns.forEach(btn => { if (btn.dataset.tab === 'customer-payments') btn.classList.add('active'); });
    }

    document.getElementById('productSearch').dispatchEvent(new Event('input'));
    updatePengurusVisibility();
}

function addBankToCart() {
    const id = parseInt(document.getElementById('bankServiceId').value);
    const nominal = parseFloat(document.getElementById('bankNominal').value) || 0;
    const fee = parseFloat(document.getElementById('bankFee').value) || 0;
    const name = (document.getElementById('bankServiceId').selectedOptions[0]?.text) || 'Agen Bank';
    if (nominal <= 0 && fee <= 0) return;
    cart.push({ id, name, price: fee, quantity: 1, maxStock: 1, bank: true, nominal_transaksi: nominal, fee });
    renderCart();
}

function addCustomerPaymentToCart() {
    const customerSelect = document.getElementById('customerPaymentCustomerId');
    const customerId = parseInt(customerSelect.value);
    const nominal = parseFloat(document.getElementById('customerPaymentNominal').value) || 0;
    if (!customerId) {
        alert('Pilih pelanggan terlebih dahulu.');
        return;
    }
    if (nominal <= 0) {
        alert('Nominal pembayaran wajib diisi.');
        return;
    }
    const customerName = customerSelect.selectedOptions[0]?.dataset?.name || customerSelect.selectedOptions[0]?.text || 'Pelanggan';
    const id = 900000000 + customerId;
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        existingItem.price = nominal;
        existingItem.nominal_transaksi = nominal;
    } else {
        cart.push({
            id,
            name: `Pembayaran - ${customerName}`,
            price: nominal,
            quantity: 1,
            maxStock: 1,
            customerPayment: true,
            customerName,
            nominal_transaksi: nominal
        });
    }
    renderCart();
}

function addCashOutToCart() {
    const floatAccountId = document.getElementById('cashOutFloatAccountId').value;
    const nominal = parseFloat(document.getElementById('cashOutNominal').value) || 0;
    const fee = parseFloat(document.getElementById('cashOutFee').value) || 0;
    if (!floatAccountId) {
        alert('Pilih akun float terlebih dahulu.');
        return;
    }
    if (nominal <= 0) {
        alert('Nominal tarik tunai wajib diisi.');
        return;
    }
    const id = 800000000 + Date.now();
    cart.push({
        id,
        name: 'Tarik Tunai',
        price: fee,
        quantity: 1,
        maxStock: 1,
        cashOut: true,
        float_account_id: floatAccountId,
        nominal_transaksi: nominal,
        fee
    });
    renderCart();
}

function addTopUpToCart() {
    const wallet = document.getElementById('topUpWallet').value;
    const walletNames = {
        'dana': 'DANA',
        'ovo': 'OVO',
        'gopay': 'GoPay',
        'shopeePay': 'ShopeePay'
    };
    const walletName = walletNames[wallet] || wallet;
    const nominal = parseFloat(document.getElementById('topUpNominal').value) || 0;
    const fee = parseFloat(document.getElementById('topUpFee').value) || 0;
    if (nominal <= 0) {
        alert('Nominal top up wajib diisi.');
        return;
    }
    const id = 700000000 + Date.now();
    cart.push({
        id,
        name: `Top Up ${walletName}`,
        price: fee,
        quantity: 1,
        maxStock: 1,
        topUp: true,
        wallet,
        nominal_transaksi: nominal,
        fee
    });
    renderCart();
}

function addPPOBToCart() {
    const service = document.getElementById('ppobService').value;
    const serviceNames = {
        'pulsa': 'Pulsa',
        'data': 'Paket Data',
        'listrik': 'Listrik PLN',
        'bpjs': 'BPJS'
    };
    const serviceName = serviceNames[service] || service;
    const number = document.getElementById('ppobNumber').value;
    const nominal = parseFloat(document.getElementById('ppobNominal').value) || 0;
    const fee = parseFloat(document.getElementById('ppobFee').value) || 0;
    if (nominal <= 0) {
        alert('Nominal wajib diisi.');
        return;
    }
    if (!number) {
        alert('Nomor pelanggan wajib diisi.');
        return;
    }
    const id = 600000000 + Date.now();
    cart.push({
        id,
        name: `${serviceName} - ${number}`,
        price: fee,
        quantity: 1,
        maxStock: 1,
        ppob: true,
        ppobService: service,
        number,
        nominal_transaksi: nominal,
        fee
    });
    renderCart();
}

function addToCart(id, name, price, maxStock, type = 'product') {
    const existingItem = cart.find(item => item.id === id);
    if (existingItem) {
        if (existingItem.quantity < maxStock) {
            existingItem.quantity++;
        } else {
            alert('Stok limit tercapai!');
        }
    } else {
        const isService = (type === 'service');
        const isCustomerPayment = (type === 'customer_payment');
        cart.push({ id, name, price, quantity: 1, maxStock, service: isService, customerPayment: isCustomerPayment });
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

function updateQuantity(id, delta) {
    const item = cart.find(item => item.id === id);
    if (item) {
        const newQty = item.quantity + delta;
        if (newQty > 0 && newQty <= item.maxStock) {
            item.quantity = newQty;
        } else if (newQty > item.maxStock) {
            alert('Stok limit tercapai!');
        } else {
            removeFromCart(id);
            return;
        }
        renderCart();
    }
}

function calculateTotal() {
    let total = 0;
    cart.forEach(item => {
        total += (item.bank || item.cashOut || item.topUp || item.ppob) ? ((item.nominal_transaksi || 0) + (item.fee || 0)) : (item.price * item.quantity);
    });
    return total;
}

function renderCart() {
    const cartList = document.getElementById('cartItems');
    const emptyMsg = document.getElementById('emptyCartMsg');
    const btnCheckout = document.getElementById('btnCheckout');

    cartList.innerHTML = '';
    let total = calculateTotal();
    let count = 0;

    if (cart.length === 0) {
        emptyMsg.style.display = 'block';
        btnCheckout.disabled = true;
        cartList.innerHTML = '';
        cartList.appendChild(emptyMsg);
    } else {
        emptyMsg.style.display = 'none';
        btnCheckout.disabled = false;

        cart.forEach(item => {
            count += item.quantity;
            const subtotal = (item.bank || item.cashOut || item.topUp || item.ppob) ? ((item.nominal_transaksi || 0) + (item.fee || 0)) : (item.price * item.quantity);

            const li = document.createElement('div');
            li.className = 'cart-item';
            li.innerHTML = `
                <div class="cart-item-left">
                    <div class="cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="cart-item-meta">
                        ${item.bank || item.cashOut || item.topUp || item.ppob
                            ? (item.nominal_transaksi ? `Nominal: Rp ${new Intl.NumberFormat('id-ID').format(item.nominal_transaksi)}${item.fee ? ` | Fee: Rp ${new Intl.NumberFormat('id-ID').format(item.fee)}` : ''}`
                            : `Rp ${new Intl.NumberFormat('id-ID').format(item.price)} x ${item.quantity}`
                        }
                    </div>
                </div>
                <div class="cart-item-right">
                    <span class="cart-item-total">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                    <div class="d-flex gap-2 align-items-center">
                        ${(item.bank || item.customerPayment || item.cashOut || item.topUp || item.ppob)
                            ? ''
                            : `<div class="btn-group btn-group-sm">
                                <button class="btn atk-secondary-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                                <button class="btn atk-secondary-btn" disabled>${item.quantity}</button>
                                <button class="btn atk-secondary-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                              </div>`
                        }
                        <button class="btn btn-sm btn-link text-danger p-0" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            cartList.appendChild(li);
        });
    }

    document.getElementById('totalItems').textContent = count;
    document.getElementById('totalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(total)}`;
    calculateChange();
    updatePengurusVisibility();
}

function calculateChange() {
    const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
    const method = document.getElementById('paymentMethod').value;
    const total = calculateTotal();
    const change = method === 'cash' ? (cash - total) : 0;
    const changeEl = document.getElementById('changeAmount');

    if (change >= 0) {
        changeEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(change)}`;
        changeEl.classList.remove('text-danger');
        changeEl.classList.add('text-success');
    } else {
        changeEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(change)}`;
        changeEl.classList.add('text-danger');
        changeEl.classList.remove('text-success');
    }
}

function updatePengurusVisibility() {
    const hasService = cart.some(i => i.service === true);
    const hasCustomerPayment = cart.some(i => i.customerPayment === true);
    const method = document.getElementById('paymentMethod')?.value;
    const div = document.getElementById('pengurusDiv');
    if (!div) return;
    if (hasCustomerPayment || (hasService && method === 'hutang')) {
        div.style.display = 'block';
    } else {
        div.style.display = 'none';
        const sel = document.getElementById('coordinatorId');
        if (sel) sel.value = '';
    }
}

function isMobileDevice() {
    const ua = navigator.userAgent || navigator.vendor || '';
    return /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(ua) || window.innerWidth <= 768;
}

function withAutoPrint(url) {
    try {
        const absoluteUrl = new URL(url, window.location.origin);
        absoluteUrl.searchParams.set('autoprint', '1');
        absoluteUrl.searchParams.set('source', 'pos-atk-mobile');
        return absoluteUrl.toString();
    } catch (error) {
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}autoprint=1&source=pos-atk-mobile`;
    }
}

function formatRupiah(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function buildAtkReceiptBlob(transactionId, payload) {
    if (typeof html2canvas === 'undefined') {
        return null;
    }

    const capture = document.createElement('div');
    capture.style.position = 'fixed';
    capture.style.left = '-10000px';
    capture.style.top = '0';
    capture.style.width = '360px';
    capture.style.background = '#ffffff';
    capture.style.color = '#111827';
    capture.style.padding = '14px';
    capture.style.fontFamily = 'Arial, sans-serif';
    capture.style.fontSize = '12px';
    capture.style.lineHeight = '1.4';
    capture.style.border = '1px solid #e5e7eb';
    const itemsHtml = payload.items.map((item) => `
        <tr>
            <td style="padding:4px 0;border-bottom:1px dashed #d1d5db;">${escapeHtml(item.name)}</td>
            <td style="padding:4px 0;text-align:center;border-bottom:1px dashed #d1d5db;">${item.quantity}</td>
            <td style="padding:4px 0;text-align:right;border-bottom:1px dashed #d1d5db;">${formatRupiah(item.price)}</td>
            <td style="padding:4px 0;text-align:right;border-bottom:1px dashed #d1d5db;">${formatRupiah(item.subtotal)}</td>
        </tr>
    `).join('');

    capture.innerHTML = `
        <div style="text-align:center;font-weight:700;font-size:14px;">${escapeHtml(@json(config('app.name')))}</div>
        <div style="text-align:center;color:#4b5563;">Detail Transaksi ATK</div>
        <div style="margin-top:8px;">No. Transaksi: #${escapeHtml(transactionId)}</div>
        <div>Tanggal: ${escapeHtml(new Date().toLocaleString('id-ID'))}</div>
        <div style="margin-top:8px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:4px 0;border-bottom:1px solid #111827;">Item</th>
                        <th style="text-align:center;padding:4px 0;border-bottom:1px solid #111827;">Qty</th>
                        <th style="text-align:right;padding:4px 0;border-bottom:1px solid #111827;">Harga</th>
                        <th style="text-align:right;padding:4px 0;border-bottom:1px solid #111827;">Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>
        <div style="margin-top:8px;display:flex;justify-content:space-between;">
            <span>Metode Bayar</span>
            <strong>${escapeHtml((payload.paymentMethod || '').toUpperCase())}</strong>
        </div>
        <div style="margin-top:4px;display:flex;justify-content:space-between;font-size:14px;">
            <span>Total</span>
            <strong>${formatRupiah(payload.total)}</strong>
        </div>
    `;

    document.body.appendChild(capture);
    try {
        const canvas = await html2canvas(capture, { scale: 2, backgroundColor: '#ffffff' });
        return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    } finally {
        capture.remove();
    }
}

async function sendAtkWhatsappReceipt(transactionId, phone, payload) {
    const formData = new FormData();
    formData.append('phone', phone);
    try {
        const blob = await buildAtkReceiptBlob(transactionId, payload);
        if (blob) {
            formData.append('receipt_image', blob, `struk-atk-${transactionId}.png`);
        }
    } catch (error) {
        console.error(error);
    }

    return fetch(`{{ url('atk/transactions') }}/${transactionId}/whatsapp-receipt`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    });
}

function processTransaction() {
    if (cart.length === 0) return;
    console.log('Cart sebelum checkout:', cart);

    const paymentMethod = document.getElementById('paymentMethod').value;
    const transactionCategory = cart.some(item => item.customerPayment === true) ? 'pembayaran_pelanggan' : 'penjualan_atk';
    const cashAmount = paymentMethod === 'cash' ? (parseFloat(document.getElementById('cashAmount').value) || 0) : null;
    let total = calculateTotal();

    if (paymentMethod === 'cash' && cashAmount < total) {
        alert('Uang diterima tidak cukup!');
        return;
    }

    const pengurusVisible = document.getElementById('pengurusDiv').style.display !== 'none';
    const coordEl = document.getElementById('coordinatorId');
    const coordinatorId = (pengurusVisible && coordEl && coordEl.value) ? coordEl.value : undefined;
    if (pengurusVisible && !coordinatorId) {
        alert('Pilih pengurus untuk transaksi ini.');
        return;
    }
    const data = {
        items: cart.map(item => {
            const mappedItem = {
                id: item.customerPayment || item.cashOut || item.topUp || item.ppob ? null : item.id,
                type: item.bank ? 'bank' : (item.service ? 'service' : (item.customerPayment ? 'customer_payment' : (item.cashOut ? 'cash_out' : (item.topUp ? 'top_up' : (item.ppob ? 'ppob' : 'product'))))),
                quantity: item.quantity,
                nominal_transaksi: (item.bank || item.customerPayment || item.cashOut || item.topUp || item.ppob) ? item.nominal_transaksi : undefined,
                fee: (item.bank || item.cashOut || item.topUp || item.ppob) ? item.fee : undefined,
                customer_name: item.customerPayment ? item.customerName : undefined,
                float_account_id: item.cashOut ? item.float_account_id : undefined
            };
            console.log('Mapped item:', mappedItem);
            return mappedItem;
        }),
        transaction_category: transactionCategory,
        payment_method: paymentMethod,
        cash_amount: cashAmount,
        coordinator_id: coordinatorId
    };
    console.log('Data to send:', data);

    const btn = document.getElementById('btnCheckout');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';

    fetch('{{ route("atk.transactions.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(async result => {
        if (result.success) {
            const receiptUrl = '{{ url("atk/transactions") }}/' + result.transaction_id + '/receipt';
            if (isMobileDevice()) {
                window.location.href = withAutoPrint(receiptUrl);
            } else {
                window.open(receiptUrl, '_blank', 'width=400,height=600');
            }

            const sendWa = document.getElementById('sendWhatsapp').checked;
            const phone = document.getElementById('customerPhone').value;
            if (sendWa && phone) {
                await sendAtkWhatsappReceipt(result.transaction_id, phone, {
                    items: cart.map((item) => ({
                        name: item.bank
                            ? `${item.name} (Nominal ${formatRupiah(item.nominal_transaksi || 0)})`
                            : item.name,
                        quantity: item.quantity,
                        price: item.bank ? (item.fee || 0) : item.price,
                        subtotal: item.bank
                            ? ((item.nominal_transaksi || 0) + (item.fee || 0))
                            : (item.price * item.quantity)
                    })),
                    total,
                    paymentMethod
                }).catch(() => {});
            }

            cart = [];
            document.getElementById('cashAmount').value = '';
            document.getElementById('customerPhone').value = '';
            document.getElementById('sendWhatsapp').checked = false;
            renderCart();
            alert('Transaksi berhasil!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = 'Proses Pembayaran';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan.');
        btn.disabled = false;
        btn.innerHTML = 'Proses Pembayaran';
    });
}
</script>

<style>
.atk-pos-page {
    color: #1e293b;
}

.atk-pos-shell {
    max-width: 1440px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
}

.atk-pos-header {
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    gap: 1rem;
    margin-bottom: 1.1rem;
}

.atk-pos-title {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 700;
    color: #0f172a;
}

.atk-pos-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.9rem;
    color: #64748b;
}

.atk-current-time {
    display: inline-flex;
    align-items: center;
    min-height: 42px;
    min-width: 170px;
    justify-content: center;
    padding: 0.45rem 0.9rem;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}

.atk-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.atk-checkout-card {
    position: sticky;
    top: 1.2rem;
}

.atk-card-header {
    padding: 1rem 1.1rem;
    background: linear-gradient(180deg, rgba(58, 126, 232, 0.22) 0%, rgba(231, 236, 255, 0.30) 100%);
    border-bottom-color: rgba(22, 22, 23, 0.98);
    display: flex;
    gap: 0.8rem;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}

.atk-card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.atk-card-title i {
    color: #3b82f6;
}

.atk-card-body {
    padding: 1rem;
}

.atk-filter-group {
    background: #e2e8f0;
    border-radius: 0.8rem;
    padding: 0.2rem;
    display: inline-flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.atk-filter-group .filter-btn {
    border: 0;
    background: transparent;
    color: #475569;
    border-radius: 0.6rem;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    transition: all 0.2s ease;
}

.atk-filter-group .filter-btn.active {
    background: #fff;
    color: #2563eb;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.12);
}

.atk-filter-group .filter-btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

.product-card {
    height: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 0.8rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    background: #fff;
}

.product-card:hover {
    border-color: #60a5fa;
    box-shadow: 0 10px 18px rgba(37, 99, 235, 0.14);
}

.product-card:active {
    transform: none;
}

.product-card-product {
    border-color: #bfdbfe;
}

.product-card-service {
    border-color: #fed7aa;
}

.product-image-wrap {
    aspect-ratio: 1 / 1;
    border-radius: 0.8rem;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .25s ease;
}

.product-fallback-icon {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    background: #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #64748b;
    transition: transform .25s ease;
}

.product-title {
    font-size: 0.94rem;
    font-weight: 700;
    line-height: 1.35;
    color: #1e293b;
    margin: 0;
}

.product-description {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.32rem 0 0;
    min-height: 2.2em;
}

.product-description-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.32rem;
    margin-top: 0.35rem;
    min-height: 1.8rem;
}

.product-description-chip {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 0.24rem 0.52rem;
    border-radius: 0.65rem;
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    color: #3730a3;
    font-size: 0.68rem;
    line-height: 1.25;
    font-weight: 600;
}

.product-description-chip-empty {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #64748b;
    font-weight: 500;
}

.product-meta {
    margin-top: 0.6rem;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.product-price {
    font-size: 0.9rem;
    font-weight: 700;
    color: #2563eb;
    width: 100%;
    line-height: 1.2;
}

.atk-field-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.68rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.07em;
}

.atk-input {
    min-height: 42px;
    border-color: #e2e8f0;
    background: #f8fafc;
    border-radius: 0.7rem;
}

.atk-input::placeholder {
    color: #94a3b8;
}

.atk-input:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.18);
    background: #fff;
}

.atk-input-group-text {
    border-color: #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 0.7rem 0 0 0.7rem;
}

.atk-secondary-btn {
    border-radius: 0.7rem;
    border: 1px solid #cbd5e1;
    background: #e2e8f0;
    color: #334155;
    min-width: 66px;
    font-weight: 600;
}

.atk-secondary-btn:hover {
    background: #cbd5e1;
}

.atk-secondary-btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.35);
}

.atk-inline-check .form-check-input {
    margin-top: 0.2rem;
    border-color: #94a3b8;
}

.atk-inline-check .form-check-label {
    color: #475569;
    font-size: 0.84rem;
}

#cartItems {
    border: 1px solid #e2e8f0;
    border-radius: 0.8rem;
    background: #fff;
    padding: 0.7rem;
    max-height: 230px;
    overflow-y: auto;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.cart-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
    padding-bottom: 0.65rem;
    margin-bottom: 0.65rem;
    border-bottom: 1px solid #e2e8f0;
}

.cart-item:last-child {
    border-bottom: 0;
    margin-bottom: 0;
    padding-bottom: 0;
}

.cart-item-left {
    flex: 1 1 auto;
    min-width: 0;
}

.cart-item-name {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.9rem;
}

.cart-item-meta {
    display: block;
    margin-top: 0.15rem;
    color: #64748b;
    font-size: 0.75rem;
}

.cart-item-right {
    flex: 0 0 auto;
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.cart-item-total {
    display: inline-block;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
}

.atk-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #475569;
    font-size: 0.9rem;
}

.atk-summary-divider {
    padding-top: 0.65rem;
    border-top: 1px solid #e2e8f0;
}

.atk-summary-total {
    margin-top: 0.2rem;
    color: #0f172a;
    font-weight: 700;
    font-size: 1rem;
}

.atk-summary-total #totalAmount {
    color: #2563eb;
    font-size: 1.2rem;
    font-weight: 800;
}

.atk-change-box {
    border-radius: 0.75rem;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    padding: 0.55rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #1e3a8a;
    font-size: 0.84rem;
}

.atk-primary-btn {
    min-height: 44px;
    border-radius: 0.8rem;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    border: 0;
    color: #fff;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.atk-primary-btn:hover {
    color: #fff;
    filter: brightness(1.03);
}

.atk-primary-btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 0.24rem rgba(59, 130, 246, 0.3);
}

.atk-scan-reader video {
    width: 100% !important;
    height: auto !important;
    object-fit: cover;
    border-radius: 0.5rem;
    filter: brightness(1.2) contrast(1.15) saturate(1.05);
}

@media (max-width: 991.98px) {
    .atk-checkout-card {
        position: static;
    }
}

@media (max-width: 767.98px) {
    .atk-pos-shell {
        padding: 0.7rem 0.35rem 0.8rem;
    }

    .atk-pos-header {
        justify-content: flex-end;
        align-items: flex-end;
        margin-bottom: 1rem;
    }

    .atk-current-time {
        min-width: 0;
        width: fit-content;
    }

    .atk-card-header {
        padding: 0.72rem;
        gap: 0.55rem;
    }

    .atk-card-body {
        padding: 0.72rem;
    }

    .atk-filter-group {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }

    .atk-filter-group .filter-btn {
        width: 100%;
        padding-left: 0.2rem;
        padding-right: 0.2rem;
        min-height: 34px;
    }

    .cart-item {
        flex-direction: column;
        gap: 0.45rem;
    }

    .cart-item-right {
        width: 100%;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }

    .atk-summary-row {
        font-size: 0.84rem;
    }

    .atk-summary-total {
        font-size: 1rem;
    }
}

@media (max-width: 575.98px) {
    .atk-card {
        border-radius: 0.85rem;
    }

    .product-card {
        border-radius: 0.85rem;
        padding: 0.62rem;
    }

    .product-image-wrap {
        aspect-ratio: 1 / 1;
        margin-bottom: 0.5rem;
    }

    .product-title {
        font-size: 0.8rem;
        line-height: 1.2;
        margin-bottom: 0.3rem;
    }

    .product-description-chips {
        min-height: 0;
        margin-top: 0.2rem;
        gap: 0.22rem;
    }

    .product-description-chip {
        font-size: 0.58rem;
        min-height: 20px;
        padding: 0.16rem 0.34rem;
    }

    .product-price {
        font-size: 0.76rem;
    }

    #cartItems {
        max-height: 220px;
    }

    .atk-filter-group {
        grid-template-columns: repeat(3, 1fr);
    }

    .atk-filter-group .filter-btn {
        font-size: 0.65rem;
        padding: 0.25rem 0.1rem;
    }
}

[data-bs-theme="dark"] .atk-pos-page {
    color: #e2e8f0;
    background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
}

[data-bs-theme="dark"] .atk-pos-title {
    color: #f8fafc;
}

[data-bs-theme="dark"] .atk-pos-subtitle {
    color: #94a3b8;
}

[data-bs-theme="dark"] .atk-current-time,
[data-bs-theme="dark"] .atk-card,
[data-bs-theme="dark"] .product-card,
[data-bs-theme="dark"] #cartItems {
    background: #0f172a;
    border-color: #334155;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .atk-current-time {
    box-shadow: none;
    color: #cbd5e1;
    border-color: #334155;
}

[data-bs-theme="dark"] .atk-card {
    box-shadow: 0 14px 30px rgba(2, 6, 23, 0.45);
}

[data-bs-theme="dark"] .atk-card-header {
    background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
    border-bottom-color: rgba(96, 165, 250, 0.28);
}

[data-bs-theme="dark"] .atk-card-title {
    color: #e2e8f0;
}

[data-bs-theme="dark"] .atk-card-title i {
    color: #93c5fd;
}

[data-bs-theme="dark"] .product-image-wrap {
    background: #1e293b;
}

[data-bs-theme="dark"] .product-card-product {
    border-color: rgba(96, 165, 250, 0.45);
}

[data-bs-theme="dark"] .product-card-service {
    border-color: rgba(251, 146, 60, 0.45);
}

[data-bs-theme="dark"] .product-fallback-icon {
    background: #334155;
    color: #bfdbfe;
}

[data-bs-theme="dark"] .product-title,
[data-bs-theme="dark"] .cart-item-name,
[data-bs-theme="dark"] .cart-item-total {
    color: #f8fafc;
}

[data-bs-theme="dark"] .product-description,
[data-bs-theme="dark"] .atk-field-label,
[data-bs-theme="dark"] .atk-inline-check .form-check-label {
    color: #94a3b8;
}

[data-bs-theme="dark"] .product-description-chip {
    background: rgba(59, 130, 246, 0.2);
    border-color: rgba(96, 165, 250, 0.42);
    color: #bfdbfe;
}

[data-bs-theme="dark"] .product-description-chip-empty {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
}

[data-bs-theme="dark"] .product-price {
    color: #60a5fa;
}

[data-bs-theme="dark"] .atk-input {
    background: #0b1220;
    border-color: #334155;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .atk-input::placeholder {
    color: #64748b;
}

[data-bs-theme="dark"] .atk-input option,
[data-bs-theme="dark"] .atk-input optgroup {
    background: #0f172a;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .atk-input-group-text {
    background: linear-gradient(135deg, #0b1220 0%, #0f172a 100%);
    border-color: #334155;
    color: #94a3b8;
}

[data-bs-theme="dark"] .atk-secondary-btn {
    background: #1e293b;
    border-color: #334155;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .atk-secondary-btn:hover {
    background: #334155;
    border-color: #475569;
}

[data-bs-theme="dark"] .atk-filter-group {
    background: #1e293b;
}

[data-bs-theme="dark"] .atk-inline-check .form-check-input {
    background-color: #0b1220;
    border-color: #475569;
}

[data-bs-theme="dark"] .atk-inline-check .form-check-input:checked {
    background-color: #2563eb;
    border-color: #2563eb;
}

[data-bs-theme="dark"] .atk-inline-check .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.24);
}

[data-bs-theme="dark"] .atk-filter-group .filter-btn {
    color: #cbd5e1;
}

[data-bs-theme="dark"] .atk-filter-group .filter-btn.active {
    background: #0b1220;
    color: #93c5fd;
    box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.32);
}

[data-bs-theme="dark"] #cartItems .text-muted,
[data-bs-theme="dark"] #emptyCartMsg {
    color: #94a3b8 !important;
}

[data-bs-theme="dark"] .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

[data-bs-theme="dark"] .atk-summary-row,
[data-bs-theme="dark"] .cart-item-meta {
    color: #94a3b8;
}

[data-bs-theme="dark"] .cart-item {
    border-bottom-color: #334155;
}

[data-bs-theme="dark"] .atk-summary-divider {
    border-top-color: #334155;
}

[data-bs-theme="dark"] .atk-summary-total {
    color: #f8fafc;
}

[data-bs-theme="dark"] .atk-summary-total #totalAmount {
    color: #60a5fa;
}

[data-bs-theme="dark"] .atk-change-box {
    background: rgba(30, 64, 175, 0.22);
    border-color: rgba(96, 165, 250, 0.35);
    color: #bfdbfe;
}

[data-bs-theme="dark"] .atk-primary-btn {
    background: linear-gradient(135deg, #15803d, #16a34a);
}
</style>
@endpush
@endsection
