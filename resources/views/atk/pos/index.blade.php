@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 py-3 pb-3">
    <!-- Header with Shortcuts Info -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0 text-gray-800"><i class="fas fa-cash-register me-2"></i>Kasir Toko (ATK)</h1>
        <div class="d-none d-md-block text-xs">
            <span class="badge bg-secondary me-1">[F2] Cari</span>
            <span class="badge bg-secondary me-1">[F4] Ubah Qty</span>
            <span class="badge bg-secondary me-1">[F8] Bayar</span>
            <span class="badge bg-secondary me-1">[F9] Cetak Terakhir</span>
            <span class="badge bg-secondary">[F10] Refresh</span>
        </div>
    </div>

    <div class="row g-2">
        <!-- Left Column: Product List -->
        <div class="col-md-8">
            <div class="card shadow h-100 border-0">
                <div class="card-header py-2 bg-white border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="fas fa-barcode"></i></span>
                        <input type="text" id="searchProduct" class="form-control form-control-lg" placeholder="Scan Barcode / Cari Nama Produk (F2)" autofocus autocomplete="off">
                    </div>
                </div>
                <div class="card-body p-2 bg-light">
                    <!-- Product Grid -->
                    <div class="row g-2" id="productList" style="max-height: 70vh; overflow-y: auto;">
                        @forelse($products as $product)
                        <div class="col-6 col-md-3 col-lg-3 product-item" 
                             data-name="{{ strtolower($product->name) }}" 
                             data-code="{{ strtolower($product->code) }}"
                             onclick="addToCart({{ $product }})">
                            <div class="card h-100 border-0 shadow-sm service-card position-relative overflow-hidden">
                                <div class="position-absolute top-0 end-0 p-1">
                                    <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $product->stock }}
                                    </span>
                                </div>
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 120px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-white d-flex align-items-center justify-content-center" style="height: 120px;">
                                        <i class="fas fa-box fa-3x text-gray-200"></i>
                                    </div>
                                @endif
                                <div class="card-body p-2 text-center">
                                    <h6 class="card-title text-truncate small mb-1 fw-bold" title="{{ $product->name }}">{{ $product->name }}</h6>
                                    <div class="text-primary fw-bold small">Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</div>
                                    <small class="text-muted d-block text-xs">{{ $product->code }}</small>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center mt-5">
                            <p class="text-muted">Tidak ada produk ditemukan.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Cart & Checkout -->
        <div class="col-md-4">
            <div class="card shadow h-100 border-0 d-flex flex-column">
                <!-- Total Display (Big) -->
                <div class="card-header bg-primary text-white p-3 text-end">
                    <small class="d-block opacity-75">Total Belanja</small>
                    <h2 class="m-0 fw-bold" id="bigTotal">Rp 0</h2>
                </div>
                
                <!-- Cart Items List -->
                <div class="card-body p-0 flex-grow-1 bg-white position-relative" style="overflow-y: auto; max-height: 40vh;">
                    <table class="table table-sm table-striped mb-0 table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Produk</th>
                                <th class="text-center" width="25%">Qty</th>
                                <th class="text-end pe-3" width="30%">Subtotal</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <!-- JS Rendered -->
                        </tbody>
                    </table>
                    <div id="emptyCartMessage" class="text-center mt-5 text-muted">
                        <i class="fas fa-shopping-basket fa-3x mb-3 opacity-50"></i>
                        <p>Keranjang kosong</p>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="card-footer bg-light p-3 border-top">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <select id="priceType" class="form-select form-select-sm" onchange="updateCartPrices()">
                                <option value="retail">Eceran</option>
                                <option value="wholesale">Grosir</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <select id="employeeId" class="form-select form-select-sm">
                                <option value="">- Kasir -</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ auth()->id() == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <input type="text" id="customerName" class="form-control form-control-sm" placeholder="Nama Pelanggan (Opsional)">
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small text-muted">Metode</label>
                            <select id="paymentMethod" class="form-select form-select-sm">
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted">Bayar (Rp)</label>
                            <input type="number" id="amountPaid" class="form-control form-control-sm fw-bold text-end" placeholder="0">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                        <span class="text-muted small">Kembalian:</span>
                        <span class="fw-bold text-success h5 mb-0" id="changeAmount">Rp 0</span>
                    </div>

                    <button class="btn btn-primary w-100 py-2 fw-bold shadow-sm" onclick="checkout()" id="btnPay">
                        <i class="fas fa-save me-2"></i> PROSES BAYAR [F8]
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let cart = [];
    const products = @json($products); // All products for client-side search if needed
    
    // --- Keyboard Shortcuts ---
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            $('#searchProduct').focus();
        } else if (e.key === 'F8') {
            e.preventDefault();
            checkout();
        } else if (e.key === 'F10') {
            e.preventDefault();
            location.reload();
        }
    });

    // --- Search & Barcode Logic ---
    $('#searchProduct').on('keyup', function(e) {
        let value = $(this).val().toLowerCase();
        
        // Barcode Scanner usually sends 'Enter' after input
        if (e.key === 'Enter') {
            // Find exact match first
            let exactMatch = null;
            $('.product-item').each(function() {
                if ($(this).data('code') == value) {
                    exactMatch = $(this);
                    return false; // break
                }
            });

            if (exactMatch) {
                exactMatch.click(); // Trigger click to add
                $(this).val(''); // Clear input
                $(this).focus();
                // Reset filter
                $('.product-item').show();
                return;
            }
        }

        // Filter Grid
        $('.product-item').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1 || $(this).data('code').indexOf(value) > -1)
        });
    });

    // --- Cart Logic ---
    function addToCart(product) {
        // Find if product is already in cart
        let existing = cart.find(item => item.id === product.id);
        
        if (existing) {
            if (existing.quantity < product.stock) {
                existing.quantity++;
                playSound('beep');
            } else {
                playSound('error');
                Swal.fire({
                    icon: 'warning',
                    title: 'Stok Habis',
                    text: 'Stok produk tidak mencukupi',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
        } else {
            if (product.stock > 0) {
                cart.push({
                    id: product.id,
                    name: product.name,
                    retail_price: parseFloat(product.sell_price_retail),
                    wholesale_price: parseFloat(product.sell_price_wholesale),
                    quantity: 1,
                    stock: product.stock
                });
                playSound('beep');
            } else {
                playSound('error');
                Swal.fire({
                    icon: 'error',
                    title: 'Stok Kosong',
                    text: 'Produk ini tidak tersedia',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
        }
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function updateQuantity(index, delta) {
        let item = cart[index];
        let newQty = item.quantity + delta;
        if (newQty > 0 && newQty <= item.stock) {
            item.quantity = newQty;
            renderCart();
        }
    }

    function renderCart() {
        let tbody = $('#cartBody');
        tbody.empty();
        let total = 0;
        let priceType = $('#priceType').val();

        if (cart.length === 0) {
            $('#emptyCartMessage').show();
        } else {
            $('#emptyCartMessage').hide();
        }

        cart.forEach((item, index) => {
            let price = (priceType === 'wholesale') ? item.wholesale_price : item.retail_price;
            let subtotal = price * item.quantity;
            total += subtotal;

            tbody.append(`
                <tr>
                    <td class="ps-3 align-middle">
                        <div class="fw-bold text-truncate" style="max-width: 150px;">${item.name}</div>
                        <div class="small text-muted">@ ${new Intl.NumberFormat('id-ID').format(price)}</div>
                    </td>
                    <td class="text-center align-middle">
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary px-1" onclick="updateQuantity(${index}, -1)">-</button>
                            <input type="text" class="form-control text-center px-1" value="${item.quantity}" readonly style="max-width: 40px;">
                            <button class="btn btn-outline-secondary px-1" onclick="updateQuantity(${index}, 1)">+</button>
                        </div>
                    </td>
                    <td class="text-end pe-3 align-middle fw-bold">
                        ${new Intl.NumberFormat('id-ID').format(subtotal)}
                    </td>
                    <td class="align-middle">
                        <button class="btn btn-link text-danger p-0" onclick="removeFromCart(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        let formattedTotal = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        $('#bigTotal').text(formattedTotal);
        calculateChange(total);
    }

    function updateCartPrices() {
        renderCart();
    }
    
    $('#amountPaid').on('input', function() {
        // Re-calculate change based on current total
        // We need to re-sum because 'total' variable is local to renderCart
        // But we can parse bigTotal text
        let totalText = $('#bigTotal').text().replace('Rp ', '').replace(/\./g, '');
        let total = parseFloat(totalText) || 0;
        calculateChange(total);
    });

    function calculateChange(total) {
        let paid = parseFloat($('#amountPaid').val()) || 0;
        let change = paid - total;
        $('#changeAmount').text('Rp ' + new Intl.NumberFormat('id-ID').format(change < 0 ? 0 : change));
        
        if (change < 0 && paid > 0) {
            $('#changeAmount').removeClass('text-success').addClass('text-danger');
        } else {
            $('#changeAmount').removeClass('text-danger').addClass('text-success');
        }
    }

    function checkout() {
        if (cart.length === 0) {
            Swal.fire('Keranjang Kosong', 'Silakan pilih produk terlebih dahulu', 'warning');
            return;
        }

        let priceType = $('#priceType').val();
        let items = cart.map(item => ({
            id: item.id,
            quantity: item.quantity,
            price_type: priceType
        }));

        // Validate Payment
        let totalText = $('#bigTotal').text().replace('Rp ', '').replace(/\./g, '');
        let total = parseFloat(totalText) || 0;
        let paid = parseFloat($('#amountPaid').val()) || 0;
        
        if (paid < total) {
            Swal.fire({
                title: 'Pembayaran Kurang',
                text: 'Jumlah pembayaran kurang dari total belanja',
                icon: 'warning',
                timer: 2000
            });
            $('#amountPaid').focus();
            return;
        }

        // Show Loading
        let btn = $('#btnPay');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Memproses...');

        $.ajax({
            url: "{{ route('atk.pos.store') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                items: items,
                customer_name: $('#customerName').val(),
                payment_method: $('#paymentMethod').val(),
                amount_paid: paid,
                price_type: priceType,
                employee_id: $('#employeeId').val()
            },
            success: function(response) {
                if (response.success) {
                    playSound('success');
                    Swal.fire({
                        title: 'Transaksi Berhasil!',
                        text: 'Kembalian: ' + $('#changeAmount').text(),
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.open(response.redirect, '_blank', 'width=400,height=600');
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                playSound('error');
                btn.prop('disabled', false).html(originalText);
                Swal.fire('Gagal', xhr.responseJSON.message || 'Terjadi kesalahan sistem', 'error');
            }
        });
    }

    // Audio Context for Beep
    function playSound(type) {
        // Simple beep implementation or use Audio object if files exist
        // For now, silent or console log
        console.log('Sound:', type);
    }
</script>
@endpush
@endsection