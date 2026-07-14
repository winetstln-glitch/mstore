<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Dana Talangan</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 20px; }
        .mt-4 { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="text-center mb-4">
        <h1>Laporan Dana Talangan</h1>
        <p>Periode: {{ ($start instanceof \Illuminate\Support\Carbon ? $start->format('d/m/Y') : $start) }} - {{ ($end instanceof \Illuminate\Support\Carbon ? $end->format('d/m/Y') : $end) }}</p>
    </div>

    <table class="mb-4">
        <tr>
            <td><strong>Dana Masuk</strong></td>
            <td class="text-right">Rp {{ number_format($incoming, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Pengembalian</strong></td>
            <td class="text-right">Rp {{ number_format($outgoing, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Saldo Aktif</strong></td>
            <td class="text-right">Rp {{ number_format($currentBalance, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h3>Detail Transaksi</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 15%;">Kode</th>
                <th style="width: 15%;">Jenis</th>
                <th style="width: 15%;" class="text-right">Jumlah</th>
                <th style="width: 15%;" class="text-right">Saldo</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($funds as $index => $fund)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $fund->transaction_date->format('d/m/Y') }}</td>
                <td>{{ $fund->transaction_code }}</td>
                <td>{{ $fund->type }}</td>
                <td class="text-right">Rp {{ number_format($fund->amount, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($fund->balance, 0, ',', '.') }}</td>
                <td>{{ $fund->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
