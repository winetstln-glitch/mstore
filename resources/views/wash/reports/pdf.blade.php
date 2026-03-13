<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Wash</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f2f2f2; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Cuci Kendaraan</h1>
    <div class="muted">Tanggal: {{ $date }} | Bulan: {{ $month }}</div>

    <table>
        <tr>
            <th>Ringkasan Harian</th>
            <th class="right">Nominal</th>
            <th>Ringkasan Bulanan</th>
            <th class="right">Nominal</th>
        </tr>
        <tr>
            <td>Pemasukan</td><td class="right">{{ number_format($dailyIncome,0,',','.') }}</td>
            <td>Pemasukan</td><td class="right">{{ number_format($monthlyIncome,0,',','.') }}</td>
        </tr>
        <tr>
            <td>Pengeluaran</td><td class="right">{{ number_format($dailyExpense,0,',','.') }}</td>
            <td>Pengeluaran</td><td class="right">{{ number_format($monthlyExpense,0,',','.') }}</td>
        </tr>
        <tr>
            <td><strong>Laba</strong></td><td class="right"><strong>{{ number_format($dailyIncome-$dailyExpense,0,',','.') }}</strong></td>
            <td><strong>Laba</strong></td><td class="right"><strong>{{ number_format($monthlyIncome-$monthlyExpense,0,',','.') }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr><th colspan="4">Rincian Pemasukan Harian</th></tr>
            <tr>
                <th>Waktu</th>
                <th>No Transaksi</th>
                <th>Metode</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyIncomeRows as $r)
            <tr>
                <td>{{ $r->created_at->format('H:i') }}</td>
                <td>{{ $r->transaction_number }}</td>
                <td>{{ strtoupper($r->payment_method) }}</td>
                <td class="right">{{ number_format($r->total_amount,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="3">Rincian Pengeluaran Harian</th></tr>
            <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyExpenseRows as $r)
            <tr>
                <td>{{ \Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                <td>{{ $r->description }}</td>
                <td class="right">{{ number_format($r->amount,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
