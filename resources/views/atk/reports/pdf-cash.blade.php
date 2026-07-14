<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kas ATK</title>
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
        <h1>Laporan Kas ATK</h1>
        <p>Periode: {{ ($start instanceof \Illuminate\Support\Carbon ? $start->format('d/m/Y') : $start) }} - {{ ($end instanceof \Illuminate\Support\Carbon ? $end->format('d/m/Y') : $end) }}</p>
    </div>

    <table class="mb-4">
        <tr>
            <td><strong>Saldo Awal</strong></td>
            <td class="text-right">Rp {{ number_format($startBalance, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Kas Masuk</strong></td>
            <td class="text-right">Rp {{ number_format($incoming, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Kas Keluar</strong></td>
            <td class="text-right">Rp {{ number_format($outgoing, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Saldo Akhir</strong></td>
            <td class="text-right">Rp {{ number_format($endBalance, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h3>Detail Pergerakan Kas</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 15%;">Kategori</th>
                <th style="width: 30%;">Keterangan</th>
                <th style="width: 10%;" class="text-right">Masuk</th>
                <th style="width: 10%;" class="text-right">Keluar</th>
                <th style="width: 15%;" class="text-right">Saldo Berjalan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $index => $movement)
            @php
                $isIncoming = $movement->direction === 'in';
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $movement->occurred_at ? $movement->occurred_at->format('d/m/Y H:i') : $movement->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $movement->movement_type }}</td>
                <td>{{ $movement->description }}</td>
                <td class="text-right">{{ $isIncoming ? 'Rp ' . number_format($movement->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ !$isIncoming ? 'Rp ' . number_format($movement->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">Rp {{ number_format($movement->running_balance, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
