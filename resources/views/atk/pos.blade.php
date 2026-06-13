@extends('layouts.app')

@section('title', __('ATK POS'))

@section('content')
<div class="container-fluid atk-pos-page">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            @if(!$activeRegister)
            <div class="card border-left-warning shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="fw-bold text-warning mb-1">
                                <i class="fa-solid fa-door-closed me-2"></i>Tidak Ada Shift Aktif
                            </div>
                            <div class="text-muted small">Silakan buka shift di halaman Dashboard sebelum melakukan transaksi.</div>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('atk.dashboard') }}" class="btn btn-outline-warning">
                                <i class="fa-solid fa-arrow-left me-2"></i>Ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card border-left-success shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="fw-bold text-success mb-1">
                                <i class="fa-solid fa-door-open me-2"></i>{{ $activeRegister->name }}
                            </div>
                            <div class="text-muted small">Dibuka: {{ $activeRegister->opened_at?->format('H:i d/m/Y') }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <div class="fw-bold mb-1">Saldo Kas</div>
                                <div class="h5 mb-0 text-success">Rp {{ number_format($activeRegister->closing_balance, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="row h-100">
        <!-- Product & Service List -->
        <div class="col-12 col-lg-8 mb-3 mb-lg-0">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <div class="input-group">
                        <span class="input-group-text  border-end-0"><i class="fas fa-search text-body-secondary"></i></span>
                        <input type="text" id="productSearch" class="form-control border-start-0 ps-0" placeholder="Search products by name or code...">
                        <button type="button" class="btn btn-outline-primary" id="openAtkBarcodeScan" title="Scan barcode via kamera">
                            <i class="fa-solid fa-barcode"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="btn-group btn-group-sm flex-wrap" role="group">
                            <button class="btn btn-outline-primary" id="tabProducts" onclick="switchTab('products')">
                                <i class="fa-solid fa-box"></i> Produk
                            </button>
                            <button class="btn btn-outline-secondary" id="tabServices" onclick="switchTab('services')">
                                <i class="fa-solid fa-print"></i> Jasa Potocopy
                            </button>
                            <button class="btn btn-outline-success" id="tabBank" onclick="switchTab('bank')">
                                <i class="fa-solid fa-building-columns"></i> Agen Bank
                            </button>
                            <button class="btn btn-outline-danger" id="tabCashOut" onclick="switchTab('cash-out')">
                                <i class="fa-solid fa-money-bill-transfer"></i> Tarik Tunai
                            </button>
                            <button class="btn btn-outline-warning" id="tabTopUp" onclick="switchTab('top-up')">
                                <i class="fa-solid fa-wallet"></i> Top Up E-Wallet
                            </button>
                            <button class="btn btn-outline-info" id="tabPPOB" onclick="switchTab('ppob')">
                                <i class="fa-solid fa-bolt-lightning"></i> PPOB
                            </button>
                            <button class="btn btn-outline-info" id="tabCustomerPayments" onclick="switchTab('customer-payments')">
                                <i class="fa-solid fa-money-check-dollar"></i> Pembayaran Pelanggan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body overflow-auto product-panel-body" style="max-height: 75vh;">
                    <div class="row g-3" id="productList">
                        @foreach($products as $product)
                        <div class="col-md-3 col-sm-4 col-6 product-item" data-name="{{ strtolower($product->name) }}" data-code="{{ strtolower($product->code) }}">
                            <div class="card h-100 product-card cursor-pointer" data-fasttap
                                 data-id="{{ $product->id }}"
                                 data-name="{{ $product->name }}"
                                 data-price="{{ $product->price }}"
                                 data-type="product"
                                 data-stock="{{ $product->stock }}">
                                <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                    <div class="mb-2 d-flex align-items-center justify-content-center rounded " style="height: 100px; overflow: hidden;">
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: cover;" alt="{{ $product->name }}">
                                        @else
                                            <i class="fas fa-image fa-2x text-body-secondary"></i>
                                        @endif
                                    </div>
                                    <h6 class="card-title mb-1 text-truncate" title="{{ $product->name }}">{{ $product->name }}</h6>
                                    <p class="card-text text-primary fw-bold mb-0">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                    <small class="text-body-secondary">Stock: {{ $product->stock }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="row g-3 d-none" id="serviceList">
                        @foreach(($services ?? []) as $service)
                        <div class="col-md-3 col-sm-4 col-6 service-item" data-name="{{ strtolower($service->name) }}" data-code="{{ strtolower($service->code) }}">
                            <div class="card h-100 product-card cursor-pointer" data-fasttap
                                 data-id="{{ $service->id }}"
                                 data-name="{{ $service->name }}"
                                 data-price="{{ $service->price }}"
                                 data-type="service"
                                 data-stock="999999">
                                <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                    <div class="mb-2 d-flex align-items-center justify-content-center rounded " style="height: 100px; overflow: hidden;">
                                        @if($service->image)
                                            <img src="{{ Storage::url($service->image) }}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: cover;" alt="{{ $service->name }}">
                                        @else
                                            <i class="fas fa-print fa-2x text-body-secondary"></i>
                                        @endif
                                    </div>
                                    <h6 class="card-title mb-1 text-truncate" title="{{ $service->name }}">{{ $service->name }}</h6>
                                    <p class="card-text text-primary fw-bold mb-0">Rp {{ number_format($service->price, 0, ',', '.') }}</p>
                                    <small class="text-body-secondary">Jasa</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if(empty($services) || count($services)===0)
                            <div class="text-center py-5">
                                <p class="text-body-secondary">Belum ada jasa. <a href="{{ route('atk.products.create') }}">Tambah Jasa</a></p>
                            </div>
                        @endif
                    </div>
                    <div class="d-none" id="customerPaymentList">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pelanggan</label>
                                <select id="customerPaymentCustomerId" class="form-select">
                                    <option value="">Pilih Pelanggan</option>
                                    @foreach(($customers ?? []) as $customer)
                                        <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }}{{ !empty($customer->phone) ? ' - '.$customer->phone : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal Pembayaran</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="customerPaymentNominal" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-info text-white w-100" onclick="addCustomerPaymentToCart()">
                                    <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                        @if(empty($customers) || count($customers)===0)
                            <div class="text-center py-5">
                                <p class="text-body-secondary">Belum ada data pelanggan. <a href="{{ route('customers.create') }}">Tambah Pelanggan</a></p>
                            </div>
                        @endif
                    </div>
                    <div class="d-none" id="bankPanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Layanan</label>
                                <select id="bankServiceId" class="form-select">
                                    @foreach(($bankServices ?? []) as $bs)
                                        <option value="{{ $bs->id }}">{{ $bs->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal Transfer</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="bankNominal" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="bankFee" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-success w-100" onclick="addBankToCart()">
                                    <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-none" id="cashOutPanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nominal Tarik Tunai</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="cashOutNominal" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="cashOutFee" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-danger w-100" onclick="addCashOutToCart()">
                                    <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-none" id="topUpPanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">E-Wallet</label>
                                <select id="topUpWallet" class="form-select">
                                    <option value="dana">DANA</option>
                                    <option value="ovo">OVO</option>
                                    <option value="gopay">GoPay</option>
                                    <option value="shopeePay">ShopeePay</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal Top Up</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="topUpNominal" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="topUpFee" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-warning text-dark w-100" onclick="addTopUpToCart()">
                                    <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-none" id="ppobPanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Layanan</label>
                                <select id="ppobService" class="form-select">
                                    <option value="pulsa">Pulsa</option>
                                    <option value="data">Paket Data</option>
                                    <option value="listrik">Listrik PLN</option>
                                    <option value="bpjs">BPJS</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Pelanggan</label>
                                <input type="text" id="ppobNumber" class="form-control" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="ppobNominal" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="ppobFee" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-info text-white w-100" onclick="addPPOBToCart()">
                                    <i class="fa-solid fa-plus me-2"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($products->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted">No products available. <a href="{{ route('atk.products.create') }}">Add Product</a></p>
                        </div>
                    @endif
                        </div>
                    </div>
                </div>

                <!-- Cart -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm h-100 d-flex flex-column">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Current Order</h5>
                        </div>
                        <div class="mb-3 custom-scrollbar" id="cartItems" style="max-height: 50vh;">
                            <div class="text-center py-5 text-body-secondary" id="emptyCartMessage">
                                <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                                <p>Cart is empty</p>
                            </div>
                            <ul class="list-group list-group-flush" id="cartList"></ul>
                        </div>
                        <div class="card-footer border-top">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-body-secondary">Total Items:</span>
                                <span class="fw-bold" id="totalItems">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="h5 mb-0">Total:</span>
                                <span class="h4 mb-0 text-primary" id="totalAmount">Rp 0</span>
                            </div>
                            
                            
                            <div class="mb-3">
                                 <label class="form-label">Metode Pembayaran</label>
                                 <select class="form-select" id="paymentMethod">
                                     <option value="cash">Cash</option>
                                     <option value="transfer">Transfer</option>
                                     <option value="qris">QRIS</option>
                                     <option value="hutang">Hutang</option>
                                 </select>
                            </div>
                            
                            <div class="mb-3 d-none" id="pengurusDiv">
                                <label class="form-label">Pengurus</label>
                                <select class="form-select" id="coordinatorId">
                                    <option value="">Pilih Pengurus (wajib untuk hutang jasa potocopy)</option>
                                    @foreach(($coordinators ?? []) as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3" id="cashInputDiv">
                                <label class="form-label">Cash Received</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="cashAmount" placeholder="0">
                                </div>
                                <div class="mt-1 d-flex justify-content-between text-body-secondary small">
                                     <span>Change:</span>
                                     <span id="changeAmount" class="fw-bold">Rp 0</span>
                                </div>
                            </div>
                    
                    <div class="mb-2">
                        <label class="form-label">No. WhatsApp (opsional)</label>
                        <input type="text" class="form-control" id="customerPhone" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="" id="sendWhatsapp">
                        <label class="form-check-label" for="sendWhatsapp">
                            Kirim struk via WhatsApp
                        </label>
                    </div>

                    <button class="btn btn-success w-100 py-2" onclick="processTransaction()" id="btnCheckout" disabled>
                        <i class="fas fa-check-circle me-2"></i> Checkout
                    </button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
    .atk-scan-reader video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
        border-radius: 0.5rem;
        filter: brightness(1.2) contrast(1.15) saturate(1.05);
    }
</style>
<script>
    let cart = [];
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
    
    // Attach click handlers to product/service cards
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

        const cashDiv = document.getElementById('cashInputDiv');
        const methodSel = document.getElementById('paymentMethod');
        methodSel.addEventListener('change', function () {
            if (this.value === 'cash') {
                cashDiv.style.display = '';
            } else {
                cashDiv.style.display = 'none';
                document.getElementById('cashAmount').value = '';
                document.getElementById('changeAmount').textContent = 'Rp 0';
            }
            updatePengurusVisibility();
        });
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
    });

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
            item.style.display = (name.includes(search) || code.includes(search)) ? 'block' : 'none';
        });
    });

    function switchTab(tab) {
        const pList = document.getElementById('productList');
        const sList = document.getElementById('serviceList');
        const cpList = document.getElementById('customerPaymentList');
        const bPanel = document.getElementById('bankPanel');
        const coPanel = document.getElementById('cashOutPanel');
        const tuPanel = document.getElementById('topUpPanel');
        const ppPanel = document.getElementById('ppobPanel');
        const tabP = document.getElementById('tabProducts');
        const tabS = document.getElementById('tabServices');
        const tabB = document.getElementById('tabBank');
        const tabCO = document.getElementById('tabCashOut');
        const tabTU = document.getElementById('tabTopUp');
        const tabPP = document.getElementById('tabPPOB');
        const tabCP = document.getElementById('tabCustomerPayments');
        
        // Reset all
        pList.classList.add('d-none');
        sList.classList.add('d-none');
        cpList.classList.add('d-none');
        bPanel.classList.add('d-none');
        coPanel.classList.add('d-none');
        tuPanel.classList.add('d-none');
        ppPanel.classList.add('d-none');
        
        tabP.classList.remove('active');
        tabS.classList.remove('active');
        tabB.classList.remove('active');
        tabCO.classList.remove('active');
        tabTU.classList.remove('active');
        tabPP.classList.remove('active');
        tabCP.classList.remove('active');
        
        if (tab === 'products') {
            pList.classList.remove('d-none');
            tabP.classList.add('active');
        } else if (tab === 'services') {
            sList.classList.remove('d-none');
            tabS.classList.add('active');
        } else if (tab === 'bank') {
            bPanel.classList.remove('d-none');
            tabB.classList.add('active');
        } else if (tab === 'cash-out') {
            coPanel.classList.remove('d-none');
            tabCO.classList.add('active');
        } else if (tab === 'top-up') {
            tuPanel.classList.remove('d-none');
            tabTU.classList.add('active');
        } else if (tab === 'ppob') {
            ppPanel.classList.remove('d-none');
            tabPP.classList.add('active');
        } else if (tab === 'customer-payments') {
            cpList.classList.remove('d-none');
            tabCP.classList.add('active');
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
        const nominal = parseFloat(document.getElementById('cashOutNominal').value) || 0;
        const fee = parseFloat(document.getElementById('cashOutFee').value) || 0;
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
            service,
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
                alert('Stock limit reached!');
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
                alert('Stock limit reached!');
            } else {
                removeFromCart(id);
                return; 
            }
            renderCart();
        }
    }

    function renderCart() {
        const cartList = document.getElementById('cartList');
        const emptyMsg = document.getElementById('emptyCartMessage');
        const btnCheckout = document.getElementById('btnCheckout');
        
        cartList.innerHTML = '';
        let total = 0;
        let count = 0;

        if (cart.length === 0) {
            emptyMsg.style.display = 'block';
            btnCheckout.disabled = true;
        } else {
            emptyMsg.style.display = 'none';
            btnCheckout.disabled = false;

            cart.forEach(item => {
                const subtotal = item.bank ? item.fee : (item.price * item.quantity);
                total += item.bank ? (item.nominal_transaksi + item.fee) : subtotal;
                count += item.quantity;

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center px-2';
                li.innerHTML = `
                    <div class="flex-grow-1">
                        <div class="fw-bold text-truncate" style="max-width: 150px;">${item.name}</div>
                        <div class="text-muted small">${
                            item.bank 
                            ? ('Nominal: Rp ' + new Intl.NumberFormat('id-ID').format(item.nominal_transaksi) + ' | Fee: Rp ' + new Intl.NumberFormat('id-ID').format(item.fee))
                            : ('Rp ' + new Intl.NumberFormat('id-ID').format(item.price) + ' x ' + item.quantity)
                        }</div>
                    </div>
                    <div class="d-flex align-items-center">
                         <div class="btn-group btn-group-sm me-2">
                            ${ (item.bank || item.customerPayment)
                                ? '<button class="btn btn-outline-secondary" disabled>1</button>' 
                                : `<button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)">-</button>
                                   <button class="btn btn-outline-secondary" disabled>${item.quantity}</button>
                                   <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, 1)">+</button>` }
                         </div>
                         <span class="fw-bold me-2">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                         <button class="btn btn-sm btn-link text-danger p-0" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                cartList.appendChild(li);
                
            });
        }

        document.getElementById('totalItems').textContent = count;
        document.getElementById('totalAmount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        calculateChange();
        updatePengurusVisibility();
    }

    document.getElementById('cashAmount').addEventListener('input', calculateChange);

    function calculateChange() {
        const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
        const method = document.getElementById('paymentMethod').value;
        let total = 0;
        cart.forEach(item => {
            total += item.bank ? (item.nominal_transaksi + item.fee) : (item.price * item.quantity);
        });
        
        const change = method === 'cash' ? (cash - total) : 0;
        const changeEl = document.getElementById('changeAmount');
        
        if (change >= 0) {
            changeEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(change);
            changeEl.classList.remove('text-danger');
            changeEl.classList.add('text-success');
        } else {
            changeEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(change);
            changeEl.classList.add('text-danger');
            changeEl.classList.remove('text-success');
        }
    }

    // Tambahkan listener change pada paymentMethod
