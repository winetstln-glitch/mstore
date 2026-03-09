@extends('layouts.app')

@section('title', __('ATK POS'))

@section('content')
<div class="container-fluid">
    <div class="row h-100">
        <!-- Product & Service List -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3">
                    <div class="input-group">
                        <span class="input-group-text  border-end-0"><i class="fas fa-search text-body-secondary"></i></span>
                        <input type="text" id="productSearch" class="form-control border-start-0 ps-0" placeholder="Search products by name or code...">
                    </div>
                    <div class="mt-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" id="tabProducts" onclick="switchTab('products')">
                                <i class="fa-solid fa-box"></i> Produk
                            </button>
                            <button class="btn btn-outline-secondary" id="tabServices" onclick="switchTab('services')">
                                <i class="fa-solid fa-print"></i> Jasa Potocopy
                            </button>
                            <button class="btn btn-outline-success" id="tabBank" onclick="switchTab('bank')">
                                <i class="fa-solid fa-building-columns"></i> Agen Bank
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body overflow-auto" style="max-height: 75vh;">
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
                    @if($products->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted">No products available. <a href="{{ route('atk.products.create') }}">Add Product</a></p>
                        </div>
                    @endif
                        </div>
                    </div>
                </div>

                <!-- Cart -->
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 d-flex flex-column">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Current Order</h5>
                        </div>
                        <div class="card-body p-0 overflow-auto flex-grow-1" id="cartItems" style="max-height: 50vh;">
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
                                 <label class="form-label">Payment Method</label>
                                 <select class="form-select" id="paymentMethod">
                                     <option value="cash">Cash</option>
                                     <option value="transfer">Transfer</option>
                                     <option value="qris">QRIS</option>
                                     <option value="hutang">Hutang</option>
                                 </select>
                            </div>
                            
                            <div class="mb-3 d-none" id="pengurusDiv">
                                <label class="form-label">Coordinator / Pengurus / Investor</label>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <select class="form-select" id="coordinatorId">
                                            <option value="">Pilih Pengurus (Coordinator)</option>
                                            @foreach(($coordinators ?? []) as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <select class="form-select" id="investorId">
                                            <option value="">Pilih Investor (opsional)</option>
                                            @foreach(($investors ?? []) as $inv)
                                                <option value="{{ $inv->id }}" data-coordinator="{{ $inv->coordinator_id }}">{{ $inv->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <small class="text-body-secondary">Pengurus wajib saat hutang jasa potocopy; investor opsional.</small>
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

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    let cart = [];

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
        const activeList = document.getElementById('serviceList').classList.contains('d-none') ? '.product-item' : '.service-item';
        document.querySelectorAll(activeList).forEach(item => {
            const name = item.dataset.name;
            const code = item.dataset.code;
            item.style.display = (name.includes(search) || code.includes(search)) ? 'block' : 'none';
        });
    });

    function switchTab(tab) {
        const pList = document.getElementById('productList');
        const sList = document.getElementById('serviceList');
        const bPanel = document.getElementById('bankPanel');
        const tabP = document.getElementById('tabProducts');
        const tabS = document.getElementById('tabServices');
        const tabB = document.getElementById('tabBank');
        if (tab === 'products') {
            pList.classList.remove('d-none');
            sList.classList.add('d-none');
            bPanel.classList.add('d-none');
            tabP.classList.add('active');
            tabS.classList.remove('active');
            tabB.classList.remove('active');
        } else {
            if (tab === 'services') {
                pList.classList.add('d-none');
                sList.classList.remove('d-none');
                bPanel.classList.add('d-none');
                tabS.classList.add('active');
                tabP.classList.remove('active');
                tabB.classList.remove('active');
            } else {
                pList.classList.add('d-none');
                sList.classList.add('d-none');
                bPanel.classList.remove('d-none');
                tabB.classList.add('active');
                tabP.classList.remove('active');
                tabS.classList.remove('active');
            }
        }
        document.getElementById('productSearch').dispatchEvent(new Event('input'));
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
            cart.push({ id, name, price, quantity: 1, maxStock, service: isService });
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
                            ${ item.bank 
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
    const div = document.getElementById('pengurusDiv');
    if (!div) return;
    if (hasService) {
        div.classList.remove('d-none');
        filterInvestorsByCoordinator();
    } else {
        div.classList.add('d-none');
        const sel = document.getElementById('coordinatorId');
        if (sel) sel.value = '';
        const invSel = document.getElementById('investorId');
        if (invSel) invSel.value = '';
    }
}

// Filter investor option berdasarkan coordinator terpilih
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'coordinatorId') {
        filterInvestorsByCoordinator();
    }
});

function filterInvestorsByCoordinator() {
    const coordId = document.getElementById('coordinatorId')?.value || '';
    const investorSel = document.getElementById('investorId');
    if (!investorSel) return;
    Array.from(investorSel.options).forEach(opt => {
        if (opt.value === '') { 
            opt.hidden = false; 
            return; 
        }
        const c = opt.getAttribute('data-coordinator') || '';
        opt.hidden = !!coordId && c !== coordId;
    });
    // Reset selection jika tidak sesuai filter
    if (investorSel.selectedOptions.length) {
        const selOpt = investorSel.selectedOptions[0];
        if (selOpt.hidden) {
            investorSel.value = '';
        }
    }
}
    function processTransaction() {
        if (cart.length === 0) return;

        const paymentMethod = document.getElementById('paymentMethod').value;
        const cashAmount = paymentMethod === 'cash' ? (parseFloat(document.getElementById('cashAmount').value) || 0) : null;
        let total = 0;
        cart.forEach(item => {
            total += item.bank ? (item.nominal_transaksi + item.fee) : (item.price * item.quantity);
        });

        if (paymentMethod === 'cash' && cashAmount < total) {
            alert('Insufficient cash amount!');
            return;
        }

        const coordEl = document.getElementById('coordinatorId');
        const coordinatorId = (!document.getElementById('pengurusDiv').classList.contains('d-none') && coordEl && coordEl.value) ? coordEl.value : undefined;
        const invEl = document.getElementById('investorId');
        const investorId = (!document.getElementById('pengurusDiv').classList.contains('d-none') && invEl && invEl.value) ? invEl.value : undefined;
        const data = {
            items: cart.map(item => ({ id: item.id, quantity: item.quantity, nominal_transaksi: item.bank ? item.nominal_transaksi : undefined, fee: item.bank ? item.fee : undefined })),
            payment_method: paymentMethod,
            cash_amount: cashAmount,
            coordinator_id: coordinatorId,
            investor_id: investorId
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
    .product-card:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endpush
@endsection
