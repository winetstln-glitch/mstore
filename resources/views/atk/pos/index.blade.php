@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Product List -->
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Katalog Produk</h6>
                </div>
                <div class="card-body">
                    <input type="text" id="searchProduct" class="form-control mb-3" placeholder="Cari produk...">
                    
                    <div class="row" id="productList" style="max-height: 600px; overflow-y: auto;">
                        @foreach($products as $product)
                        <div class="col-md-4 mb-3 product-item" data-name="{{ strtolower($product->name) }}" data-code="{{ strtolower($product->code) }}">
                            <div class="card h-100 cursor-pointer" onclick="addToCart({{ $product }})">
                                <div class="card-body text-center">
                                    <h6 class="font-weight-bold">{{ $product->name }}</h6>
                                    <p class="small text-muted mb-1">{{ $product->code }}</p>
                                    <div class="badge bg-success">Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</div>
                                    <div class="small mt-2">Stok: {{ $product->stock }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart -->
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Keranjang Belanja</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Nama Pelanggan</label>
                        <input type="text" id="customerName" class="form-control" placeholder="Umum">
                    </div>
                    <div class="mb-3">
                        <label>Tipe Harga</label>
                        <select id="priceType" class="form-select" onchange="updateCartPrices()">
                            <option value="retail">Eceran</option>
                            <option value="wholesale">Grosir</option>
                        </select>
                    </div>

                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <!-- Cart items go here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th id="cartTotal">Rp 0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mb-3">
                        <label>Metode Pembayaran</label>
                        <select id="paymentMethod" class="form-select">
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Bayar</label>
                        <input type="number" id="amountPaid" class="form-control" placeholder="0">
                    </div>
                    
                    <div class="mb-3">
                        <label>Kembalian: <span id="changeAmount" class="fw-bold">Rp 0</span></label>
                    </div>

                    <button class="btn btn-primary w-100" onclick="checkout()">Proses Transaksi</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let cart = [];
    
    // Search Filter
    $('#searchProduct').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('.product-item').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1 || $(this).data('code').indexOf(value) > -1)
        });
    });

    function addToCart(product) {
        let existing = cart.find(item => item.id === product.id);
        if (existing) {
            if (existing.quantity < product.stock) {
                existing.quantity++;
            } else {
                alert('Stok tidak mencukupi!');
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
            } else {
                alert('Stok habis!');
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

        cart.forEach((item, index) => {
            let price = (priceType === 'wholesale') ? item.wholesale_price : item.retail_price;
            let subtotal = price * item.quantity;
            total += subtotal;

            tbody.append(`
                <tr>
                    <td>${item.name}</td>
                    <td>
                        <button class="btn btn-xs btn-outline-secondary" onclick="updateQuantity(${index}, -1)">-</button>
                        ${item.quantity}
                        <button class="btn btn-xs btn-outline-secondary" onclick="updateQuantity(${index}, 1)">+</button>
                    </td>
                    <td>${new Intl.NumberFormat('id-ID').format(price)}</td>
                    <td>${new Intl.NumberFormat('id-ID').format(subtotal)}</td>
                    <td><button class="btn btn-xs btn-danger" onclick="removeFromCart(${index})">x</button></td>
                </tr>
            `);
        });

        $('#cartTotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        calculateChange(total);
    }

    function updateCartPrices() {
        renderCart();
    }
    
    $('#amountPaid').on('input', function() {
        let totalText = $('#cartTotal').text().replace('Rp ', '').replace(/\./g, '');
        calculateChange(parseFloat(totalText) || 0);
    });

    function calculateChange(total) {
        let paid = parseFloat($('#amountPaid').val()) || 0;
        let change = paid - total;
        $('#changeAmount').text('Rp ' + new Intl.NumberFormat('id-ID').format(change < 0 ? 0 : change));
    }

    function checkout() {
        if (cart.length === 0) {
            alert('Keranjang kosong!');
            return;
        }

        let priceType = $('#priceType').val();
        let items = cart.map(item => ({
            id: item.id,
            quantity: item.quantity,
            price_type: priceType
        }));

        $.ajax({
            url: "{{ route('atk.pos.store') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                items: items,
                customer_name: $('#customerName').val(),
                payment_method: $('#paymentMethod').val(),
                amount_paid: $('#amountPaid').val(),
                price_type: priceType
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Transaksi Berhasil!',
                        text: 'Mencetak struk...',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Open receipt in new window/tab
                        window.open(response.redirect, '_blank', 'width=400,height=600');
                        
                        // Reload page to reset cart
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
            }
        });
    }
</script>
@endpush
@endsection
