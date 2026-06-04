<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Beli Voucher Hotspot - {{ config('app.name', 'MStore') }}</title>
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
        .voucher-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }
        .voucher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .voucher-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 24px;
        }
        .voucher-body {
            padding: 24px;
            background: white;
        }
        .price-tag {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent-color);
        }
        .btn-buy {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-buy:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        .feature-item i {
            color: var(--accent-color);
        }
        .hero-section {
            padding: 60px 0 40px;
            text-align: center;
        }
        .hero-section h1 {
            font-weight: 800;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 16px;
        }
        .hero-section p {
            color: #64748b;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <a href="{{ url('/') }}" class="btn btn-outline-secondary mb-4">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
            </a>
            <h1><i class="fab fa-whatsapp text-success me-2"></i>Beli Voucher Hotspot</h1>
            <p>Pilih paket voucher yang sesuai dengan kebutuhan Anda!</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4 mb-5">
            @foreach($templates as $template)
                <div class="col-md-4">
                    <div class="card voucher-card h-100">
                        <div class="voucher-header text-center">
                            <i class="fas fa-wifi fa-3x mb-2"></i>
                            <h4 class="mb-0">{{ $template->name }}</h4>
                            <small class="text-success-50 opacity-80">{{ $template->rate_limit ?: 'Kecepatan Maksimal' }}</small>
                        </div>
                        <div class="voucher-body">
                            <div class="text-center mb-4">
                                <div class="price-tag">
                                    Rp {{ number_format((float)$template->price, 0, ',', '.') }}
                                </div>
                            </div>
                            <ul class="list-unstyled mb-4">
                                @if($template->duration_seconds)
                                    <li class="feature-item">
                                        <i class="fas fa-clock"></i>
                                        <span>
                                            @if($template->duration_seconds % 86400 === 0)
                                                {{ (int)($template->duration_seconds / 86400) }} Hari
                                            @elseif($template->duration_seconds % 3600 === 0)
                                                {{ (int)($template->duration_seconds / 3600) }} Jam
                                            @elseif($template->duration_seconds % 60 === 0)
                                                {{ (int)($template->duration_seconds / 60) }} Menit
                                            @else
                                                {{ $template->duration_seconds }} Detik
                                            @endif
                                        </span>
                                    </li>
                                @endif
                                @if($template->quota_mb)
                                    <li class="feature-item">
                                        <i class="fas fa-database"></i>
                                        <span>{{ number_format((int)$template->quota_mb, 0, ',', '.') }} MB</span>
                                    </li>
                                @else
                                    <li class="feature-item">
                                        <i class="fas fa-infinity"></i>
                                        <span>Kuota Unlimited</span>
                                    </li>
                                @endif
                                @if($template->rate_limit)
                                    <li class="feature-item">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Kecepatan {{ $template->rate_limit }}</span>
                                    </li>
                                @endif
                            </ul>
                            <button class="btn-buy" data-template-id="{{ $template->id }}" data-template-name="{{ $template->name }}" data-price="{{ (float)$template->price }}">
                                <i class="fas fa-shopping-cart me-2"></i> Beli Voucher
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Pembelian -->
    <div class="modal fade" id="buyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <form id="buyForm" action="{{ route('voucher.payment.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="voucher_template_id" id="voucher_template_id">
                    <div class="modal-header" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                        <h5 class="modal-title">
                            <i class="fas fa-shopping-cart me-2"></i>Konfirmasi Pembelian
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Paket Voucher</label>
                            <input type="text" id="template_name_display" class="form-control-plaintext fw-bold text-success" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Harga</label>
                            <input type="text" id="price_display" class="form-control-plaintext fw-bold fs-4 text-primary" readonly>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="customer_name">Nama Anda <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required placeholder="Masukkan nama Anda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="phone_number">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">+62</span>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" required placeholder="81234567890" maxlength="15">
                            </div>
                            <small class="text-muted">Voucher akan dikirim ke nomor ini</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" style="padding: 12px 32px; border-radius: 12px;">
                            <i class="fas fa-check-circle me-2"></i>Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const buyModal = new bootstrap.Modal('#buyModal');
        
        document.querySelectorAll('.btn-buy').forEach(btn => {
            btn.addEventListener('click', () => {
                const templateId = btn.dataset.templateId;
                const templateName = btn.dataset.templateName;
                const price = parseFloat(btn.dataset.price);
                
                document.getElementById('voucher_template_id').value = templateId;
                document.getElementById('template_name_display').value = templateName;
                document.getElementById('price_display').value = 'Rp ' + price.toLocaleString('id-ID');
                document.getElementById('customer_name').value = '';
                document.getElementById('phone_number').value = '';
                
                buyModal.show();
            });
        });

        // Auto format phone number
        document.getElementById('phone_number').addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
