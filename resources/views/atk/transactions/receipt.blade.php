@php
    $generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
    $generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
    $generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
    $generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
    $generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
        ? asset($generalStoreLogo)
        : $generalStoreLogo;

    $receiptStoreName = \App\Models\Setting::getValue('atk_store_name', $generalStoreName ?: 'ATK STORE');
    $receiptStoreAddress = \App\Models\Setting::getValue('atk_store_address', $generalStoreAddress ?: 'Jl. Raya Contoh No. 123');
    $receiptStorePhone = \App\Models\Setting::getValue('atk_store_phone', $generalStorePhone ?: '0812-3456-7890');
    $receiptStoreLogo = \App\Models\Setting::getValue('atk_store_logo', $generalStoreLogo);
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
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $transaction->transaction_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            width: 58mm;
            margin: 0;
            padding: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
        }
        .header-logo {
            display: block;
            margin: 0 auto 6px;
            max-width: 120px;
            max-height: 40px;
            object-fit: contain;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .total-section {
            margin-top: 10px;
            text-align: right;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; }
            .receipt-actions { display: none; }
        }
        .receipt-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .receipt-actions button {
            border: 1px solid #000;
            background: #fff;
            padding: 4px 8px;
            font-family: inherit;
            font-size: 11px;
            cursor: pointer;
        }
        .receipt-actions span {
            font-size: 11px;
            align-self: center;
        }
    </style>
    <script>
        const receiptPayload = {{ Js::from([
            'store' => $receiptStoreName,
            'address' => $receiptStoreAddress,
            'phone' => $receiptStorePhoneLabel,
            'logo' => $receiptStoreLogo,
            'date' => $transaction->created_at->format('d/m/Y H:i'),
            'number' => $transaction->transaction_number,
            'cashier' => $transaction->user->name ?? 'Admin',
            'items' => $transaction->items->map(fn ($item) => [
                'name' => $item->product_name,
                'qty' => (float) $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'total' => (float) $transaction->total_amount,
            'cash' => (float) ($transaction->cash_amount ?? 0),
            'change' => (float) ($transaction->change_amount ?? 0),
            'method' => strtoupper($transaction->payment_method ?? 'cash'),
            'footer1' => 'Thank you for your visit!',
            'footer2' => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.',
            'qrisText' => $posQrisText,
            'printerConfig' => [
                'autoReconnect' => $posPrinterAutoReconnect,
                'logoEnabled' => $posPrintLogoEnabled,
                'chunkSize' => $posBluetoothChunkSize,
                'chunkDelayMs' => $posBluetoothChunkDelayMs,
                'defaultPrinterName' => $posPreferredPrinterName,
                'defaultPrinterId' => $posPreferredPrinterId,
                'performanceProfile' => $posPerformanceProfile,
            ],
        ]) }};

        const bluetoothServiceUuids = [
            0xFFE0,
            '0000ffe0-0000-1000-8000-00805f9b34fb',
            '49535343-fe7d-4ae5-8fa9-9fafd205e455'
        ];

        const bluetoothCharacteristicUuids = [
            0xFFE1,
            '0000ffe1-0000-1000-8000-00805f9b34fb',
            '49535343-8841-43f4-a8d4-ecbe34729bb3',
            '49535343-1e4d-4bd9-ba61-23c647249616'
        ];

        const printerStorageKey = 'mstore.pos.selectedPrinter.v1';
        const queueStorageKey = 'mstore.pos.printQueue.v1';
        const logoRasterCache = new Map();
        let queueInProgress = false;

        function formatCurrency(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function getPrinterConfig() {
            const raw = receiptPayload.printerConfig || {};
            const profile = ['ultrafast', 'balanced', 'stable'].includes(raw.performanceProfile) ? raw.performanceProfile : 'ultrafast';
            const presets = {
                ultrafast: { chunkSize: 512, chunkDelayMs: 0, retryBaseMs: 300, retryMaxMs: 5000 },
                balanced: { chunkSize: 256, chunkDelayMs: 0, retryBaseMs: 1000, retryMaxMs: 15000 },
                stable: { chunkSize: 180, chunkDelayMs: 10, retryBaseMs: 2000, retryMaxMs: 30000 },
            };
            const profilePreset = presets[profile];
            const chunkSize = Number(raw.chunkSize || 256);
            const chunkDelayMs = Number(raw.chunkDelayMs || 0);
            return {
                autoReconnect: raw.autoReconnect !== false,
                logoEnabled: raw.logoEnabled !== false,
                performanceProfile: profile,
                chunkSize: Math.min(512, Math.max(90, Number.isFinite(chunkSize) ? chunkSize : profilePreset.chunkSize)),
                chunkDelayMs: Math.min(100, Math.max(0, Number.isFinite(chunkDelayMs) ? chunkDelayMs : profilePreset.chunkDelayMs)),
                retryBaseMs: profilePreset.retryBaseMs,
                retryMaxMs: profilePreset.retryMaxMs,
            };
        }

        function buildReceiptText(payload) {
            const line = '-'.repeat(32);
            const rows = [];
            rows.push(payload.store);
            rows.push(payload.address);
            rows.push(payload.phone);
            rows.push(line);
            rows.push(`Date   : ${payload.date}`);
            rows.push(`No     : ${payload.number}`);
            rows.push(`Cashier: ${payload.cashier}`);
            rows.push(line);
            payload.items.forEach((item) => {
                rows.push(item.name);
                rows.push(`${item.qty} x ${formatCurrency(item.price)}`);
                rows.push(`Subtotal: ${formatCurrency(item.subtotal)}`);
            });
            rows.push(line);
            rows.push(`TOTAL  : ${formatCurrency(payload.total)}`);
            if (Number(payload.cash) > 0) {
                rows.push(`CASH   : ${formatCurrency(payload.cash)}`);
                rows.push(`CHANGE : ${formatCurrency(payload.change)}`);
            } else {
                rows.push(`METHOD : ${payload.method}`);
            }
            if (String(payload.method || '').toUpperCase() === 'QRIS' && payload.qrisText) {
                rows.push(line);
                rows.push('SCAN QRIS');
            }
            rows.push(line);
            rows.push(payload.footer1);
            rows.push(payload.footer2);
            rows.push('\n\n');
            return rows.join('\n');
        }

        function getStoredQueue() {
            try {
                const parsed = JSON.parse(localStorage.getItem(queueStorageKey) || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        function setStoredQueue(queue) {
            localStorage.setItem(queueStorageKey, JSON.stringify(queue));
            syncQueueInfo();
        }

        function enqueuePrintJob(payload, options = {}) {
            const queue = getStoredQueue();
            queue.push({
                id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
                payload,
                options,
                retryCount: 0,
                nextRetryAt: Date.now(),
            });
            setStoredQueue(queue);
        }

        function getStoredPrinter() {
            try {
                const parsed = JSON.parse(localStorage.getItem(printerStorageKey) || 'null');
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
                const config = receiptPayload.printerConfig || {};
                if (config.defaultPrinterId || config.defaultPrinterName) {
                    return {
                        id: config.defaultPrinterId || config.defaultPrinterName,
                        name: config.defaultPrinterName || config.defaultPrinterId,
                    };
                }
                return null;
            } catch (error) {
                return null;
            }
        }

        function saveStoredPrinter(printer) {
            localStorage.setItem(printerStorageKey, JSON.stringify(printer || null));
        }

        function syncQueueInfo() {
            const target = document.getElementById('printQueueInfo');
            if (!target) {
                return;
            }
            const pending = getStoredQueue().length;
            target.textContent = pending > 0 ? `Queue: ${pending}` : '';
        }

        function encodeEscPosText(text) {
            const encoder = new TextEncoder();
            return Array.from(encoder.encode(text));
        }

        function bytesToBase64(bytes) {
            let binary = '';
            const chunkSize = 0x8000;
            for (let index = 0; index < bytes.length; index += chunkSize) {
                const chunk = bytes.subarray(index, index + chunkSize);
                binary += String.fromCharCode(...chunk);
            }
            return btoa(binary);
        }

        function buildQrEscPosBytes(data) {
            const encoded = encodeEscPosText(data);
            const length = encoded.length + 3;
            const pL = length & 0xff;
            const pH = (length >> 8) & 0xff;
            return [
                0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00,
                0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, 0x06,
                0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x30,
                0x1D, 0x28, 0x6B, pL, pH, 0x31, 0x50, 0x30,
                ...encoded,
                0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30,
            ];
        }

        async function imageUrlToEscPosRasterBytes(url) {
            if (!url) {
                return [];
            }
            if (logoRasterCache.has(url)) {
                return logoRasterCache.get(url);
            }
            const image = await new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = `${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`;
            });
            const widthLimit = 384;
            const ratio = image.width > widthLimit ? widthLimit / image.width : 1;
            const width = Math.max(1, Math.floor(image.width * ratio));
            const height = Math.max(1, Math.floor(image.height * ratio));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(image, 0, 0, width, height);
            const source = ctx.getImageData(0, 0, width, height).data;
            const bytesPerRow = Math.ceil(width / 8);
            const raster = new Uint8Array(bytesPerRow * height);
            for (let y = 0; y < height; y += 1) {
                for (let x = 0; x < width; x += 1) {
                    const index = (y * width + x) * 4;
                    const r = source[index];
                    const g = source[index + 1];
                    const b = source[index + 2];
                    const alpha = source[index + 3];
                    const gray = 0.299 * r + 0.587 * g + 0.114 * b;
                    if (alpha > 10 && gray < 180) {
                        const byteIndex = y * bytesPerRow + (x >> 3);
                        raster[byteIndex] |= 0x80 >> (x & 7);
                    }
                }
            }
            const xL = bytesPerRow & 0xff;
            const xH = (bytesPerRow >> 8) & 0xff;
            const yL = height & 0xff;
            const yH = (height >> 8) & 0xff;
            const result = [0x1D, 0x76, 0x30, 0x00, xL, xH, yL, yH, ...Array.from(raster)];
            logoRasterCache.set(url, result);
            return result;
        }

        async function buildEscPosBytes(payload) {
            const config = getPrinterConfig();
            const bytes = [];
            bytes.push(0x1B, 0x40);
            if (config.logoEnabled && payload.logo) {
                try {
                    bytes.push(0x1B, 0x61, 0x01);
                    bytes.push(...await imageUrlToEscPosRasterBytes(payload.logo));
                    bytes.push(0x0A);
                } catch (error) {
                }
            }
            bytes.push(0x1B, 0x61, 0x00);
            bytes.push(...encodeEscPosText(buildReceiptText(payload)));
            if (String(payload.method || '').toUpperCase() === 'QRIS' && payload.qrisText) {
                bytes.push(0x1B, 0x61, 0x01);
                bytes.push(...buildQrEscPosBytes(payload.qrisText));
                bytes.push(0x0A, 0x0A, 0x1B, 0x61, 0x00);
            }
            bytes.push(0x1D, 0x56, 0x41, 0x00);
            return new Uint8Array(bytes);
        }

        async function writeChunks(characteristic, bytes) {
            const config = getPrinterConfig();
            const useWriteWithoutResponse = typeof characteristic.writeValueWithoutResponse === 'function';
            for (let index = 0; index < bytes.length; index += config.chunkSize) {
                const chunk = bytes.slice(index, index + config.chunkSize);
                if (useWriteWithoutResponse) {
                    await characteristic.writeValueWithoutResponse(chunk);
                } else {
                    await characteristic.writeValue(chunk);
                }
                if (config.chunkDelayMs > 0) {
                    await new Promise((resolve) => setTimeout(resolve, config.chunkDelayMs));
                }
            }
        }

        async function getWritableCharacteristic(server) {
            for (const serviceUuid of bluetoothServiceUuids) {
                try {
                    const service = await server.getPrimaryService(serviceUuid);
                    for (const characteristicUuid of bluetoothCharacteristicUuids) {
                        try {
                            const characteristic = await service.getCharacteristic(characteristicUuid);
                            if (characteristic.properties.write || characteristic.properties.writeWithoutResponse) {
                                return characteristic;
                            }
                        } catch (error) {
                        }
                    }
                } catch (error) {
                }
            }
            throw new Error('Karakteristik printer Bluetooth tidak ditemukan');
        }

        function printReceipt() {
            window.print();
        }

        function isLikelyWebView() {
            const ua = navigator.userAgent || '';
            if (/\bwv\b/.test(ua)) {
                return true;
            }
            if (/Android/.test(ua) && /Version\/[\d.]+/.test(ua)) {
                return true;
            }
            if (/iPhone|iPad|iPod/.test(ua) && !/Safari/.test(ua)) {
                return true;
            }
            return false;
        }

        function getAutoParam(name) {
            const value = new URLSearchParams(window.location.search).get(name);
            if (value === null) {
                return null;
            }
            return value === '1' || value === 'true' || value === 'yes';
        }

        async function selectBluetoothPrinter() {
            try {
                if (window.MobilePrinterBridge && typeof window.MobilePrinterBridge.selectBluetoothPrinter === 'function') {
                    const result = await window.MobilePrinterBridge.selectBluetoothPrinter();
                    if (result) {
                        saveStoredPrinter(typeof result === 'string' ? { id: result, name: result } : result);
                    }
                    return;
                }
                if (!('bluetooth' in navigator)) {
                    alert('Web Bluetooth tidak tersedia di perangkat ini.');
                    return;
                }
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: bluetoothServiceUuids,
                });
                saveStoredPrinter({ id: device.id, name: device.name || 'Bluetooth Printer' });
                alert(`Printer dipilih: ${device.name || device.id}`);
            } catch (error) {
                alert('Pemilihan printer dibatalkan atau gagal.');
            }
        }

        async function tryNativeBluetoothPrint(text, payload) {
            try {
                if (window.MobilePrinterBridge && typeof window.MobilePrinterBridge.printBluetooth === 'function') {
                    const result = window.MobilePrinterBridge.printBluetooth(text, JSON.stringify(payload));
                    if (result && typeof result.then === 'function') {
                        await result;
                    }
                    return true;
                }
                if (window.AndroidPrinter && typeof window.AndroidPrinter.printBluetooth === 'function') {
                    window.AndroidPrinter.printBluetooth(text);
                    return true;
                }
                if (window.Android && typeof window.Android.printBluetooth === 'function') {
                    window.Android.printBluetooth(text);
                    return true;
                }
                if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.bluetoothPrinter) {
                    window.webkit.messageHandlers.bluetoothPrinter.postMessage({
                        type: 'printBluetooth',
                        text: text,
                        payload: payload
                    });
                    return true;
                }
            } catch (error) {
            }
            return false;
        }

        async function tryNativeEscPosPrint(payload) {
            const text = buildReceiptText(payload);
            const escposBytes = await buildEscPosBytes(payload);
            const escposBase64 = bytesToBase64(escposBytes);
            const payloadWithEscPos = {
                ...payload,
                escposBase64,
            };
            try {
                if (window.MobilePrinterBridge && typeof window.MobilePrinterBridge.printBluetoothEscPos === 'function') {
                    const result = window.MobilePrinterBridge.printBluetoothEscPos(escposBase64, JSON.stringify(payloadWithEscPos));
                    if (result && typeof result.then === 'function') {
                        await result;
                    }
                    return { printed: true, text, escposBytes };
                }
                const printed = await tryNativeBluetoothPrint(text, payloadWithEscPos);
                return { printed, text, escposBytes };
            } catch (error) {
                return { printed: false, text, escposBytes };
            }
        }

        async function resolveBluetoothDevice() {
            const saved = getStoredPrinter();
            const config = getPrinterConfig();
            if (saved && navigator.bluetooth && typeof navigator.bluetooth.getDevices === 'function' && config.autoReconnect) {
                const devices = await navigator.bluetooth.getDevices();
                const matched = devices.find((item) => item.id === saved.id);
                if (matched) {
                    return matched;
                }
            }
            const requested = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: bluetoothServiceUuids
            });
            saveStoredPrinter({ id: requested.id, name: requested.name || 'Bluetooth Printer' });
            return requested;
        }

        async function printViaWebBluetooth(payload) {
            if (!('bluetooth' in navigator)) {
                return false;
            }
            let device;
            try {
                device = await resolveBluetoothDevice();
                const server = await device.gatt.connect();
                const characteristic = await getWritableCharacteristic(server);
                const bytes = payload.escposBytes instanceof Uint8Array ? payload.escposBytes : await buildEscPosBytes(payload);
                await writeChunks(characteristic, bytes);
                if (device.gatt.connected) {
                    device.gatt.disconnect();
                }
                return true;
            } catch (error) {
                if (device && device.gatt && device.gatt.connected) {
                    device.gatt.disconnect();
                }
                return false;
            }
        }

        async function dispatchPrint(payload, options = {}) {
            const selectedPrinter = getStoredPrinter();
            const payloadWithPrinter = selectedPrinter
                ? { ...payload, selectedPrinter }
                : payload;
            const nativeAttempt = await tryNativeEscPosPrint(payloadWithPrinter);
            if (nativeAttempt.printed) {
                return true;
            }
            const bluetoothPrinted = await printViaWebBluetooth({
                ...payloadWithPrinter,
                escposBytes: nativeAttempt.escposBytes,
            });
            if (bluetoothPrinted) {
                return true;
            }
            if (!options.skipFallbackPrint) {
                window.print();
            }
            return false;
        }

        async function processPrintQueue() {
            if (queueInProgress) {
                return;
            }
            queueInProgress = true;
            try {
                let queue = getStoredQueue();
                while (queue.length > 0) {
                    const next = queue[0];
                    if (next.nextRetryAt && next.nextRetryAt > Date.now()) {
                        break;
                    }
                    const ok = await dispatchPrint(next.payload, next.options || {});
                    if (ok) {
                        queue.shift();
                        setStoredQueue(queue);
                        continue;
                    }
                    next.retryCount = Number(next.retryCount || 0) + 1;
                    const config = getPrinterConfig();
                    next.nextRetryAt = Date.now() + Math.min(config.retryMaxMs, config.retryBaseMs * (2 ** Math.min(5, next.retryCount)));
                    queue[0] = next;
                    setStoredQueue(queue);
                    break;
                }
            } finally {
                queueInProgress = false;
            }
        }

        async function printBluetooth(options = {}) {
            enqueuePrintJob(receiptPayload, {
                silentSuccess: !!options.silentSuccess,
                skipFallbackPrint: !!options.skipFallbackPrint,
            });
            await processPrintQueue();
            if (!options.silentSuccess && getStoredQueue().length === 0) {
                alert('Data struk berhasil dikirim ke printer Bluetooth.');
            }
        }

        async function retryPrintQueue() {
            await processPrintQueue();
            const pending = getStoredQueue().length;
            if (pending > 0) {
                alert(`Masih ada ${pending} item di queue print.`);
            } else {
                alert('Queue print sudah kosong.');
            }
        }

        window.addEventListener('load', function () {
            syncQueueInfo();
            if (receiptPayload.logo) {
                imageUrlToEscPosRasterBytes(receiptPayload.logo).catch(() => null);
            }
            processPrintQueue();
            const autoPrint = new URLSearchParams(window.location.search).get('autoprint');
            const autoBluetooth = getAutoParam('autobluetooth');
            const shouldAutoBluetooth = autoBluetooth === true || (autoBluetooth === null && isLikelyWebView());
            if (autoPrint !== '0') {
                if (shouldAutoBluetooth) {
                    printBluetooth({ silentSuccess: true, skipFallbackPrint: true });
                    return;
                }
                window.print();
            }
        });
        window.addEventListener('online', processPrintQueue);
        window.addEventListener('focus', processPrintQueue);
        window.selectBluetoothPrinter = selectBluetoothPrinter;
        window.retryPrintQueue = retryPrintQueue;
        window.printBluetooth = printBluetooth;
        window.printReceipt = printReceipt;
    </script>
