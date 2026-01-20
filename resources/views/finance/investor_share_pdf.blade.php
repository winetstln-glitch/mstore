<!DOCTYPE html>
<html>
<head>
    <title>Investor Share per Coordinator Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-end {
            text-align: right;
        }
        .text-danger {
            color: #e74a3b;
        }
        .text-muted {
            color: #858796;
        }
        .sub-row td {
            background-color: #f8f9fc;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Bagi Hasil Investor per Koordinator</h2>
        <p>Dibuat pada: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Koordinator</th>
                <th class="text-end">Bagi Hasil Investor (Setelah Kas)</th>
                <th class="text-end">Dana Kas Investor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coordinatorSummaries as $summary)
            <tr>
                <td><strong>{{ $summary->name }}</strong></td>
                <td class="text-end text-danger">-{{ number_format($summary->investor_share, 0, ',', '.') }}</td>
                <td class="text-end text-danger">-{{ number_format($summary->investor_cash, 0, ',', '.') }}</td>
            </tr>
            @php
                $investorDetails = $investorDetailsByCoordinator[$summary->id] ?? [];
            @endphp
            @foreach($investorDetails as $detail)
            <tr class="sub-row">
                <td>&nbsp;&nbsp;&nbsp;&nbsp;- {{ $detail['investor_name'] }}</td>
                <td class="text-end text-muted">-{{ number_format($detail['profit_share'], 0, ',', '.') }}</td>
                <td class="text-end text-muted">-{{ number_format($detail['cash_fund'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>