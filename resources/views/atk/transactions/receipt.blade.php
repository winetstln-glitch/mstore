<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaction->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 58mm; /* Standard thermal width, adjust to 80mm if needed */
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .info {
            font-size: 10px;
            margin-bottom: 5px;
        }
        .info table {
            width: 100%;
        }
        .info td {
            vertical-align: top;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .items th {
            text-align: left;
            border-bottom: 1px dashed #000;
        }
        .items td {
            padding: 2px 0;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 5px;
            width: 100%;
            font-size: 11px;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
        }
        
        @media print {
            body {
                width: auto; /* Allow printer to control width */
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        
        .no-print {
            margin-bottom: 10px;
            text-align: center;
        }
        .btn {
            background: #333;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            text-decoration: none;
            font-family: sans-serif;
            font-size: 12px;
            display: inline-block;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" class="btn">Cetak Struk</button>
        <button onclick="window.close()" class="btn" style="background: #dc3545; margin-left: 5px;">Tutup</button>
    </div>

    <div class="header">
        <h2>{{ config('app.name', 'MStore') }}</h2>
        <p>{{ config('app.address', 'Jalan Raya No. 123, Kota Internet') }}</p>
        <p>Telp: {{ config('app.phone', '0812-3456-7890') }}</p>
    </div>

    <div class="divider"></div>

    <div class="info">
        <table>
            <tr>
                <td>No. Invoice</td>
                <td class="text-right">{{ $transaction->invoice_number }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td class="text-right">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="text-right">{{ $transaction->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Pelanggan</td>
                <td class="text-right">{{ $transaction->customer_name ?? 'Guest' }}</td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $item)
            <tr>
                <td colspan="4">{{ $item->product->name }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals">
        <tr>
            <td>Total</td>
            <td class="text-right" style="font-weight: bold; font-size: 14px;">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bayar ({{ ucfirst($transaction->payment_method) }})</td>
            <td class="text-right">Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="text-right">Rp {{ number_format($transaction->amount_paid - $transaction->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        <p>Terima Kasih atas Kunjungan Anda</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
        <p>Powered by MStore System</p>
    </div>

</body>
</html>
