@extends('layouts.app')

@section('title', __('ATK POS'))

@section('content')
<div class="container-fluid">
    <div class="row h-100">
        <!-- Product & Service List -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
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
                                 data-stock="{{ $product->stock }}">
                                <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                    <div class="mb-2 d-flex align-items-center justify-content-center rounded bg-light" style="height: 100px; overflow: hidden;">
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: cover;" alt="{{ $product->name }}">
                                        @else
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        @endif
                                    </div>
                                    <h6 class="card-title mb-1 text-truncate" title="{{ $product->name }}">{{ $product->name }}</h6>
                                    <p class="card-text text-primary fw-bold mb-0">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                    <small class="text-muted">Stock: {{ $product->stock }}</small>
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
                                 data-stock="999999">
                                <div class="card-body text-center p-2 d-flex flex-column justify-content-center">
                                    <div class="mb-2 d-flex align-items-center justify-content-center rounded bg-light" style="height: 100px; overflow: hidden;">
                                        @if($service->image)
                                            <img src="{{ Storage::url($service->image) }}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: cover;" alt="{{ $service->name }}">
                                        @else
                                            <i class="fas fa-print fa-2x text-muted"></i>
                                        @endif
                                    </div>
                                    <h6 class="card-title mb-1 text-truncate" title="{{ $service->name }}">{{ $service->name }}</h6>
                                    <p class="card-text text-primary fw-bold mb-0">Rp {{ number_format($service->price, 0, ',', '.') }}</p>
                                    <small class="text-muted">Jasa</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if(empty($services) || count($services)===0)
                            <div class="text-center py-5">
                                <p class="text-muted">Belum ada jasa. <a href="{{ route('atk.products.create') }}">Tambah Jasa</a></p>
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
                    <div class="text-center py-5 text-muted" id="emptyCartMessage">
                        <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                        <p>Cart is empty</p>
                    </div>
                    <ul class="list-group list-group-flush" id="cartList"></ul>
                </div>
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Items:</span>
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
                         </select>
                    </div>
                    
                    <div class="mb-3" id="cashInputDiv">
                        <label class="form-label">Cash Received</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="cashAmount" placeholder="0">
                        </div>
                        <div class="mt-1 d-flex justify-content-between text-muted small">
                             <span>Change:</span>
                             <span id="changeAmount" class="fw-bold">Rp 0</span>
                        </div>
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
<script>
    let cart = [];
    
    // Attach click handlers to product/service cards
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.product-card[data-id]').forEach(function (el) {
            el.addEventListener('click', function () {
                const id = parseInt(this.dataset.id);
                const name = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                const maxStock = parseInt(this.dataset.stock);
                addToCart(id, name, price, maxStock);
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

    function addToCart(id, name, price, maxStock) {
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            if (existingItem.quantity < maxStock) {
                existingItem.quantity++;
            } else {
                alert('Stock limit reached!');
            }
        } else {
            cart.push({ id, name, price, quantity: 1, maxStock });
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
    }

    document.getElementById('cashAmount').addEventListener('input', calculateChange);

    function calculateChange() {
        const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
        let total = 0;
        cart.forEach(item => total += item.price * item.quantity);
        
        const change = cash - total;
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

    function processTransaction() {
        if (cart.length === 0) return;

        const paymentMethod = document.getElementById('paymentMethod').value;
        const cashAmount = parseFloat(document.getElementById('cashAmount').value) || 0;
        let total = 0;
        cart.forEach(item => {
            total += item.bank ? (item.nominal_transaksi + item.fee) : (item.price * item.quantity);
        });

        if (paymentMethod === 'cash' && cashAmount < total) {
            alert('Insufficient cash amount!');
            return;
        }

        const data = {
            items: cart.map(item => ({ id: item.id, quantity: item.quantity, nominal_transaksi: item.bank ? item.nominal_transaksi : undefined, fee: item.bank ? item.fee : undefined })),
            payment_method: paymentMethod,
            cash_amount: cashAmount
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
        .then(result => {
            if (result.success) {
                // Open Receipt
                window.open('{{ url("atk/transactions") }}/' + result.transaction_id + '/receipt', '_blank', 'width=400,height=600');
                
                // Reset Cart
                cart = [];
                document.getElementById('cashAmount').value = '';
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
