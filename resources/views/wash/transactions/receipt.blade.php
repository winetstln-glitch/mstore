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
    </style>
    <script>
        const receiptPayload = {{ Js::from([
            'store' => 'AUTO WASH',
            'address' => 'Jl. Contoh Bersih No. 123',
            'phone' => 'Telp: 0812-0000-0000',
            'date' => $transaction->created_at->format('d/m/Y H:i'),
            'number' => $transaction->transaction_number,
            'queue' => $transaction->queue_number ?? null,
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
            'footer1' => 'Terima kasih! Kendaraan Anda bersih kembali.',
            'footer2' => 'Simpan struk ini sebagai bukti layanan.',
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

        function formatCurrency(value) {
            return Number(value || 0).toLocaleString('id-ID');
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
            if (payload.queue) {
                rows.push(`Queue  : #${payload.queue}`);
            }
            rows.push(`Cashier: ${payload.cashier}`);
            rows.push(line);
            payload.items.forEach((item) => {
                rows.push(item.name);
                rows.push(`${item.qty} x ${formatCurrency(item.price)}`);
                rows.push(`Subtotal: ${formatCurrency(item.subtotal)}`);
            });
            rows.push(line);
            if (Number(payload.discount) > 0) {
                rows.push(`DISCOUNT: -${formatCurrency(payload.discount)}`);
            }
            rows.push(`TOTAL  : ${formatCurrency(payload.total)}`);
            if (Number(payload.cash) > 0) {
                rows.push(`CASH   : ${formatCurrency(payload.cash)}`);
                rows.push(`CHANGE : ${formatCurrency(payload.change)}`);
            } else {
                rows.push(`METHOD : ${payload.method}`);
            }
            rows.push(line);
            rows.push(payload.footer1);
            rows.push(payload.footer2);
            rows.push('\n\n');
            return rows.join('\n');
        }

        function encodeEscPos(text) {
            const encoder = new TextEncoder();
            const content = encoder.encode(text);
            const bytes = new Uint8Array(3 + content.length + 5);
            bytes.set([0x1B, 0x40, 0x1B], 0);
            bytes[3] = 0x61;
            bytes[4] = 0x00;
            bytes.set(content, 5);
            const end = bytes.length - 3;
            bytes[end] = 0x1D;
            bytes[end + 1] = 0x56;
            bytes[end + 2] = 0x41;
            return bytes;
        }

        async function writeChunks(characteristic, bytes, chunkSize = 180) {
            for (let index = 0; index < bytes.length; index += chunkSize) {
                const chunk = bytes.slice(index, index + chunkSize);
                await characteristic.writeValue(chunk);
                await new Promise((resolve) => setTimeout(resolve, 30));
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

        async function printBluetooth() {
            if (!('bluetooth' in navigator)) {
                alert('Browser ini belum mendukung Web Bluetooth.');
                return;
            }

            let device;
            try {
                device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: bluetoothServiceUuids
                });

                const server = await device.gatt.connect();
                const characteristic = await getWritableCharacteristic(server);
                const text = buildReceiptText(receiptPayload);
                const bytes = encodeEscPos(text);
                await writeChunks(characteristic, bytes);

                if (device.gatt.connected) {
                    device.gatt.disconnect();
                }

                alert('Data struk berhasil dikirim ke printer Bluetooth.');
            } catch (error) {
                if (device?.gatt?.connected) {
                    device.gatt.disconnect();
                }
                alert('Gagal kirim ke printer Bluetooth. Sistem membuka dialog print sebagai cadangan.');
                window.print();
            }
        }

        window.addEventListener('load', function() {
            const autoPrint = new URLSearchParams(window.location.search).get('autoprint');
            if (autoPrint !== '0') {
                window.print();
            }
        });
    </script>
</head>
<body>
    <div class="receipt-actions">
        <button type="button" onclick="printReceipt()">Print</button>
        <button type="button" onclick="printBluetooth()">Print Bluetooth</button>
    </div>
    <div class="header">
        <h2>AUTO WASH</h2>
        <p>Jl. Contoh Bersih No. 123</p>
        <p>Telp: 0812-0000-0000</p>
    </div>
    
    <div class="divider"></div>
    <div>
        Date: {{ $transaction->created_at->format('d/m/Y H:i') }}<br>
        No: {{ $transaction->transaction_number }}<br>
        @if(!empty($transaction->queue_number))
        Queue: #{{ $transaction->queue_number }}<br>
        @endif
        Cashier: {{ $transaction->user->name ?? 'Admin' }}
    </div>
    <div class="divider"></div>

    @foreach($transaction->items as $item)
    <div style="margin-bottom: 5px;">
        <div>{{ $item->service_name }}</div>
        <div class="item">
            <span>{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
            <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="total-section">
        @if(($transaction->discount_amount ?? 0) > 0)
        <div class="item">
            <span>DISCOUNT</span>
            <span>-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="item">
            <strong>TOTAL</strong>
            <strong>{{ number_format($transaction->total_amount, 0, ',', '.') }}</strong>
        </div>
        @if(($transaction->cash_amount ?? 0) > 0)
        <div class="item">
            <span>CASH</span>
            <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
        </div>
        <div class="item">
            <span>KEMBALIAN</span>
            <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
        </div>
        @else
        <div class="item">
            <span>METHOD</span>
            <span>{{ strtoupper($transaction->payment_method) }}</span>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Terima kasih! Kendaraan Anda bersih kembali.</p>
        <p>Simpan struk ini sebagai bukti layanan.</p>
    </div>
</body>
</html>
