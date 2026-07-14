<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
    </head>
<body>
    <h3 style="margin:0 0 10px 0;">Laporan Laba Rugi</h3>
    <p style="margin:0 0 10px 0;">Periode: {{ $start ?? '-' }} s/d {{ $end ?? '-' }}</p>
    <h4 style="margin: 10px 0 6px 0;">Pendapatan</h4>
    <table>
        <thead><tr><th>Kode</th><th>Nama</th><th class="right">Jumlah</th></tr></thead>
        <tbody>
            @foreach($revenues as $r)
            <tr><td>{{ $r->code }}</td><td>{{ $r->name }}</td><td class="right">{{ number_format($r->amount,0,',','.') }}</td></tr>
            @endforeach
            <tr><th colspan="2" class="right">Total Pendapatan</th><th class="right">{{ number_format($totalRevenue,0,',','.') }}</th></tr>
        </tbody>
    </table>
    <h4 style="margin: 16px 0 6px 0;">Beban</h4>
    <table>
        <thead><tr><th>Kode</th><th>Nama</th><th class="right">Jumlah</th></tr></thead>
        <tbody>
            @foreach($expenses as $e)
            <tr><td>{{ $e->code }}</td><td>{{ $e->name }}</td><td class="right">{{ number_format($e->amount,0,',','.') }}</td></tr>
            @endforeach
            <tr><th colspan="2" class="right">Total Beban</th><th class="right">{{ number_format($totalExpense,0,',','.') }}</th></tr>
        </tbody>
    </table>
    <h3 style="text-align:right; margin-top: 16px;">Laba Bersih: {{ number_format($netIncome,0,',','.') }}</h3>
</body>
</html>
