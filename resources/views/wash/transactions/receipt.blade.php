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
    <script>
        window.addEventListener('load', function() {
            window.print();
            setTimeout(function() { window.close(); }, 500);
        });
    </script>
</head>
<body>
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
