<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Neraca Awal</title>
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
            background: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .table-compact th,
        .table-compact td {
            padding: 3px 4px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <h1>Neraca Awal</h1>
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
            <th>Uraian</th>
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
            <th>Uraian</th>
            <th class="text-right">Jumlah</th>
        </tr>
        <tr>
            <td>Komisi Pengurus (±{{ $coordRate }}%)</td>
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

    @if(!empty($coordinatorSummaries))
        <h2>Ringkasan per Pengurus</h2>
        <table class="table-compact">
            <tr>
                <th>No</th>
                <th>Pengurus</th>
                <th class="text-right">Pendapatan</th>
                <th class="text-right">Komisi Pengurus</th>
                <th class="text-right">Pengeluaran Pengurus</th>
                <th class="text-right">Total Sisa Disetor ke Perusahaan</th>
            </tr>
            @foreach($coordinatorSummaries as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row->name }}</td>
                    <td class="text-right">{{ number_format($row->gross_revenue, 0, ',', '.') }}</td>
                    <td class="text-right">-{{ number_format($row->commission, 0, ',', '.') }}</td>
                    <td class="text-right">-{{ number_format($row->expenses, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->net_balance, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if(isset($investorSummaries) && count($investorSummaries) > 0)
        <h2>Ringkasan Pembagian Investor</h2>
        <table class="table-compact">
            <tr>
                <th>Investor</th>
                <th class="text-right">Bagian Investor Setelah Dana Kas</th>
                <th class="text-right">Dana Kas Investor</th>
                <th class="text-right">Total Pembagian</th>
                <th class="text-right">Saldo Bersih Investor</th>
            </tr>
            @foreach($investorSummaries as $row)
                @php
                    $totalShare = $row->profit_share + $row->cash_fund;
                    $netBalance = $row->capital - $row->withdrawals;
                @endphp
                <tr>
                    <td>{{ $row->investor_name }}</td>
                    <td class="text-right">-{{ number_format($row->profit_share, 0, ',', '.') }}</td>
                    <td class="text-right">-{{ number_format($row->cash_fund, 0, ',', '.') }}</td>
                    <td class="text-right">-{{ number_format($totalShare, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($netBalance, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
