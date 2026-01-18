<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Pengurus</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 10px 0 4px; }
        .meta { font-size: 11px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-border td { border: none; }
    </style>
</head>
<body>
    <h1>Laporan Keuangan Pengurus</h1>
    <div class="meta">
        @php
            $period = $month
                ? \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y')
                : 'Semua Periode';
        @endphp
        <div>Periode: {{ $period }}</div>
        <div>Tanggal Cetak: {{ now()->format('d-m-Y H:i') }}</div>
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
            <th class="text-right">{{ number_format($totalRevenue, 0, ',', '.') }}</th>
        </tr>
    </table>

    <h2>Komisi dan Pengeluaran</h2>
    <table>
        <tr>
            <th>Item</th>
            <th class="text-right">Jumlah</th>
        </tr>
        <tr>
            <td>Komisi Pengurus (±15%)</td>
            <td class="text-right">-{{ number_format($coordCommission, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Sisa Setelah Komisi</td>
            <td class="text-right">{{ number_format($afterCommission, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pengeluaran Transportasi</td>
            <td class="text-right">-{{ number_format($transportExpenses, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pengeluaran Konsumsi</td>
            <td class="text-right">-{{ number_format($consumptionExpenses, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pengeluaran Perbaikan</td>
            <td class="text-right">-{{ number_format($repairExpenses, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Total Pengeluaran Pengurus</strong></td>
            <td class="text-right"><strong>-{{ number_format($operatingExpenses, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <th>Total Sisa Disetor ke Perusahaan</th>
            <th class="text-right">{{ number_format($depositToCompany, 0, ',', '.') }}</th>
        </tr>
    </table>
</body>
</html>
