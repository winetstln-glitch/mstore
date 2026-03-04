@extends('layouts.app')

@section('content')
<div class="col-12">
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
                            <button class="nav-link active filter-btn" data-filter="all" type="button">All</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link filter-btn" data-filter="car" type="button">Mobil</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link filter-btn" data-filter="motor" type="button">Motor</button>
                        </li>
                    </ul>
                    <div class="row g-3" id="services-container">
                        @foreach($services as $service)
                        <div class="col-6 col-md-6 col-lg-6 mb-3 service-item" data-type="{{ strtolower($service->vehicle_type) }}">
                            <div class="card h-100 service-card" data-fasttap
                                 data-id="{{ $service->id }}"
                                 data-name="{{ $service->name }}"
                                 data-price="{{ $service->price }}"
                                 data-vehicletype="{{ strtolower($service->vehicle_type) }}">
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
                            <label for="vehicle_brand" class="form-label">Vehicle Brand</label>
                            <select class="form-select" id="vehicle_brand" name="vehicle_brand">
                                <option value="">Select Brand</option>
                                <optgroup label="Motor">
                                    @foreach($brands['Motor'] as $brand)
                                        <option value="{{ $brand }}">{{ $brand }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Mobil">
                                    @foreach($brands['Mobil'] as $brand)
                                        <option value="{{ $brand }}">{{ $brand }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_plate" class="form-label">Vehicle Plate</label>
                            <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate">
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Customer Phone</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="customer_phone" name="customer_phone" placeholder="Enter phone number">
                                <button class="btn btn-outline-secondary" type="button" id="btnCheckCustomer">Check</button>
                            </div>
                            <small id="customerInfo" class="form-text text-muted"></small>
                        </div>
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name">
                        </div>
                        <div class="mb-3 form-check" id="voucherSection" style="display: none;">
                            <input type="checkbox" class="form-check-input" id="use_voucher" name="use_voucher">
                            <label class="form-check-label" for="use_voucher">Use Free Wash Voucher (Eligible: <span id="voucherCount">0</span>)</label>
                        </div>

                        <hr>
                        <div id="cartItems" class="mb-3" style="max-height: 360px; overflow-y: auto;">
                            <!-- Cart items will appear here -->
                            <p class="text-center text-muted" id="emptyCartMsg">No items selected</p>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="font-weight-bold">Total:</span>
                            <span class="font-weight-bold" id="totalAmount">Rp 0</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method">
                                <option value="cash">Cash</option>
                                <option value="qris">QRIS</option>
                                <option value="transfer">Transfer</option>
                                <option value="edc"> NB EDC</option>
                            </select>
                        </div>
                        <div id="cashSection">
                            <div class="mb-3">
                                <label for="cash_amount" class="form-label">Cash Amount</label>
                                <input type="number" class="form-control" id="cash_amount" name="cash_amount" oninput="calculateChange()">
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Change:</span>
                                <span id="changeAmount">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="btnCheckout" disabled>Checkout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.service-card').forEach(function (el) {
        el.addEventListener('click', function () {
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const type = this.dataset.vehicletype;
            addToCart(id, name, price, type);
        });
    });
});
</script>
@endpush

