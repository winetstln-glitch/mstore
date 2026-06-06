<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Voucher Hotspot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .qr-container {
            background: white;
            padding: 24px;
            border-radius: 20px;
        }
        @media (max-width: 640px) {
            .qr-container {
                padding: 16px;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="qr-container shadow-2xl">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4m12-4l-4 4 4 4m-4 4l4-4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Pembayaran Voucher Hotspot</h1>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Paket</span>
                    <span class="font-semibold text-gray-800">{{ $payment->voucherTemplate->name }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Kode Referensi</span>
                    <span class="font-mono text-purple-600">{{ $payment->reference_id }}</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                    <span class="text-gray-800 font-semibold">Total</span>
                    <span class="text-xl font-bold text-purple-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            @if($payment->status === 'pending')
                @if($payment->qr_url)
                    <div class="text-center" id="payment-section">
                        @if($payment->use_pop)
                            <p class="text-gray-600 mb-4">Klik tombol dibawah ini untuk membayar:</p>
                            <button onclick="openDuitkuPop()" class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-semibold py-3 px-6 rounded-xl hover:from-purple-600 hover:to-indigo-700 transition shadow-lg">
                                <i class="fas fa-credit-card mr-2"></i>Bayar Sekarang
                            </button>
                        @else
                            <p class="text-gray-600 mb-4">Scan QR Code dibawah ini untuk membayar:</p>
                            <a href="{{ $payment->qr_url }}" target="_blank" class="block">
                                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-semibold py-3 px-6 rounded-xl hover:from-purple-600 hover:to-indigo-700 transition shadow-lg">
                                    Buka Halaman Pembayaran
                                </div>
                            </a>
                        @endif
                        @if($payment->expires_at)
                            <p class="text-sm text-gray-500 mt-4">Kadaluarsa pada: {{ $payment->expires_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                @else
                    <div class="text-center">
                        <p class="text-yellow-600 mb-4">QR Code sedang dipersiapkan, silakan refresh halaman dalam beberapa detik.</p>
                        <button onclick="location.reload()" class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-semibold py-3 px-6 rounded-xl hover:from-yellow-600 hover:to-orange-600 transition shadow-lg">
                            Refresh Halaman
                        </button>
                    </div>
                @endif
            @elseif($payment->status === 'paid')
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-green-600 mb-2">Pembayaran Berhasil!</h2>
                    <p class="text-gray-600 mb-4">Voucher Anda akan dikirimkan via WhatsApp.</p>
                    @if($payment->voucher)
                        <div class="bg-green-50 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-800 mb-3">Detail Voucher</h3>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="bg-white p-3 rounded-lg text-center">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Username</p>
                                    <p class="font-mono font-bold text-gray-800">{{ $payment->voucher->username }}</p>
                                </div>
                                <div class="bg-white p-3 rounded-lg text-center">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Password</p>
                                    <p class="font-mono font-bold text-gray-800">{{ $payment->voucher->password }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($payment->status === 'expired' || $payment->status === 'failed')
                <div class="text-center">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-red-600 mb-2">{{ $payment->status === 'expired' ? 'Kadaluarsa' : 'Gagal' }}</h2>
                    <p class="text-gray-600">Silakan buat pembayaran baru.</p>
                    <a href="{{ route('voucher.payment.index') }}" class="inline-block mt-4 bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-semibold py-3 px-6 rounded-xl hover:from-purple-600 hover:to-indigo-700 transition shadow-lg">
                        Beli Voucher Lagi
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if($payment->status === 'pending' && $payment->qr_url && $payment->use_pop)
        <script src="https://app.duitku.com/pop/js/pop.js"></script>
        <script>
            let duitkuReference = '{{ $payment->duitku_reference }}';
            
            function openDuitkuPop() {
                checkout.process(duitkuReference, {
                    successEvent: function(result) {
                        console.log('success', result);
                        location.reload();
                    },
                    pendingEvent: function(result) {
                        console.log('pending', result);
                    },
                    errorEvent: function(result) {
                        console.log('error', result);
                    },
                    closeEvent: function(result) {
                        console.log('close', result);
                        checkStatus();
                    }
                });
            }

            function checkStatus() {
                fetch('{{ route('voucher.payment.check_status', $payment->reference_id) }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.statusCode === '00') {
                            location.reload();
                        }
                    })
                    .catch(err => console.error(err));
            }

            // Auto check status every 5 seconds
            setInterval(checkStatus, 5000);
        </script>
    @endif
</body>
</html>
