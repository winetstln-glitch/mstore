@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Service Selection -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Select Service') }}</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-all-tab" data-bs-toggle="pill" data-bs-target="#pills-all" type="button" role="tab">All</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-car-tab" data-bs-toggle="pill" data-bs-target="#pills-car" type="button" role="tab">Mobil</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-motor-tab" data-bs-toggle="pill" data-bs-target="#pills-motor" type="button" role="tab">Motor</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-all" role="tabpanel">
                            <div class="row">
                                @foreach($services as $service)
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 service-card" onclick="addToCart({{ $service->id }}, '{{ $service->name }}', {{ $service->price }})">
                                        <div class="card-body text-center">
                                            <div class="mb-2 d-flex align-items-center justify-content-center rounded bg-light" style="height: 100px; overflow: hidden;">
                                                @if($service->image)
                                                    <img src="{{ Storage::url($service->image) }}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: cover;" alt="{{ $service->name }}">
                                                @else
                                                    <i class="fas fa-image fa-2x text-muted"></i>
                                                @endif
                                            </div>
                                            <h5 class="card-title">{{ $service->name }}</h5>
                                            <p class="card-text text-primary font-weight-bold">Rp {{ number_format($service->price, 0, ',', '.') }}</p>
                                            <small class="text-muted">{{ ucfirst($service->vehicle_type) }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <!-- Add filtered tabs logic via JS or server-side if needed, for now just show all in All tab -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart/Checkout -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Current Transaction') }}</h6>
                </div>
                <div class="card-body">
                    <form id="checkoutForm">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name">
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_plate" class="form-label">Vehicle Plate</label>
                            <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate">
                        </div>

                        <hr>
                        <div id="cartItems" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                            <!-- Cart items will appear here -->
                            <p class="text-center text-muted" id="emptyCartMsg">No items selected</p>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-bold">Total:</span>
                            <span class="font-weight-bold" id="totalAmount">Rp 0</span>
                        </div>

                        <div class="mb-3">
                            <label for="cash_amount" class="form-label">Cash Amount</label>
                            <input type="number" class="form-control" id="cash_amount" name="cash_amount" oninput="calculateChange()">
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Change:</span>
                            <span id="changeAmount">Rp 0</span>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="btnCheckout" disabled>Checkout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];

    function addToCart(id, name, price) {
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({ id, name, price, quantity: 1 });
        }
        updateCartUI();
    }

    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        updateCartUI();
    }

    function updateCartUI() {
        const cartContainer = document.getElementById('cartItems');
        const emptyMsg = document.getElementById('emptyCartMsg');
        const totalEl = document.getElementById('totalAmount');
        const btnCheckout = document.getElementById('btnCheckout');
        
        cartContainer.innerHTML = '';
        let total = 0;

        if (cart.length === 0) {
            emptyMsg.style.display = 'block';
            btnCheckout.disabled = true;
        } else {
            emptyMsg.style.display = 'none';
            btnCheckout.disabled = false;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center mb-2 border-bottom pb-2';
                div.innerHTML = `
                    <div>
                        <div>${item.name}</div>
                        <small class="text-muted">${item.quantity} x ${item.price.toLocaleString('id-ID')}</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-2">${itemTotal.toLocaleString('id-ID')}</span>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">&times;</button>
                    </div>
                `;
                cartContainer.appendChild(div);
            });
        }

        totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        calculateChange();
    }

    function calculateChange() {
        const totalText = document.getElementById('totalAmount').textContent.replace('Rp ', '').replace(/\./g, '');
        const total = parseInt(totalText) || 0;
        const cashInput = document.getElementById('cash_amount').value;
        const cash = parseInt(cashInput) || 0;
        const changeEl = document.getElementById('changeAmount');

        if (cash >= total) {
            changeEl.textContent = 'Rp ' + (cash - total).toLocaleString('id-ID');
        } else {
            changeEl.textContent = 'Rp 0';
        }
    }

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (cart.length === 0) return;

        const totalText = document.getElementById('totalAmount').textContent.replace('Rp ', '').replace(/\./g, '');
        const total = parseInt(totalText) || 0;
        const cashInput = document.getElementById('cash_amount').value;
        const cash = parseInt(cashInput) || 0;

        /* if (cash < total) {
             alert('Insufficient cash!');
             return;
        } */ // Allow credit/later payment if needed, or enforce. Let's enforce for POS.
        
        const data = {
            items: cart,
            payment_method: 'cash',
            cash_amount: cash,
            customer_name: document.getElementById('customer_name').value,
            vehicle_plate: document.getElementById('vehicle_plate').value,
            _token: '{{ csrf_token() }}'
        };

        fetch('{{ route("wash.transactions.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Transaction successful!');
                cart = [];
                document.getElementById('checkoutForm').reset();
                updateCartUI();
                // Optionally redirect or print receipt
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred.');
        });
    });
</script>

<style>
    .service-card {
        cursor: pointer;
        transition: transform 0.2s;
    }
    .service-card:hover {
        transform: scale(1.05);
        border-color: #4e73df;
    }
</style>
@endsection
