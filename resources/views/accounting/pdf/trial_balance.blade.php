<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neraca Saldo</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
    </head>
<body>
    <h3 style="margin:0 0 10px 0;">Neraca Saldo</h3>
    <p style="margin:0 0 10px 0;">Periode: {{ $start ?? '-' }} s/d {{ $end ?? '-' }}</p>
    <table>
        <thead>
            <tr><th>Kode</th><th>Nama Akun</th><th class="right">Debit</th><th class="right">Kredit</th></tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td>{{ $r->code }}</td>
                <td>{{ $r->name }}</td>
                <td class="right">{{ number_format($r->debit,0,',','.') }}</td>
                <td class="right">{{ number_format($r->credit,0,',','.') }}</td>
            </tr>
            @endforeach
            <tr>
                <th colspan="2" class="right">Total</th>
                <th class="right">{{ number_format($totalDebit,0,',','.') }}</th>
                <th class="right">{{ number_format($totalCredit,0,',','.') }}</th>
            </tr>
        </tbody>
    </table>
</body>
</html>
