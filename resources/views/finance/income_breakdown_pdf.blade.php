<!DOCTYPE html>
<html>
<head>
    <title>Rincian Pendapatan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-end {
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
        .bg-light {
            background-color: #f8f9fc;
        }
        .fw-bold {
            font-weight: bold;
        }
        h2 {
            text-align: center;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .subtitle {
            text-align: center;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <h2>Rincian Pendapatan</h2>
    <div class="subtitle">10 Transaksi Terakhir</div>
    
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Koordinator</th>
                <th>Investor</th>
                <th class="text-end">Pendapatan Kotor</th>
                <th class="text-end">Komisi Pengurus ({{ $coordRate }}%)</th>
                <th class="text-end">Iuran Internet ({{ $ispRate }}%)</th>
                <th class="text-end">Manajemen ({{ $toolRate }}%)</th>
                <th class="text-end">Pendapatan Pengelola ({{ $managerRate }}%)</th>
                <th class="text-end" style="background-color: #ffc107; color: #fff;">Sisa Bersih (Net Balance)</th>
                <th class="text-end">Dana Kas ({{ $investorCashRate }}%)</th>
                <th class="text-end">Income Bersih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomeBreakdowns as $breakdown)
            <tr>
                <td>{{ $breakdown->date->format('d M Y') }}</td>
                <td>{{ $breakdown->coordinator_name }}</td>
                <td>{{ $breakdown->investor_names }}</td>
                <td class="text-end fw-bold">{{ number_format($breakdown->gross_amount, 0, ',', '.') }}</td>
                <td class="text-end text-danger">-{{ number_format($breakdown->commission, 0, ',', '.') }}</td>
                <td class="text-end text-danger">-{{ number_format($breakdown->isp_share, 0, ',', '.') }}</td>
                <td class="text-end text-danger">-{{ number_format($breakdown->tool_fund, 0, ',', '.') }}</td>
                <td class="text-end text-danger fw-bold">-{{ number_format($breakdown->manager_income, 0, ',', '.') }}</td>
                <td class="text-end fw-bold bg-light">{{ number_format($breakdown->net_balance, 0, ',', '.') }}</td>
                <td class="text-end text-danger">{{ $breakdown->cash_fund > 0 ? '-' . number_format($breakdown->cash_fund, 0, ',', '.') : '0' }}</td>
                <td class="text-end text-success">{{ number_format($breakdown->investor_share, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
