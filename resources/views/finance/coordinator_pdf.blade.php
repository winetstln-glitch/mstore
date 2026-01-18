<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan Coordinator</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header h3 { margin: 5px 0; }
        .header p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #e74a3b; }
        .text-success { color: #1cc88a; }
        .summary-box { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background: #fafafa; border-radius: 5px; }
        .summary-item { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Keuangan Coordinator</h2>
        <h3>{{ $coordinator->name }}</h3>
        <p>Wilayah: {{ $coordinator->region->name ?? '-' }}</p>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    <div class="summary-box">
        <table style="border: none; margin: 0; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; vertical-align: top; width: 50%;">
                    <div class="summary-item"><strong>Total Pendapatan:</strong> Rp {{ number_format($grossRevenue, 0, ',', '.') }}</div>
                    <div class="summary-item"><strong>Komisi (15%):</strong> Rp {{ number_format($commission, 0, ',', '.') }}</div>
                    <div class="summary-item"><strong>ISP Share (25%):</strong> Rp {{ number_format($ispShare, 0, ',', '.') }}</div>
                </td>
                <td style="border: none; vertical-align: top; width: 50%;">
                    <div class="summary-item"><strong>Dana Alat (15%):</strong> Rp {{ number_format($toolFund, 0, ',', '.') }}</div>
                    <div class="summary-item"><strong>Pengeluaran Lain:</strong> Rp {{ number_format($expenses, 0, ',', '.') }}</div>
                    <div class="summary-item" style="font-size: 14px; margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px;">
                        <strong>Saldo Bersih:</strong> 
                        <span class="{{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($netBalance, 0, ',', '.') }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <h3>Rincian Transaksi</h3>
    <table>
        <thead>
            <tr>
                <th width="80">Tanggal</th>
                <th width="60">Tipe</th>
                <th width="100">Kategori</th>
                <th>Deskripsi</th>
                <th width="100" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
            <tr>
                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                <td>{{ $transaction->type == 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                <td>{{ $transaction->category }}</td>
                <td>{{ $transaction->description }}</td>
                <td class="text-right {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($transaction->amount, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada transaksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 10px; color: #777;">
        Dicetak pada: {{ now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>
