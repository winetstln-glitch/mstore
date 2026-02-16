<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neraca</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
    </head>
<body>
    <h3 style="margin:0 0 10px 0;">Neraca</h3>
    <p style="margin:0 0 10px 0;">Periode: {{ $start ?? '-' }} s/d {{ $end ?? '-' }}</p>
    <table>
        <thead><tr><th colspan="3">Aset</th></tr></thead>
        <tbody>
            <tr><th>Kode</th><th>Nama</th><th class="right">Jumlah</th></tr>
            @foreach($assets as $a)
            <tr><td>{{ $a->code }}</td><td>{{ $a->name }}</td><td class="right">{{ number_format($a->amount,0,',','.') }}</td></tr>
            @endforeach
            <tr><th colspan="2" class="right">Total Aset</th><th class="right">{{ number_format($totalAssets,0,',','.') }}</th></tr>
        </tbody>
    </table>
    <table style="margin-top: 14px;">
        <thead><tr><th colspan="3">Kewajiban</th></tr></thead>
        <tbody>
            <tr><th>Kode</th><th>Nama</th><th class="right">Jumlah</th></tr>
            @foreach($liabilities as $l)
            <tr><td>{{ $l->code }}</td><td>{{ $l->name }}</td><td class="right">{{ number_format($l->amount,0,',','.') }}</td></tr>
            @endforeach
            <tr><th colspan="2" class="right">Total Kewajiban</th><th class="right">{{ number_format($totalLiabilities,0,',','.') }}</th></tr>
        </tbody>
    </table>
    <table style="margin-top: 14px;">
        <thead><tr><th colspan="3">Ekuitas</th></tr></thead>
        <tbody>
            <tr><th>Kode</th><th>Nama</th><th class="right">Jumlah</th></tr>
            @foreach($equity as $e)
            <tr><td>{{ $e->code }}</td><td>{{ $e->name }}</td><td class="right">{{ number_format($e->amount,0,',','.') }}</td></tr>
            @endforeach
            <tr><td>3201</td><td>Laba Berjalan</td><td class="right">{{ number_format($netIncome,0,',','.') }}</td></tr>
            <tr><th colspan="2" class="right">Total Ekuitas</th><th class="right">{{ number_format($totalEquity,0,',','.') }}</th></tr>
        </tbody>
    </table>
    <h4 style="text-align:right; margin-top: 16px;">Aset: {{ number_format($totalAssets,0,',','.') }} | Kewajiban+Ekuitas: {{ number_format($rhs,0,',','.') }}</h4>
</body>
</html>
