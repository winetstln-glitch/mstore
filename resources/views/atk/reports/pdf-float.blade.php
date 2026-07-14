<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Float Account</title>
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
        <h1>Laporan Float Account</h1>
        @if($selectedAccount)
            <p>Akun: {{ $selectedAccount->name }}</p>
        @endif
        <p>Periode: {{ $start }} - {{ $end }}</p>
    </div>

    @if($selectedAccount)
        <table class="mb-4">
            <tr>
                <td><strong>Saldo Awal</strong></td>
                <td class="text-right">Rp {{ number_format($startBalance, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Masuk</strong></td>
                <td class="text-right">Rp {{ number_format($totalIn, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Keluar</strong></td>
                <td class="text-right">Rp {{ number_format($totalOut, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Saldo Akhir</strong></td>
                <td class="text-right">Rp {{ number_format($endBalance, 0, ',', '.') }}</td>
            </tr>
        </table>

        <h3>Detail Transaksi Float</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 15%;">Referensi</th>
                    <th style="width: 30%;">Keterangan</th>
                    <th style="width: 10%;" class="text-right">Debit</th>
                    <th style="width: 10%;" class="text-right">Kredit</th>
                    <th style="width: 15%;" class="text-right">Saldo Berjalan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $index => $transaction)
                @php
                    $isIncoming = in_array($transaction->transaction_type, ['deposit', 'topup', 'transfer_in']);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaction->transaction_type }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td class="text-right">{{ $isIncoming ? 'Rp ' . number_format($transaction->amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ !$isIncoming ? 'Rp ' . number_format($transaction->amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->running_balance, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-center">Pilih akun float terlebih dahulu.</p>
    @endif
</body>
</html>