document.getElementById('paymentMethod').addEventListener('change', function(e) {
    const cashDiv = document.getElementById('cashInputDiv');
    if (e.target.value === 'cash') {
        cashDiv.style.display = 'block';
    } else {
        cashDiv.style.display = 'none';
    }
    updatePengurusVisibility();
});

function updatePengurusVisibility() {
    const hasService = cart.some(i => i.service === true);
    const hasCustomerPayment = cart.some(i => i.customerPayment === true);
    const method = document.getElementById('paymentMethod')?.value;
    const div = document.getElementById('pengurusDiv');
    if (!div) return;
    if (hasCustomerPayment || (hasService && method === 'hutang')) {
        div.classList.remove('d-none');
    } else {
        div.classList.add('d-none');
        const sel = document.getElementById('coordinatorId');
        if (sel) sel.value = '';
    }
}
    function processTransaction() {
        if (cart.length === 0) return;

        const paymentMethod = document.getElementById('paymentMethod').value;
        const transactionCategory = cart.some(item => item.customerPayment === true) ? 'pembayaran_pelanggan' : 'penjualan_atk';
        const cashAmount = paymentMethod === 'cash' ? (parseFloat(document.getElementById('cashAmount').value) || 0) : null;
        let total = 0;
        cart.forEach(item => {
            total += item.bank ? (item.nominal_transaksi + item.fee) : (item.price * item.quantity);
        });

        if (paymentMethod === 'cash' && cashAmount < total) {
            alert('Insufficient cash amount!');
            return;
        }

        const pengurusVisible = !document.getElementById('pengurusDiv').classList.contains('d-none');
        const coordEl = document.getElementById('coordinatorId');
        const coordinatorId = (pengurusVisible && coordEl && coordEl.value) ? coordEl.value : undefined;
        if (pengurusVisible && !coordinatorId) {
            alert('Pilih pengurus untuk transaksi ini.');
            return;
        }
        const data = {
            items: cart.map(item => ({
                id: item.bank || item.customerPayment ? null : item.id,
                type: item.bank ? 'bank' : (item.service ? 'service' : (item.customerPayment ? 'customer_payment' : 'product')),
                quantity: item.quantity,
                nominal_transaksi: (item.bank || item.customerPayment) ? item.nominal_transaksi : undefined,
                fee: item.bank ? item.fee : undefined,
                customer_name: item.customerPayment ? item.customerName : undefined
            })),
            transaction_category: transactionCategory,
            payment_method: paymentMethod,
            cash_amount: cashAmount,
            coordinator_id: coordinatorId
        };

        const btn = document.getElementById('btnCheckout');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

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
                alert('Transaction successful!');
                location.reload(); // To update stock display
            } else {
                alert('Error: ' + result.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Checkout';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Checkout';
        });
    }
