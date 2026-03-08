@php
    $generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
    $generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
    $generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
    $generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
    $generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
        ? asset($generalStoreLogo)
        : $generalStoreLogo;

    $receiptStoreName = \App\Models\Setting::getValue('wash_store_name', $generalStoreName ?: 'AUTO WASH');
    $receiptStoreAddress = \App\Models\Setting::getValue('wash_store_address', $generalStoreAddress ?: 'Jl. Contoh Bersih No. 123');
    $receiptStorePhone = \App\Models\Setting::getValue('wash_store_phone', $generalStorePhone ?: '0812-0000-0000');
    $receiptStoreLogo = \App\Models\Setting::getValue('wash_store_logo', $generalStoreLogo);
    $receiptStoreLogo = $receiptStoreLogo && !str_starts_with($receiptStoreLogo, 'http') && !str_starts_with($receiptStoreLogo, 'data:') && !str_starts_with($receiptStoreLogo, '/')
        ? asset($receiptStoreLogo)
        : $receiptStoreLogo;
    
    $receiptStorePhoneLabel = str_starts_with(strtolower($receiptStorePhone), 'telp') ? $receiptStorePhone : 'Telp: '.$receiptStorePhone;
    $posPrinterAutoReconnect = \App\Models\Setting::getValue('pos_printer_auto_reconnect', '1') === '1';
    $posPrintLogoEnabled = \App\Models\Setting::getValue('pos_print_logo_enabled', '1') === '1';
    $posBluetoothChunkSize = (int) \App\Models\Setting::getValue('pos_bluetooth_chunk_size', '256');
    $posBluetoothChunkDelayMs = (int) \App\Models\Setting::getValue('pos_bluetooth_chunk_delay_ms', '0');
    $posQrisText = \App\Models\Setting::getValue('pos_qris_text', '');
    $posPreferredPrinterName = \App\Models\Setting::getValue('pos_preferred_printer_name', '');
    $posPreferredPrinterId = \App\Models\Setting::getValue('pos_preferred_printer_id', '');
    $posPerformanceProfile = \App\Models\Setting::getValue('pos_performance_profile', 'ultrafast');
    $customerName = trim((string) ($transaction->customer_name ?? ''));
    $customerName = $customerName !== '' ? $customerName : '-';
    $vehiclePlate = strtoupper(trim((string) ($transaction->vehicle_plate ?? '')));
    $vehiclePlate = $vehiclePlate !== '' ? $vehiclePlate : '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $transaction->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; }
         body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            width: 58mm;
            margin: 0 auto;
            padding: 8px;
            background: #fff;
        }

        .no-print-area {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
            background: #f4f4f4;
            padding: 8px;
            border-radius: 4px;
        }
        .btn {
            background: #333;
            color: #fff;
            border: none;
            padding: 8px;
            border-radius: 3px;
            font-family: sans-serif;
            font-size: 10px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-blue { background: #0056b3; }
        
        .header { text-align: center; margin-bottom: 8px; }
        .header-logo {
            display: block;
            margin: 0 auto 5px;
            max-width: 100px;
            max-height: 45px;
            object-fit: contain;
            filter: grayscale(1);
        }
        .header h2 { margin: 2px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 0; font-size: 9px; }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
        }
  .payment-status {
            margin-top: 12px;
            text-align: center;
            border: 1px solid #000;
            padding: 4px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
        }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .info-table td { vertical-align: top; font-size: 10px; padding: 1px 0; }
        .label { color: #333; width: 65px; font-weight: bold; }

        .queue-badge {
            text-align: center;
            border: 1px solid #000;
            padding: 6px;
            margin: 8px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .items-list { width: 100%; margin: 5px 0; }
        .item-row { margin-bottom: 6px; }
        .item-name { text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 2px; }
        .item-details { display: flex; justify-content: space-between; font-size: 10px; }

        .totals { margin-top: 5px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .total-row.grand-total { font-size: 14px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; }

        .footer { text-align: center; margin-top: 15px; font-size: 9px; }
        .footer p { margin: 2px 0; }
        
        @media print {
            .no-print-area { display: none; }
            body { padding: 0; width: 58mm; }
        }
    </style>
</head>
<body>
    <!-- Area Navigasi (Tidak Ikut Dicetak) -->
    <div class="no-print-area">
        <button class="btn" onclick="window.print()">Cetak (Browser)</button>
        <button class="btn btn-blue" onclick="printBluetooth()">Cetak Bluetooth</button>
        <div id="printQueueInfo" style="font-size: 9px; text-align: center;"></div>
    </div>

    <!-- Konten Struk -->
    <div class="header">
        @if(!empty($receiptStoreLogo))
            <img src="{{ $receiptStoreLogo }}" alt="Logo" class="header-logo">
        @endif
        <h2>{{ $receiptStoreName }}</h2>
        <p>{{ $receiptStoreAddress }}</p>
        <p>{{ $receiptStorePhoneLabel }}</p>
    </div>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td class="label">Nota</td>
            <td>: {{ $transaction->transaction_number }}</td>
        </tr>
        <tr>
            <td class="label">Waktu</td>
            <td>: {{ $transaction->created_at->format('d/m/y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Kasir</td>
            <td>: {{ $transaction->user->name ?? 'Admin' }}</td>
        </tr>
        <tr>
            <td class="label">Customer</td>
            <td>: {{ $customerName }}</td>
        </tr>
        <tr>
            <td class="label">No. Kend</td>
            <td>: <strong>{{ $vehiclePlate }}</strong></td>
        </tr>
    </table>

    @if(!empty($transaction->queue_number))
        <div class="queue-badge">
            ANTRIAN: #{{ $transaction->queue_number }}
        </div>
    @endif

    <div class="divider"></div>

    <div class="items-list">
        @foreach($transaction->items as $item)
            <div class="item-row">
                <span class="item-name">{{ $item->service_name }}</span>
                <div class="item-details">
                    <span>{{ (float)$item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                    <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="divider"></div>

    <div class="totals">
        @if(($transaction->discount_amount ?? 0) > 0)
        <div class="total-row">
            <span>Diskon</span>
            <span>-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        
        <div class="total-row grand-total">
            <span>TOTAL</span>
            <span>{{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
        </div>

        @if(($transaction->cash_amount ?? 0) > 0)
            <div class="total-row" style="margin-top:4px">
                <span>Bayar (Tunai)</span>
                <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Kembali</span>
                <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        @else
            <div class="total-row" style="margin-top:4px">
                <span>Metode Pembayaran</span>
                <span>{{ strtoupper($transaction->payment_method) }}</span>
            </div>
        @endif
    </div>
<div class="payment-status">
        Pembayaran - {{ strtoupper($transaction->payment_method ?? 'CASH') }}
    </div>
    <div class="footer">
        <div class="divider"></div>
        <p><strong>TERIMA KASIH</strong></p>
        <p>Kendaraan Anda adalah prioritas kami.</p>
        <p>{{ $transaction->created_at->format('l, d F Y') }}</p>
    </div>

    <script>
        const receiptPayload = {{ Js::from([
            'store' => $receiptStoreName,
            'address' => $receiptStoreAddress,
            'phone' => $receiptStorePhoneLabel,
            'logo' => $receiptStoreLogo,
            'date' => $transaction->created_at->format('d/m/Y H:i'),
            'number' => $transaction->transaction_number,
            'queue' => $transaction->queue_number ?? null,
            'customer' => $customerName,
            'vehicle' => $vehiclePlate,
            'cashier' => $transaction->user->name ?? 'Admin',
            'items' => $transaction->items->map(fn ($item) => [
                'name' => $item->service_name,
                'qty' => (float) $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'discount' => (float) ($transaction->discount_amount ?? 0),
            'total' => (float) $transaction->total_amount,
            'cash' => (float) ($transaction->cash_amount ?? 0),
            'change' => (float) ($transaction->change_amount ?? 0),
            'method' => strtoupper($transaction->payment_method ?? 'cash'),
            'footer1' => 'TERIMA KASIH',
            'footer2' => 'Kendaraan Anda Bersih Kembali',
        ]) }};

        function isMobileDevice() {
            const ua = navigator.userAgent || navigator.vendor || '';
            return /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(ua) || window.innerWidth <= 768;
        }

        function invokeBridgePrinter(invoker, payload) {
            try {
                return invoker(payload) !== false;
            } catch (_) {
                try {
                    return invoker(JSON.stringify(payload)) !== false;
                } catch (_) {
                    return false;
                }
            }
        }

        function resolveBluetoothPrinter() {
            const windowMethods = ['printBluetoothAction', 'printBluetooth', 'printReceipt', 'printStruk', 'printBluetoothReceipt', 'cetakBluetooth'];
            for (const method of windowMethods) {
                if (typeof window[method] === 'function') {
                    return function (payload) {
                        return invokeBridgePrinter(window[method], payload);
                    };
                }
            }
            const bridgeCandidates = [window.Android, window.android, window.MstoreAndroid].filter(Boolean);
            for (const bridge of bridgeCandidates) {
                for (const method of windowMethods) {
                    if (typeof bridge[method] !== 'function') {
                        continue;
                    }
                    return function (payload) {
                        return invokeBridgePrinter(function (data) {
                            return bridge[method](data);
                        }, payload);
                    };
                }
                if (typeof bridge.postMessage === 'function') {
                    return function (payload) {
                        return invokeBridgePrinter(function (data) {
                            return bridge.postMessage({ action: 'printBluetooth', payload: data });
                        }, payload);
                    };
                }
            }
            return null;
        }

        function printBluetooth(attempt = 0) {
            const queueInfo = document.getElementById('printQueueInfo');
            const maxAttempts = 8;
            const printer = resolveBluetoothPrinter();
            if (!printer && attempt < maxAttempts) {
                if (queueInfo) {
                    queueInfo.textContent = 'Menunggu layanan Bluetooth...';
                }
                setTimeout(function () {
                    printBluetooth(attempt + 1);
                }, 250);
                return true;
            }
            if (!printer) {
                if (queueInfo) {
                    queueInfo.textContent = 'Bridge Bluetooth tidak terdeteksi, pakai Cetak (Browser).';
                }
                alert('Bridge Bluetooth tidak terdeteksi. Silakan buka dari aplikasi Android MStore.');
                return false;
            }
            const success = printer(receiptPayload);
            if (queueInfo) {
                queueInfo.textContent = success ? 'Data dikirim ke printer Bluetooth.' : 'Gagal mengirim ke printer Bluetooth.';
            }
            if (!success) {
                alert('Gagal mencetak via Bluetooth. Pastikan printer terhubung.');
            }
            return success;
        }
        
        window.onload = function() {
            const autoPrint = new URLSearchParams(window.location.search).get('autoprint');
            if (autoPrint === '1') {
                const printedViaBluetooth = isMobileDevice() ? printBluetooth() : false;
                if (!printedViaBluetooth) {
                    window.print();
                }
            }
        };
    </script>
</body>
</html>
