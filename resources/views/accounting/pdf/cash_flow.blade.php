<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
    </head>
<body>
    <h3 style="margin:0 0 10px 0;">Laporan Arus Kas (Metode Langsung)</h3>
    <p style="margin:0 0 10px 0;">Periode: {{ $start ?? '-' }} s/d {{ $end ?? '-' }}</p>
    <table>
        <tbody>
            <tr><th colspan="2">Arus Kas dari Aktivitas Operasi</th></tr>
            <tr><td>Penerimaan dari pelanggan</td><td class="right">{{ number_format($operatingIn,0,',','.') }}</td></tr>
            <tr><td>Pembayaran beban</td><td class="right">({{ number_format($operatingOut,0,',','.') }})</td></tr>
            <tr><th>Netto Operasi</th><th class="right">{{ number_format($netOperating,0,',','.') }}</th></tr>
            <tr><th colspan="2">Arus Kas dari Aktivitas Investasi</th></tr>
            <tr><td>Penerimaan penjualan aset</td><td class="right">{{ number_format($investingIn,0,',','.') }}</td></tr>
            <tr><td>Pembelian aset</td><td class="right">({{ number_format($investingOut,0,',','.') }})</td></tr>
            <tr><th>Netto Investasi</th><th class="right">{{ number_format($netInvesting,0,',','.') }}</th></tr>
            <tr><th colspan="2">Arus Kas dari Aktivitas Pendanaan</th></tr>
            <tr><td>Penerimaan pendanaan</td><td class="right">{{ number_format($financingIn,0,',','.') }}</td></tr>
            <tr><td>Pengembalian/Distribusi</td><td class="right">({{ number_format($financingOut,0,',','.') }})</td></tr>
            <tr><th>Netto Pendanaan</th><th class="right">{{ number_format($netFinancing,0,',','.') }}</th></tr>
            <tr><th>Kenaikan (Penurunan) Kas</th><th class="right">{{ number_format($netChange,0,',','.') }}</th></tr>
            @if(!is_null($openingCash))
            <tr><td>Saldo Awal Kas</td><td class="right">{{ number_format($openingCash,0,',','.') }}</td></tr>
            @endif
            <tr><td>Saldo Akhir Kas</td><td class="right">{{ number_format($closingCash,0,',','.') }}</td></tr>
        </tbody>
    </table>
</body>
</html>
