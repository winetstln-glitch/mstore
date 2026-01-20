<!DOCTYPE html>
<html lang="en">
<head>
    <title>Laporan Keuangan Pengurus</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 4px;
        }
        h2 {
            font-size: 13px;
            margin: 8px 0 4px;
        }
        .meta {
            font-size: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-danger {
            color: #e74a3b;
        }
        .text-success {
            color: #1cc88a;
        }
        .table-compact th,
        .table-compact td {
            padding: 3px 4px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <h1>Laporan Keuangan Pengurus</h1>
    <div class="meta">
        <div>Pengurus: {{ $coordinator->name }}</div>
        <div>Wilayah: {{ $coordinator->region->name ?? '-' }}</div>
        <div>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
        <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
    </div>

    <h2>Ringkasan Pendapatan</h2>
    <table>
        <tr>
            <th>Item</th>
            <th class="text-right">Jumlah</th>
        </tr>
        <tr>
            <td>Pendapatan Member</td>
            <td class="text-right">{{ number_format($memberIncome, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pendapatan Voucher</td>
            <td class="text-right">{{ number_format($voucherIncome, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Total Pendapatan</th>
            <th class="text-right">{{ number_format($grossRevenue, 0, ',', '.') }}</th>
        </tr>
    </table>

    <h2>Komisi dan Pengeluaran</h2>
    <table>
        <tr>
            <th>Item</th>
            <th class="text-right">Jumlah</th>
        </tr>
        <tr>
            <td>Komisi Pengurus</td>
            <td class="text-right">-{{ number_format($commission, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pengeluaran Pengurus</td>
            <td class="text-right">-{{ number_format($expenses, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Total Sisa Disetor ke Perusahaan</th>
            <th class="text-right">{{ number_format($netBalance, 0, ',', '.') }}</th>
        </tr>
    </table>

    @if(isset($investorDetails) && $investorDetails->count() > 0)
    <h2>Uang Kas Investor</h2>
    <table class="table-compact">
        <thead>
            <tr>
                <th>Investor</th>
                <th class="text-right">Uang Kas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investorDetails as $row)
            <tr>
                <td>{{ $row->investor_name }}</td>
                <td class="text-right">{{ number_format($row->cash_fund, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h2>Rincian Transaksi</h2>
    <table class="table-compact">
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

    <div style="margin-top: 20px; text-align: right; font-size: 9px; color: #777;">
        Dicetak pada: {{ now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>
