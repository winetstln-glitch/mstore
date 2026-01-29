<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pendapatan Material</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .meta { font-size: 10px; text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background: #f0f0f0; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan Pendapatan Material</h1>
    <div class="meta">
        <div>Periode: {{ $startDate }} s/d {{ $endDate }}</div>
        <div>Tanggal Cetak: {{ now()->format('d-m-Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pengurus</th>
                <th>Nama Barang</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Total</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
            <tr>
                <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $t->coordinator->name ?? '-' }}</td>
                <td>{{ $t->item->name ?? '-' }}</td>
                <td class="text-right">{{ $t->quantity }}</td>
                <td class="text-right">{{ number_format($t->item->price ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format(($t->quantity * ($t->item->price ?? 0)), 0, ',', '.') }}</td>
                <td>{{ $t->notes }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">TOTAL GROSS</th>
                <th class="text-right">{{ number_format($totalQuantity, 0, ',', '.') }}</th>
                <th></th>
                <th class="text-right">{{ number_format($totalValue, 0, ',', '.') }}</th>
                <th></th>
            </tr>
            <tr>
                <th colspan="5" class="text-right">Komisi Pengurus ({{ $commissionRate }}%)</th>
                <th class="text-right">-{{ number_format($commissionAmount, 0, ',', '.') }}</th>
                <th></th>
            </tr>
            <tr>
                <th colspan="5" class="text-right">NET TOTAL</th>
                <th class="text-right">{{ number_format($netTotal, 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>