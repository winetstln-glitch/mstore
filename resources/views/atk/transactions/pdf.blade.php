<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ATK Transactions</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 4px;
        }
        .period {
            text-align: center;
            margin-bottom: 12px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        th {
            background-color: #f0f0f0;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Laporan Transaksi ATK</h1>
    <div class="period">
        Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Tanggal</th>
                <th style="width: 15%">No. Transaksi</th>
                <th style="width: 15%">Pelanggan</th>
                <th style="width: 35%">Item</th>
                <th style="width: 10%" class="text-right">Total</th>
                <th style="width: 10%" class="text-center">Metode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
                <tr>
                    <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $trx->transaction_number }}</td>
                    <td>{{ $trx->user->name ?? 'Guest' }}</td>
                    <td>
                        @foreach($trx->items as $item)
                            <div>- {{ $item->product_name }} ({{ $item->quantity }})</div>
                        @endforeach
                    </td>
                    <td class="text-right">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                    <td class="text-center">{{ ucfirst($trx->payment_method) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data transaksi.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>Grand Total</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($transactions->sum('total_amount'), 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
