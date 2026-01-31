@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Service Selection -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Pilih Layanan</h6>
                    <a href="{{ route('wash.index') }}" class="btn btn-sm btn-secondary">Kembali ke Dashboard</a>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pills-all-tab" data-toggle="pill" href="#pills-all" role="tab">Semua</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-car-tab" data-toggle="pill" href="#pills-car" role="tab">Mobil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-motor-tab" data-toggle="pill" href="#pills-motor" role="tab">Motor</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-all" role="tabpanel">
                            <div class="row" id="service-list">
                                @foreach($services as $service)
                                    <div class="col-md-4 mb-3 service-item" data-vehicle-type="{{ $service->vehicle_type }}">
                                        <div class="card h-100 border-left-primary service-card shadow-sm" style="cursor: pointer; overflow: hidden;" onclick="addToCart({{ $service }})">
                                            @if($service->image)
                                                <img src="{{ asset('storage/' . $service->image) }}" class="card-img-top" alt="{{ $service->name }}" style="height: 140px; object-fit: cover;">
                                            @else
                                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 140px;">
                                                    <i class="fas fa-soap fa-3x text-gray-300"></i>
                                                </div>
                                            @endif
                                            <div class="card-body">
                                                <div class="font-weight-bold text-primary text-uppercase mb-1">{{ $service->name }}</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($service->price, 0, ',', '.') }}</div>
                                                <div class="text-xs font-weight-bold text-uppercase mt-2">
                                                    @if($service->vehicle_type == 'car') <i class="fas fa-car"></i> Mobil @else <i class="fas fa-motorcycle"></i> Motor @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <!-- Separate tabs for filtering can be done with JS or backend, keeping simple for now -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart / Checkout -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Keranjang</h6>
                </div>
                <div class="card-body">
                    <div id="cart-items" class="mb-3" style="min-height: 200px;">
                        <p class="text-muted text-center mt-5">Belum ada layanan dipilih</p>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="font-weight-bold">Total:</span>
                        <span class="font-weight-bold h5 text-primary" id="cart-total">Rp 0</span>
                    </div>

                    <form id="checkout-form">
                        <div class="form-group">
                            <label>Nama Pelanggan</label>
                            <input type="text" id="customer_name" class="form-control" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label>Plat Nomor</label>
                            <input type="text" id="plate_number" class="form-control" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label>Karyawan (Opsional)</label>
                            <select id="employee_id" class="form-control">
                                <option value="">-- Pilih Karyawan --</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pembayaran</label>
                            <input type="number" id="amount_paid" class="form-control" placeholder="Jumlah Bayar" required>
                        </div>
                        <div class="form-group">
                            <label>Metode</label>
                            <select id="payment_method" class="form-control">
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-block btn-lg">Bayar & Cetak</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab Filtering
    document.querySelectorAll('#pills-tab .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href'); // #pills-all, #pills-car, #pills-motor
            
            // Activate Tab
            document.querySelectorAll('#pills-tab .nav-link').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Filter Items
            const type = target.replace('#pills-', ''); // all, car, motor
            const items = document.querySelectorAll('.service-item');
            
            items.forEach(item => {
                if (type === 'all' || item.dataset.vehicleType === type) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

let cart = [];

function addToCart(service) {
    const existing = cart.find(item => item.id === service.id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({
            id: service.id,
            name: service.name,
            price: service.price,
            qty: 1
        });
    }
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) {
        removeFromCart(index);
    } else {
        renderCart();
    }
}

function renderCart() {
    const container = document.getElementById('cart-items');
    if (cart.length === 0) {
        container.innerHTML = '<p class="text-muted text-center mt-5">Belum ada layanan dipilih</p>';
        document.getElementById('cart-total').innerText = 'Rp 0';
        return;
    }

    let html = '<ul class="list-group list-group-flush">';
    let total = 0;

    cart.forEach((item, index) => {
        const subtotal = item.price * item.qty;
        total += subtotal;
        html += `
            <li class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="font-weight-bold">${item.name}</span>
                    <button class="btn btn-sm btn-danger py-0 px-2" onclick="removeFromCart(${index})">&times;</button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="updateQty(${index}, -1)">-</button>
                        <span class="mx-2">${item.qty}</span>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="updateQty(${index}, 1)">+</button>
                    </div>
                    <span>Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</span>
                </div>
            </li>
        `;
    });
    html += '</ul>';
    container.innerHTML = html;
    document.getElementById('cart-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
}

document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    if (cart.length === 0) return alert('Pilih layanan terlebih dahulu');

    const amountPaid = parseFloat(document.getElementById('amount_paid').value);
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    if (amountPaid < total) {
        return alert('Pembayaran kurang!');
    }

    const data = {
        items: cart,
        customer_name: document.getElementById('customer_name').value,
        plate_number: document.getElementById('plate_number').value,
        employee_id: document.getElementById('employee_id').value,
        amount_paid: amountPaid,
        payment_method: document.getElementById('payment_method').value,
        _token: '{{ csrf_token() }}'
    };

    fetch('{{ route("wash.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.open(data.redirect_url, '_blank');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan sistem');
        console.error(err);
    });
});
</script>
@endsection