<script>
    document.getElementById('btnCheckCustomer').addEventListener('click', function() {
        const phone = document.getElementById('customer_phone').value;
        if (!phone) {
            alert('Please enter phone number');
            return;
        }

        fetch(`{{ route('wash.customer.check') }}?phone=${phone}`)
            .then(response => response.json())
            .then(data => {
                const info = document.getElementById('customerInfo');
                const voucherSection = document.getElementById('voucherSection');
                const nameInput = document.getElementById('customer_name');
                const voucherCount = document.getElementById('voucherCount');

                if (data.found) {
                    nameInput.value = data.name;
                    info.innerHTML = `<span class="text-success">Customer found! Visits: ${data.visit_count}</span>`;
                    
                    if (data.free_wash_eligibility > 0) {
                        voucherSection.style.display = 'block';
                        voucherCount.textContent = data.free_wash_eligibility;
                    } else {
                        voucherSection.style.display = 'none';
                    }
                } else {
                    info.innerHTML = '<span class="text-warning">New Customer</span>';
                    voucherSection.style.display = 'none';
                    // Don't clear name if user already typed it
                    if (nameInput.value === '') {
                        nameInput.value = ''; 
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking customer');
            });
    });

    document.getElementById('use_voucher').addEventListener('change', function() {
        updateCartUI();
    });

    let cart = [];
    const employees = @json($employees ?? []);

    function addToCart(id, name, price, type) {
        id = parseInt(id);
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            // Check if cart has items of different type
            if (cart.length > 0 && cart[0].type !== type) {
                if (!confirm('You are adding a service for a different vehicle type. Clear current cart?')) {
                    return;
                }
                resetCart();
            }
            cart.push({ id, name, price, type, quantity: 1 });
        }
        updateCartUI();
        filterBrands(type);
    }

    function filterBrands(type) {
        const brandSelect = document.getElementById('vehicle_brand');
        const options = brandSelect.options;
        let typeLower = type.toLowerCase();
        
        // Map database types to optgroup labels
        if (typeLower === 'car') {
            typeLower = 'mobil';
        } else if (typeLower === 'motor') {
            typeLower = 'motor';
        }

        for (let i = 0; i < options.length; i++) {
            const opt = options[i];
            const parent = opt.parentElement;
            if (parent.tagName === 'OPTGROUP') {
                if (typeLower === 'all' || parent.label.toLowerCase() === typeLower) {
                    parent.style.display = '';
                } else {
                    parent.style.display = 'none';
                }
            }
        }
        // Select the first valid option or reset
        brandSelect.value = "";
    }

    function removeFromCart(id) {
        id = parseInt(id);
        const index = cart.findIndex(item => item.id === id);
        if (index > -1) {
            cart.splice(index, 1);
        }
        updateCartUI();
        if (cart.length === 0) {
            resetServiceSelection();
        }
    }

    function resetServiceSelection() {
        // Only reset filters and service visibility, NOT customer data
        
        // Reset filters by clicking All button to trigger all UI logic
        const allBtn = document.querySelector('.filter-btn[data-filter="all"]');
        if (allBtn) {
            allBtn.click();
        }
        
        // Reset brand options visibility
        const brandSelect = document.getElementById('vehicle_brand');
        const options = brandSelect.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].parentElement.tagName === 'OPTGROUP') {
                options[i].parentElement.style.display = '';
            }
        }
    }

    function resetCart() {
        cart = [];
        document.getElementById('vehicle_brand').value = "";
        document.getElementById('vehicle_plate').value = "";
        document.getElementById('customer_name').value = "";
        document.getElementById('customer_phone').value = "";
        document.getElementById('cash_amount').value = "";
        document.getElementById('changeAmount').textContent = "Rp 0";
        document.getElementById('customerInfo').innerHTML = "";
        document.getElementById('voucherSection').style.display = 'none';
        document.getElementById('use_voucher').checked = false;
        
        resetServiceSelection();

        updateCartUI();
    }

    function updateCartUI() {
        const cartContainer = document.getElementById('cartItems');
        const emptyMsg = document.getElementById('emptyCartMsg');
        const totalEl = document.getElementById('totalAmount');
        const btnCheckout = document.getElementById('btnCheckout');
        const voucherEl = document.getElementById('use_voucher');
        const useVoucher = voucherEl ? !!voucherEl.checked : false;
        
        cartContainer.innerHTML = '';
        let total = 0;
        let discount = 0;

        if (cart.length === 0) {
            if (emptyMsg) emptyMsg.style.display = 'block';
            if (btnCheckout) btnCheckout.disabled = true;
        } else {
            if (emptyMsg) emptyMsg.style.display = 'none';
            if (btnCheckout) btnCheckout.disabled = false;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center mb-2 border-bottom pb-2';
                const selectId = 'emp_sel_' + item.id;
                const empOptions = ['<option value=\"\">- Pegawai -</option>']
                    .concat(employees.map(e => `<option value=\"${e.id}\" ${item.employee_id==e.id?'selected':''}>${e.name}</option>`))
                    .join('');
                div.innerHTML = `
                    <div class="me-2">
                        <div class="fw-semibold">${item.name}</div>
                        <small class="text-muted d-block">${item.quantity} x ${item.price.toLocaleString('id-ID')}</small>
                        <div class="mt-1">
                            <select id="${selectId}" class="form-select form-select-sm">
                                ${empOptions}
                            </select>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-2">Rp ${itemTotal.toLocaleString('id-ID')}</span>
                         <button class="btn btn-sm btn-link text-danger p-0" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                setTimeout(() => {
                    const sel = document.getElementById(selectId);
                    if (sel) {
                        sel.addEventListener('change', function() {
                            item.employee_id = this.value ? parseInt(this.value) : null;
                        });
                    }
                }, 0);
                cartContainer.appendChild(div);
            });

            if (useVoucher && cart.length > 0) {
                discount = cart[0].price;
            }
        }

        const finalTotal = Math.max(0, total - discount);
        
        if (discount > 0) {
             totalEl.innerHTML = `<small class="text-decoration-line-through text-muted">Rp ${total.toLocaleString('id-ID')}</small> <span class="text-success fw-bold">Rp ${finalTotal.toLocaleString('id-ID')}</span>`;
        } else {
             totalEl.textContent = 'Rp ' + finalTotal.toLocaleString('id-ID');
        }
        
        if (totalEl) totalEl.dataset.amount = finalTotal; // Store numeric value
        calculateChange();
    }

    function calculateChange() {
        const totalEl = document.getElementById('totalAmount');
        const total = parseInt(totalEl?.dataset?.amount || 0) || 0;
        const method = document.getElementById('payment_method')?.value || 'cash';
        const cashInput = document.getElementById('cash_amount')?.value || 0;
        const cash = parseInt(cashInput) || 0;
        const changeEl = document.getElementById('changeAmount');
        const cashSection = document.getElementById('cashSection');

        if (cashSection) cashSection.style.display = (method === 'cash') ? '' : 'none';

        if (method === 'cash' && cash >= total) {
            if (changeEl) changeEl.textContent = 'Rp ' + (cash - total).toLocaleString('id-ID');
        } else {
            if (changeEl) changeEl.textContent = 'Rp 0';
        }
    }

    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function () {
            // Remove active class from all
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked
            this.classList.add('active');
            
            const type = this.getAttribute('data-filter');
            
            // Update brand filter if cart is empty
            if (cart.length === 0) {
                filterBrands(type);
            }

            document.querySelectorAll('.service-item').forEach(item => {
                if (type === 'all' || item.dataset.type === type) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Toggle cash section on payment method change
    const paymentMethodEl = document.getElementById('payment_method');
    if (paymentMethodEl) {
        paymentMethodEl.addEventListener('change', calculateChange);
        // Initialize visibility
        calculateChange();
    }
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (cart.length === 0) return;

        const totalText = document.getElementById('totalAmount').textContent.replace('Rp ', '').replace(/\./g, '');
        const total = parseInt(totalText) || 0;
        const method = document.getElementById('payment_method')?.value || 'cash';
        const cashInput = document.getElementById('cash_amount')?.value || 0;
        const cash = parseInt(cashInput) || 0;

        /* if (cash < total) {
             alert('Insufficient cash!');
             return;
        } */ // Allow credit/later payment if needed, or enforce. Let's enforce for POS.
        
        const data = {
            items: cart,
            payment_method: method,
            cash_amount: method === 'cash' ? cash : null,
            customer_name: document.getElementById('customer_name').value,
            customer_phone: document.getElementById('customer_phone').value,
            use_voucher: document.getElementById('use_voucher').checked,
            vehicle_plate: document.getElementById('vehicle_plate').value,
            vehicle_brand: document.getElementById('vehicle_brand').value
        };
        
        const btn = document.getElementById('btnCheckout');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        fetch('{{ route("wash.transactions.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const url = data.receipt_url ? data.receipt_url : ('{{ url("wash/transactions") }}/' + data.transaction_id + '/receipt');
                window.open(url, '_blank', 'width=400,height=600');
                const phone = data.customer_phone || document.getElementById('customer_phone').value;
                if (phone && !data.wa_sent) {
                    fetch(`{{ url('wash/transactions') }}/${data.transaction_id}/whatsapp-receipt`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: `phone=${encodeURIComponent(phone)}`
                    }).catch(()=>{});
                }
                resetCart();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
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
