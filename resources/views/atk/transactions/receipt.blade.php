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
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>ATK STORE</h2>
        <p>Jl. Raya Contoh No. 123</p>
        <p>Telp: 0812-3456-7890</p>
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
        @endif
    </div>

    <div class="footer">
        <p>Thank you for your visit!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>
</body>
</html>
