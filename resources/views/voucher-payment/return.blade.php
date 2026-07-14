<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        @if($payment->status === 'paid')
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-green-600 mb-2">Pembayaran Berhasil!</h1>
            <p class="text-gray-600 mb-6">Voucher Anda akan dikirimkan via WhatsApp.</p>
        @else
            <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-yellow-600 mb-2">Menunggu Konfirmasi</h1>
            <p class="text-gray-600 mb-6">Pembayaran Anda sedang diproses. Silakan cek status via WhatsApp.</p>
        @endif

        <a href="{{ route('voucher.payment.show', $payment->reference_id) }}" class="inline-block bg-purple-600 text-white font-semibold py-3 px-6 rounded-xl hover:bg-purple-700 transition">
            Lihat Detail Pembayaran
        </a>
    </div>
</body>
</html>
