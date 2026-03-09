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
    
    $customerName = trim((string) ($transaction->customer_name ?? ''));
    $customerName = $customerName !== '' ? $customerName : '-';
    $vehiclePlate = strtoupper(trim((string) ($transaction->vehicle_plate ?? '')));
    $vehiclePlate = $vehiclePlate !== '' ? $vehiclePlate : '-';
    $cashierName = strtoupper(trim((string) ($transaction->user->name ?? '')));
    $cashierName = $cashierName !== '' ? $cashierName : '-';
    $printedAt = date('d/m/Y H:i:s');
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
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1;
            color: #000;
            margin: 0 auto;
            padding: 10px;
            background: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .no-print-area {
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 10px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .paper-selector {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 5px;
        }

        .paper-selector label {
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
            text-align: center;
        }

        .paper-selector input:checked + label {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .paper-selector input { display: none; }

        .btn {
            background: #222;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        .btn-blue { background: #007bff; }
        
        #receipt-wrapper {
            background: #fff;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: width 0.3s ease;
        }

        .size-58mm { width: 58mm; }
        .size-80mm { width: 80mm; }

        .header { text-align: center; margin-bottom: 10px; }
        .header-logo {
            display: block;
            margin: 0 auto;
            max-width: 80px;
            filter: grayscale(1);
        }
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; }

        .divider { border-top: 1px dashed #000; margin: 8px 0; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { font-size: 11px; padding: 2px 0; vertical-align: top; }
        .label { width: 70px; }

        .queue-badge {
            text-align: center;
            border: 2px solid #000;
            padding: 10px;
            margin: 10px 0;
            font-size: 20px;
            font-weight: bold;
        }

        .item-row { margin-bottom: 5px; }
        .item-main { display: flex; justify-content: space-between; font-weight: bold; }
        .item-sub { font-size: 10px; }

        .total-row { display: flex; justify-content: space-between; margin-top: 2px; }
        .grand-total { font-weight: bold; font-size: 14px; border-top: 1px solid #000; padding-top: 4px; margin-top: 4px;}

 .footer { text-align: center; 
            margin-top: 20px; 
 }
        .footer h2 { margin: 0; font-size: 12px; text-transform: uppercase; }
        .footer p { margin: 2px 0; 
                    font-size: 10px;
                    font-style: italic; }

        @media print {
            .no-print-area { display: none; }
            body { background: transparent; padding: 0; }
            #receipt-wrapper { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print-area">
        <div style="text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 5px;">Pilih Ukuran Kertas:</div>
        <div class="paper-selector">
            <input type="radio" name="paper_size" id="size58" value="32" checked onchange="updatePreviewSize('58')">
            <label for="size58">58mm (32 Kolom)</label>
            
            <input type="radio" name="paper_size" id="size80" value="48" onchange="updatePreviewSize('80')">
            <label for="size80">80mm (48 Kolom)</label>
        </div>
        
        <button class="btn btn-blue" onclick="printBluetoothDirect()">Connect & Print Bluetooth</button>
        <button class="btn" onclick="window.print()">Print via Browser (PDF/System)</button>
        <div id="status" style="font-size: 10px; color: #666; text-align: center;">Status: Ready</div>
    </div>

    <!-- START RECEIPT CONTENT -->
    <div id="receipt-wrapper" class="size-58mm">
        <div id="receipt-content">
            <div class="header">
                @if(!empty($receiptStoreLogo))
                    <img id="logo-img" src="{{ $receiptStoreLogo }}" class="header-logo" crossorigin="anonymous">
                @endif
                <h2>{{ $receiptStoreName }}</h2>
                <p>{{ $receiptStoreAddress }}</p>
                <p>{{ $receiptStorePhoneLabel }}</p>
            </div>

            <div class="divider"></div>

            <table class="info-table">
                <tr><td class="label">Nota</td><td>: {{ $transaction->transaction_number }}</td></tr>
                <tr><td class="label">Waktu</td><td>: {{ $transaction->created_at->format('d/m/y H:i') }}</td></tr>
                <tr><td class="label">Kasir</td><td>: {{ $cashierName }}</td></tr>
                <tr><td class="label">Cust/Plat</td><td>: {{ $customerName }} / {{ $vehiclePlate }}</td></tr>
            </table>

            @if(!empty($transaction->queue_number))
                <div class="queue-badge">ANTRIAN: #{{ $transaction->queue_number }}</div>
            @endif

            <div class="divider"></div>

            @foreach($transaction->items as $item)
                <div class="item-row">
                    <div class="item-main">
                        <span>{{ strtoupper($item->service_name) }}</span>
                        <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="item-sub">
                        {{ (float)$item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}
                    </div>
                </div>
            @endforeach

            <div class="divider"></div>

            @if(($transaction->discount_amount ?? 0) > 0)
                <div class="total-row">
                    <span>Diskon</span>
                    <span>-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="total-row grand-total">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>

            <div class="total-row">
                <span>Metode</span>
                <span>{{ strtoupper($transaction->payment_method ?? 'CASH') }}</span>
            </div>

            @if(($transaction->cash_amount ?? 0) > 0)
                <div class="total-row">
                    <span>Bayar</span>
                    <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
                </div>
                <div class="total-row">
                    <span>Kembali</span>
                    <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="footer">
            <h2>*** TERIMA KASIH ***</h2>
            <p>Kepuasan Anda Kebanggaan Kami.<br>Periksa kembali barang bawaan Anda sebelum meninggalkan lokasi.</p>
            <p class="timestamp">Dicetak pada: {{ $printedAt }}</p>
        </div>
        </div>
    </div>
    <!-- END RECEIPT CONTENT -->

    <canvas id="canvas-logo" style="display:none;"></canvas>

<script>
/**
 * DATA PREPARATION
 */
const data = {{ Js::from([
    'logo' => !empty($receiptStoreLogo) ? $receiptStoreLogo : null,
    'store' => $receiptStoreName,
    'address' => $receiptStoreAddress,
    'phone' => $receiptStorePhoneLabel,
    'nota' => $transaction->transaction_number,
    'time' => $transaction->created_at->format('d/m/y H:i'),
    'cashier' => $cashierName,
    'customer' => $customerName . " / " . $vehiclePlate,
    'queue' => $transaction->queue_number ?? null,
    'items' => $transaction->items->map(fn($i) => [
        'n' => strtoupper($i->service_name),
        'q' => (float)$i->quantity,
        'p' => (float)$i->price,
        's' => (float)$i->subtotal
    ]),
    'discount' => (float)($transaction->discount_amount ?? 0),
    'total' => (float)$transaction->total_amount,
    'method' => strtoupper($transaction->payment_method ?? 'CASH'),
    'cash' => (float)($transaction->cash_amount ?? 0),
    'change' => (float)($transaction->change_amount ?? 0),
    'printed_at' => $printedAt,
]) }};

function updatePreviewSize(size) {
    const wrapper = document.getElementById('receipt-wrapper');
    if (size === '58') {
        wrapper.className = 'size-58mm';
    } else {
        wrapper.className = 'size-80mm';
    }
}

/**
 * ESC/POS UTILS
 */
class EscPosBuilder {
    constructor(maxChars = 32) {
        this.encoder = new TextEncoder();
        this.buffer = [];
        this.maxChars = maxChars;
    }
    
    add(data) {
        if (typeof data === 'string') {
            this.buffer.push(...this.encoder.encode(data));
        } else if (data instanceof Uint8Array || Array.isArray(data)) {
            this.buffer.push(...data);
        }
    }

    init() { this.add([0x1B, 0x40]); }
    center() { this.add([0x1B, 0x61, 1]); }
    left() { this.add([0x1B, 0x61, 0]); }
    right() { this.add([0x1B, 0x61, 2]); }
    bold(on) { this.add([0x1B, 0x45, on ? 1 : 0]); }
    big(on) { this.add([0x1B, 0x21, on ? 0x30 : 0x00]); }
    feed(n = 3) { this.add(new Array(n).fill(0x0A)); }
    line() { this.add("-".repeat(this.maxChars) + "\n"); }

    justify(leftText, rightText) {
        const spaces = this.maxChars - leftText.toString().length - rightText.toString().length;
        this.add(leftText + " ".repeat(Math.max(1, spaces)) + rightText + "\n");
    }

    async addImage(imgElement) {
        if (!imgElement) return;
        
        const canvas = document.getElementById('canvas-logo');
        const ctx = canvas.getContext('2d');
        
        // Ukuran logo disesuaikan dengan lebar kolom
        const maxWidth = this.maxChars === 48 ? 240 : 180; 
        const scale = maxWidth / imgElement.naturalWidth;
        const width = maxWidth;
        const height = Math.round(imgElement.naturalHeight * scale);
        
        canvas.width = width;
        canvas.height = height;
        
        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(imgElement, 0, 0, width, height);
        
        const imageData = ctx.getImageData(0, 0, width, height);
        const pixels = imageData.data;
        
        const widthBytes = Math.ceil(width / 8);
        const raster = new Uint8Array(widthBytes * height);
        
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const idx = (y * width + x) * 4;
                const intensity = (pixels[idx] + pixels[idx+1] + pixels[idx+2]) / 3;
                if (intensity < 150) { 
                    raster[y * widthBytes + Math.floor(x / 8)] |= (0x80 >> (x % 8));
                }
            }
        }
        
        const xL = widthBytes % 256;
        const xH = Math.floor(widthBytes / 256);
        const yL = height % 256;
        const yH = Math.floor(height / 256);
        
        this.add([0x1D, 0x76, 0x30, 0, xL, xH, yL, yH]);
        this.add(raster);
        this.add("\n");
    }

    generate() { return new Uint8Array(this.buffer); }
}

const formatIdr = (n) => new Intl.NumberFormat('id-ID').format(n);

function invokeBridgePrinter(invoker, payload) {
    try {
        invoker(payload);
        return true;
    } catch (_) {
        try {
            invoker(JSON.stringify(payload));
            return true;
        } catch (_) {
            return false;
        }
    }
}

function resolveBluetoothBridge() {
    const methodNames = [
        'printBluetoothAction',
        'printBluetooth',
        'printReceipt',
        'printStruk',
        'printBluetoothReceipt',
        'cetakBluetooth',
        'handleBluetoothPrint',
        'printViaBluetooth',
        'sendPrintJob',
        'bluetoothPrint'
    ];

    for (const method of methodNames) {
        if (typeof window[method] === 'function') {
            return function (payload) {
                return invokeBridgePrinter(window[method], payload);
            };
        }
    }

    const bridgeCandidates = [window.Android, window.android, window.MstoreAndroid, window.NativeAndroid, window.JsBridge].filter(Boolean);
    for (const bridge of bridgeCandidates) {
        for (const method of methodNames) {
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

    if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.printBluetooth && typeof window.webkit.messageHandlers.printBluetooth.postMessage === 'function') {
        return function (payload) {
            return invokeBridgePrinter(function (data) {
                return window.webkit.messageHandlers.printBluetooth.postMessage(data);
            }, payload);
        };
    }

    if (window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === 'function') {
        return function (payload) {
            return invokeBridgePrinter(function (data) {
                const message = { action: 'printBluetooth', payload: data };
                return window.ReactNativeWebView.postMessage(JSON.stringify(message));
            }, payload);
        };
    }

    return null;
}

/**
 * CORE PRINT FUNCTION
 */
async function printBluetoothDirect() {
    const status = document.getElementById('status');
    const paperSize = parseInt(document.querySelector('input[name="paper_size"]:checked').value);
    const bridgePayload = {
        store: data.store,
        address: data.address,
        phone: data.phone,
        date: data.time,
        number: data.nota,
        cashier: data.cashier,
        queue: data.queue,
        customer: data.customer,
        items: data.items.map((item) => ({
            name: item.n,
            qty: item.q,
            price: item.p,
            subtotal: item.s
        })),
        discount: data.discount,
        total: data.total,
        cash: data.cash,
        change: data.change,
        method: data.method,
        paper_size: paperSize
    };
    const bridgePrinter = resolveBluetoothBridge();
    
    try {
        if (!(navigator.bluetooth && typeof navigator.bluetooth.requestDevice === 'function')) {
            if (!bridgePrinter) {
                throw new Error('Bluetooth tidak didukung browser ini. Gunakan Chrome (HTTPS) atau aplikasi Android MStore.');
            }
            status.innerText = "Sending via App Bridge...";
            const ok = bridgePrinter(bridgePayload);
            if (!ok) {
                throw new Error('Bridge Bluetooth tersedia, tetapi gagal mengirim data.');
            }
            status.innerText = "Print Success via Bridge!";
            setTimeout(() => status.innerText = "Status: Ready", 3000);
            return;
        }

        status.innerText = "Requesting Printer...";
        const device = await navigator.bluetooth.requestDevice({
            filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }],
            optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
        });

        status.innerText = "Connecting...";
        const server = await device.gatt.connect();
        const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
        const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

        status.innerText = `Processing (${paperSize === 32 ? '58mm' : '80mm'})...`;
        const esc = new EscPosBuilder(paperSize);
        esc.init();

        // 1. Logo
        const logoImg = document.getElementById('logo-img');
        if (logoImg && logoImg.complete) {
            esc.center();
            await esc.addImage(logoImg);
        }

        // 2. Header
        esc.center();
        esc.bold(true);
        esc.add(`${data.store}\n`);
        esc.bold(false);
        esc.add(`${data.address}\n`);
        esc.add(`${data.phone}\n`);
        esc.line();

        // 3. Info
        esc.left();
        esc.add(`Nota : ${data.nota}\n`);
        esc.add(`Waktu: ${data.time}\n`);
        esc.add(`Kasir: ${data.cashier}\n`);
        esc.add(`Cust : ${data.customer}\n`);
        
        if (data.queue) {
            esc.center();
            esc.big(true);
            esc.add(`\nANTRIAN #${data.queue}\n\n`);
            esc.big(false);
        }

        esc.line();

        // 4. Items
        data.items.forEach(item => {
            esc.left();
            esc.add(`${item.n}\n`);
            let priceDetail = `${item.q} x ${formatIdr(item.p)}`;
            let subtotal = formatIdr(item.s);
            esc.justify(priceDetail, subtotal);
        });

        esc.line();

        // 5. Totals
        if (data.discount > 0) {
            esc.justify("Diskon", `-${formatIdr(data.discount)}`);
        }

        esc.bold(true);
        esc.justify("TOTAL", `Rp ${formatIdr(data.total)}`);
        esc.bold(false);

        esc.add(`Metode: ${data.method}\n`);
        if (data.cash > 0) {
            esc.justify("Bayar", formatIdr(data.cash));
            esc.justify("Kembali", formatIdr(data.change));
        }

        // 6. Footer
        esc.feed(1);
        esc.center();
        esc.add("*** TERIMA KASIH ***\n");
        esc.add("Kepuasan Anda Kebanggaan Kami.\n");
        esc.add("Periksa kembali barang bawaan Anda\n");
        esc.add("sebelum meninggalkan lokasi.\n");
        esc.add(`Dicetak pada: ${data.printed_at}\n`);
        esc.feed(3);

        status.innerText = "Sending Data...";
        const receiptData = esc.generate();
        const chunkSize = 20; 
        for (let i = 0; i < receiptData.length; i += chunkSize) {
            await characteristic.writeValue(receiptData.slice(i, i + chunkSize));
        }

        status.innerText = "Print Success!";
        setTimeout(() => status.innerText = "Status: Ready", 3000);

    } catch (error) {
        if (bridgePrinter) {
            const ok = bridgePrinter(bridgePayload);
            if (ok) {
                status.innerText = "Print Success via Bridge!";
                setTimeout(() => status.innerText = "Status: Ready", 3000);
                return;
            }
        }
        status.innerText = "Error: " + error.message;
    }
}
</script>
</body>
</html>
