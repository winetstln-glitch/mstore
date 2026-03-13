<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaksi Wash</title>
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
        .badge {
            padding: 2px 4px;
            border-radius: 4px;
            background-color: #eee;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <h1>Laporan Transaksi Cuci (Wash)</h1>
    <div class="period">
        Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Tanggal</th>
                <th style="width: 15%">No. Transaksi</th>
                <th style="width: 15%">Pelanggan</th>
                <th style="width: 10%">Kendaraan</th>
                <th style="width: 25%">Layanan</th>
                <th style="width: 10%" class="text-right">Total</th>
                <th style="width: 10%" class="text-center">Metode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
                <tr>
                    <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $trx->transaction_number }}</td>
                    <td>{{ $trx->user->name ?? ($trx->customer_name ?? 'Pelanggan Umum') }}</td>
                    <td>{{ $trx->vehicle_type }} {{ $trx->vehicle_plate ? '('.$trx->vehicle_plate.')' : '' }}</td>
                    <td>
                        @foreach($trx->items as $item)
                            <div>- {{ $item->service_name }}</div>
                        @endforeach
                    </td>
                    <td class="text-right">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                    <td class="text-center">{{ ucfirst($trx->payment_method) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data transaksi.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><strong>Total Keseluruhan</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($transactions->sum('total_amount'), 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