</head>
<body>
    <div class="receipt-actions">
        <button type="button" onclick="printReceipt()">Print</button>
        <button type="button" onclick="printBluetooth()">Print Bluetooth</button>
        <span id="printQueueInfo"></span>
    </div>
    <div class="header">
        @if(!empty($receiptStoreLogo))
        <img src="{{ $receiptStoreLogo }}" alt="{{ $receiptStoreName }}" class="header-logo">
        @endif
        <h2>{{ $receiptStoreName }}</h2>
        <p>{{ $receiptStoreAddress }}</p>
        <p>{{ $receiptStorePhoneLabel }}</p>
    </div>
    
    <div class="divider"></div>
    <div>
        Date: {{ $transaction->created_at->format('d/m/Y H:i') }}<br>
        No: {{ $transaction->transaction_number }}<br>
        Cashier: {{ $transaction->user->name ?? 'Admin' }}
    </div>
    <div class="divider"></div>

    @foreach($transaction->items as $item)
    <div style="margin-bottom: 5px;">
        <div>{{ $item->product_name }}</div>
        <div class="item">
            <span>{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
            <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="total-section">
        <div class="item">
            <strong>TOTAL</strong>
            <strong>{{ number_format($transaction->total_amount, 0, ',', '.') }}</strong>
        </div>
        @if($transaction->cash_amount > 0)
        <div class="item">
            <span>CASH</span>
            <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
        </div>
        <div class="item">
            <span>CHANGE</span>
            <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
        </div>
        @else
        <div class="item">
            <span>METHOD</span>
            <span>{{ strtoupper($transaction->payment_method) }}</span>
        </div>
        @if(strtoupper($transaction->payment_method) === 'QRIS' && !empty($posQrisText))
        <div class="item">
            <span>QRIS</span>
            <span>READY</span>
        </div>
        @endif
        @endif
    </div>

    <div class="footer">
        <p>Thank you for your visit!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>
</body>
</html>