</script>

<style>
    .atk-pos-page .product-card:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .atk-pos-page .card {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 16px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.1);
    }
    .atk-pos-page .card-header {
        border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        background: rgba(248, 250, 252, 0.82);
    }
    .atk-pos-page .form-control,
    .atk-pos-page .form-select,
    .atk-pos-page .input-group-text {
        border-radius: 12px;
    }
    .atk-pos-page .input-group .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .atk-pos-page .input-group .input-group-text {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .atk-pos-page .cursor-pointer {
        cursor: pointer;
    }
    [data-bs-theme="dark"] .atk-pos-page .card {
        background: #111827;
        border-color: rgba(148, 163, 184, 0.22);
        box-shadow: 0 18px 38px rgba(2, 6, 23, 0.55);
    }
    [data-bs-theme="dark"] .atk-pos-page .card-header {
        background: #0f172a;
        border-color: rgba(148, 163, 184, 0.3);
    }
    [data-bs-theme="dark"] .atk-pos-page .product-card:hover {
        background-color: rgba(37, 99, 235, 0.18);
        border-color: rgba(96, 165, 250, 0.5);
    }
    [data-bs-theme="dark"] .atk-pos-page .form-control,
    [data-bs-theme="dark"] .atk-pos-page .form-select,
    [data-bs-theme="dark"] .atk-pos-page .input-group-text {
        background: #0b1220;
        color: #e2e8f0;
        border-color: rgba(148, 163, 184, 0.35);
    }
    [data-bs-theme="dark"] .atk-pos-page .text-muted,
    [data-bs-theme="dark"] .atk-pos-page .text-body-secondary {
        color: #94a3b8 !important;
    }
    @media (max-width: 991.98px) {
        .atk-pos-page {
            padding-left: 0.4rem;
            padding-right: 0.4rem;
        }
        .atk-pos-page .card {
            border-radius: 12px;
        }
        .atk-pos-page .card-header {
            padding: 0.75rem;
        }
        .atk-pos-page .btn-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.4rem;
            width: 100%;
        }
        .atk-pos-page .btn-group .btn {
            border-radius: 10px !important;
            min-height: 40px;
        }
        .atk-pos-page .product-panel-body {
            max-height: none !important;
            overflow: visible !important;
            padding: 0.75rem;
        }
        .atk-pos-page .product-card .card-body {
            padding: 0.6rem !important;
        }
        .atk-pos-page .product-card .card-title {
            font-size: 0.83rem;
        }
        .atk-pos-page #cartItems {
            max-height: 38vh !important;
        }
        .atk-pos-page .card-footer {
            padding: 0.75rem;
        }
    }
</style>
@endpush
@endsection
