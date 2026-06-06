<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pilih Metode Pembayaran - {{ config('app.name', 'MStore') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #0f172a;
            --secondary-color: #1e293b;
            --accent-color: #10b981;
        }
        body {
            font-family: 'Plus Jakarta Sans', 'Public Sans', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        }
        .payment-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            cursor: pointer;
        }
        .payment-card:hover, .payment-card.selected {
            border-color: var(--accent-color);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);
        }
        .payment-card.selected {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }
        .hero-section {
            padding: 40px 0 30px;
            text-align: center;
        }
        .btn-pay {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
        }
        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <a href="{{ route('voucher.payment.index') }}" class="btn btn-outline-secondary mb-4">
                <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
            <h1><i class="fas fa-credit-card text-success me-2"></i>Pilih Metode Pembayaran</h1>
            <p>Pilih metode pembayaran yang Anda inginkan!</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4" style="border-radius: 16px; border: none;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Detail Pesanan</h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Paket</span>
                            <span class="fw-bold">{{ $template->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Nama</span>
                            <span class="fw-bold">{{ $request->customer_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">WhatsApp</span>
                            <span class="fw-bold">+62{{ $request->phone_number }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="price-tag">Rp {{ number_format($template->price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('voucher.payment.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="voucher_template_id" value="{{ $template->id }}">
                    <input type="hidden" name="customer_name" value="{{ $request->customer_name }}">
                    <input type="hidden" name="phone_number" value="{{ $request->phone_number }}">
                    <input type="hidden" name="email" value="{{ $request->email }}">
                    <input type="hidden" name="use_pop" value="1" id="use_pop">
                    <input type="hidden" name="payment_method" id="payment_method">

                    <div class="mb-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="use_pop_switch" checked onchange="toggleUsePop()">
                            <label class="form-check-label fw-bold" for="use_pop_switch">
                                <i class="fas fa-window-restore me-2"></i>Gunakan Duitku POP (Popup Pembayaran)
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Metode Pembayaran</h5>
                        <div class="row g-3" id="payment_methods">
                            @if(isset($paymentMethods['paymentFee']) && is_array($paymentMethods['paymentFee']))
                                @foreach($paymentMethods['paymentFee'] as $method)
                                    <div class="col-md-6">
                                        <div class="payment-card p-3" data-method="{{ $method['paymentMethod'] }}">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="fas fa-wallet fa-2x text-success"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-0">{{ $method['paymentName'] }}</h6>
                                                    <small class="text-muted">{{ $method['paymentFee'] > 0 ? 'Biaya: Rp ' . number_format($method['paymentFee'], 0, ',', '.') : 'Gratis' }}</small>
                                                </div>
                                                <i class="fas fa-check-circle text-success d-none selected-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Tidak dapat mengambil daftar metode pembayaran. Silakan gunakan opsi "Gunakan Duitku POP" di atas untuk melanjutkan pembayaran.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn-pay" id="submit_btn">
                        <i class="fas fa-check-circle me-2"></i>Lanjutkan Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMethod = null;

        function toggleUsePop() {
            const usePop = document.getElementById('use_pop_switch').checked;
            document.getElementById('use_pop').value = usePop ? '1' : '0';
            
            // Enable submit button if using POP, even without selecting a payment method
            if (usePop) {
                document.getElementById('submit_btn').disabled = false;
            } else {
                document.getElementById('submit_btn').disabled = selectedMethod === null;
            }
        }

        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.payment-card').forEach(c => {
                    c.classList.remove('selected');
                    c.querySelector('.selected-icon')?.classList.add('d-none');
                });
                
                card.classList.add('selected');
                card.querySelector('.selected-icon')?.classList.remove('d-none');
                
                selectedMethod = card.dataset.method;
                document.getElementById('payment_method').value = selectedMethod;
                document.getElementById('submit_btn').disabled = false;
            });
        });
        
        // Initialize submit button state
        document.addEventListener('DOMContentLoaded', function() {
            toggleUsePop();
        });
    </script>
</body>
</html>
