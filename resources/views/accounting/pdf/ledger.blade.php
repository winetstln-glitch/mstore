<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Besar</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
    </head>
<body>
    <h3 style="margin:0 0 10px 0;">Buku Besar</h3>
    <p style="margin:0 0 10px 0;">Periode: {{ $start ?? '-' }} s/d {{ $end ?? '-' }}</p>
    @if($selected)
    <p style="margin:0 0 10px 0;">Akun: {{ $selected->code }} - {{ $selected->name }}</p>
    <table>
        <thead><tr><th>Tanggal</th><th>No. Jurnal</th><th class="right">Debit</th><th class="right">Kredit</th><th>Keterangan</th></tr></thead>
        <tbody>
            @foreach($entries as $e)
            <tr>
                <td>{{ \Carbon\Carbon::parse($e->date)->format('Y-m-d') }}</td>
                <td>{{ $e->journal_no }}</td>
                <td class="right">{{ number_format($e->debit,0,',','.') }}</td>
                <td class="right">{{ number_format($e->credit,0,',','.') }}</td>
                <td>{{ $e->memo }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>Pilih akun untuk menampilkan buku besar.</p>
    @endif
</body>
</html>
