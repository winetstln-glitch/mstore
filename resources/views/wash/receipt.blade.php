<!DOCTYPE html>
<html>
<head>
    <title>Struk Transaksi #{{ $transaction->transaction_code }}</title>
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
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .item {
            margin-bottom: 5px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
        }
        .totals {
            margin-top: 10px;
            text-align: right;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h3 style="margin:0">M-STORE WASH</h3>
        <p style="margin:0">Jasa Cuci Mobil & Motor</p>
        <p style="margin:0">{{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="divider"></div>

    <div>
        Ref: {{ $transaction->transaction_code }}<br>
        Kasir: {{ $transaction->user->name }}<br>
        @if($transaction->customer_name)
        Plg: {{ $transaction->customer_name }}<br>
        @endif
        @if($transaction->plate_number)
        Plat: {{ $transaction->plate_number }}
        @endif
    </div>

    <div class="divider"></div>

    @foreach($transaction->items as $item)
    <div class="item">
        <div>{{ $item->service->name }}</div>
        <div class="item-row">
            <span>{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
            <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="totals">
        <div class="item-row">
            <strong>Total:</strong>
            <strong>{{ number_format($transaction->total_amount, 0, ',', '.') }}</strong>
        </div>
        <div class="item-row">
            <span>Bayar:</span>
            <span>{{ number_format($transaction->amount_paid, 0, ',', '.') }}</span>
        </div>
        <div class="item-row">
            <span>Kembali:</span>
            <span>{{ number_format($transaction->amount_paid - $transaction->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Terima Kasih atas Kunjungan Anda</p>
    </div>
</body>
</html>
